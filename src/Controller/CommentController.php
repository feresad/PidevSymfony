<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Questions;
use App\Form\CommentFormType;
use App\Repository\QuestionsRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class CommentController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/forum/topic/{id}/comment', name: 'comment_create', methods: ['POST'])]
    public function create(
        int $id,
        Request $request,
        QuestionsRepository $questionsRepository,
        UtilisateurRepository $utilisateurRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $question = $questionsRepository->find($id);
        if (!$question) {
            $this->logger->error('Topic not found', ['topic_id' => $id]);
            $this->addFlash('error', 'Topic not found.');
            return $this->redirectToRoute('forum_topics');
        }

        $utilisateur = $this->getUser() ?? $utilisateurRepository->findOneBy([], ['id' => 'ASC']);
        if (!$utilisateur) {
            $this->logger->error('No user found');
            $this->addFlash('error', 'No users found.');
            return $this->redirectToRoute('forum_topics');
        }

        $comment = new Commentaire();
        $comment->setQuestionId($question);
        $comment->setUtilisateurId($utilisateur);

        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $type = $request->request->get('type');
            $parentId = $request->request->get('parent_id');

            if ($type === 'comment' && $parentId) {
                $parentComment = $entityManager->getRepository(Commentaire::class)->find($parentId);
                if ($parentComment) {
                    $comment->setParentCommentaireId($parentComment);
                } else {
                    $this->logger->error('Parent comment not found', ['parent_comment_id' => $parentId]);
                    $this->addFlash('error', 'Parent comment not found.');
                    return $this->redirectToRoute('forum_single_topic', ['id' => $id]);
                }
            }

            try {
                $entityManager->beginTransaction();
                $comment->setVotes(0);
                $comment->setCreationAt(new \DateTime());
                $entityManager->persist($comment);
                $entityManager->flush();
                $entityManager->commit();

                $this->logger->info('Comment added', [
                    'comment_id' => $comment->getCommentaireId(),
                    'user_id' => $utilisateur->getId(),
                ]);

                $this->addFlash('success', 'Comment added successfully!');
            } catch (\Exception $e) {
                $entityManager->rollback();
                $this->logger->error('Error adding comment', ['error' => $e->getMessage()]);
                $this->addFlash('error', 'Error adding comment: ' . $e->getMessage());
            }
        } else {
            $errors = $form->getErrors(true, true);
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            if (!empty($errorMessages)) {
                $this->logger->warning('Comment form validation failed', ['errors' => $errorMessages]);
                $this->addFlash('error', implode(' ', $errorMessages));
            }
        }

        return $this->redirectToRoute('forum_single_topic', ['id' => $id]);
    }

    #[Route('/forum/comment/{id}/delete', name: 'comment_delete', methods: ['POST'])]
    public function delete(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $comment = $entityManager->getRepository(Commentaire::class)->find($id);
        if (!$comment) {
            $this->addFlash('error', 'Comment not found.');
            return $this->redirectToRoute('forum_topics');
        }

        $questionId = $comment->getQuestionId()->getQuestionId();

        if (!$this->isCsrfTokenValid('delete_comment_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('forum_single_topic', ['id' => $questionId]);
        }

        try {
            $entityManager->beginTransaction();
            $entityManager->remove($comment);
            $entityManager->flush();
            $entityManager->commit();
            $this->addFlash('success', 'Comment deleted successfully!');
        } catch (\Exception $e) {
            $entityManager->rollback();
            $this->logger->error('Error deleting comment', ['error' => $e->getMessage()]);
            $this->addFlash('error', 'Error deleting comment: ' . $e->getMessage());
        }

        return $this->redirectToRoute('forum_single_topic', ['id' => $questionId]);
    }

    #[Route('/forum/comment/{id}/update', name: 'comment_update', methods: ['POST'])]
    public function update(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $comment = $entityManager->getRepository(Commentaire::class)->find($id);
        if (!$comment) {
            $this->addFlash('error', 'Comment not found.');
            return $this->redirectToRoute('forum_topics');
        }

        $questionId = $comment->getQuestionId()->getQuestionId();

        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->beginTransaction();
                $entityManager->flush();
                $entityManager->commit();
                $this->addFlash('success', 'Comment updated successfully!');
            } catch (\Exception $e) {
                $entityManager->rollback();
                $this->logger->error('Error updating comment', ['error' => $e->getMessage()]);
                $this->addFlash('error', 'Error updating comment: ' . $e->getMessage());
            }
        } else {
            $errors = $form->getErrors(true, true);
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            if (!empty($errorMessages)) {
                $this->logger->warning('Comment update form validation failed', ['errors' => $errorMessages]);
                $this->addFlash('error', implode(' ', $errorMessages));
            }
        }

        return $this->redirectToRoute('forum_single_topic', ['id' => $questionId]);
    }
}