<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Questions;
use App\Entity\QuestionVotes;
use App\Entity\CommentaireVotes;
use App\Entity\QuestionReactions;
use App\Entity\CommentaireReactions;
use App\Enum\VoteType;
use App\Form\CommentFormType;
use App\Repository\QuestionsRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CommentController extends AbstractController
{
   private $logger;
    private $httpClient;
    private $sightengineApiUser;
    private $sightengineApiSecret;

    public function __construct(
        LoggerInterface $logger,
        HttpClientInterface $httpClient,
        string $sightengineApiUser,
        string $sightengineApiSecret
    ) {
        $this->logger = $logger;
        $this->httpClient = $httpClient;
        $this->sightengineApiUser = $sightengineApiUser;
        $this->sightengineApiSecret = $sightengineApiSecret;
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
            $this->addFlash('error', 'Topic not found.');
            return $this->redirectToRoute('forum_topics');
        }

        $userId = $request->request->get('user_id') ?? $request->headers->get('X-User-Id');
        if (!$userId) {
            $this->addFlash('error', 'You must be logged in to comment.');
            return $this->redirectToRoute('forum_single_topic', ['id' => $id]);
        }

        $utilisateur = $utilisateurRepository->find($userId);
        if (!$utilisateur) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('forum_single_topic', ['id' => $id]);
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

                $this->addFlash('success', 'Comment added successfully!');
            } catch (\Exception $e) {
                $entityManager->rollback();
                $this->logger->error('Error adding comment', ['error' => $e->getMessage()]);
                $this->addFlash('error', 'Error adding comment: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('forum_single_topic', ['id' => $id]);
    }
    #[Route('/api/check-profanity', name: 'api_check_profanity', methods: ['POST'])]
    public function checkProfanity(Request $request): JsonResponse
    {
        $content = $request->request->get('content');
        if (!$content) {
            return new JsonResponse(['success' => false, 'message' => 'Content is required'], 400);
        }

        try {
            $response = $this->httpClient->request('GET', 'https://api.sightengine.com/1.0/text/check.json', [
                'query' => [
                    'text' => $content,
                    'lang' => 'en',
                    'mode' => 'standard',
                    'api_user' => $this->sightengineApiUser,
                    'api_secret' => $this->sightengineApiSecret,
                ],
            ]);

            $data = $response->toArray();
            $isProfane = !empty($data['profanity']['matches']);

            return new JsonResponse([
                'success' => true,
                'isProfane' => $isProfane,
                'details' => $isProfane ? $data['profanity']['matches'] : null,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Sightengine API error', ['error' => $e->getMessage()]);
            return new JsonResponse(['success' => false, 'message' => 'Error checking profanity'], 500);
        }
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

    #[Route('/vote', name: 'vote_action', methods: ['POST'])]
    public function voteAction(
        Request $request,
        EntityManagerInterface $entityManager,
        UtilisateurRepository $utilisateurRepository
    ): RedirectResponse {
        $id = $request->request->get('id');
        $type = $request->request->get('type');
        $voteType = $request->request->get('vote_type');

        if (!$this->isCsrfTokenValid('vote_action', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('forum_topics');
        }

        $utilisateur = $this->getUser() ?? $utilisateurRepository->findOneBy([], ['id' => 'ASC']);
        if (!$utilisateur) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('forum_topics');
        }

        try {
            $entityManager->beginTransaction();

            if ($type === 'question') {
                $entity = $entityManager->getRepository(Questions::class)->find($id);
                $voteEntityClass = QuestionVotes::class;
                $voteEntityProperty = 'question_id';
            } else {
                $entity = $entityManager->getRepository(Commentaire::class)->find($id);
                $voteEntityClass = CommentaireVotes::class;
                $voteEntityProperty = 'commentaire_id';
            }

            if (!$entity) {
                $entityManager->rollback();
                $this->addFlash('error', ucfirst($type) . ' not found.');
                return $this->redirectToRoute('forum_topics');
            }

            $voteRepository = $entityManager->getRepository($voteEntityClass);
            $existingVote = $voteRepository->findOneBy([
                $voteEntityProperty => $id,
                'user_id' => $utilisateur->getId(),
            ]);

            $voteTypeEnum = VoteType::from($voteType);

            if ($existingVote) {
                $currentVoteType = $existingVote->getVoteType();
                if ($currentVoteType->value === $voteType) {
                    $entityManager->remove($existingVote);
                    if ($voteType === 'UP') {
                        $entity->setVotes($entity->getVotes() - 1);
                    } else {
                        $entity->setVotes($entity->getVotes() + 1);
                    }
                } else {
                    if ($voteType === 'UP') {
                        $entity->setVotes($entity->getVotes() + 2);
                    } else {
                        $entity->setVotes($entity->getVotes() - 2);
                    }
                    $existingVote->setVoteType($voteTypeEnum);
                }
            } else {
                $vote = new $voteEntityClass();
                $vote->setUserId($utilisateur);
                $vote->setVoteType($voteTypeEnum);
                if ($type === 'question') {
                    $vote->setQuestionId($entity);
                } else {
                    $vote->setCommentaireId($entity);
                }
                $entityManager->persist($vote);

                if ($voteType === 'UP') {
                    $entity->setVotes($entity->getVotes() + 1);
                } else {
                    $entity->setVotes($entity->getVotes() - 1);
                }
            }

            $entityManager->flush();
            $entityManager->commit();

            $this->logger->info(ucfirst($type) . ' vote processed', [
                'id' => $id,
                'type' => $type,
                'vote_type' => $voteType,
                'user_id' => $utilisateur->getId(),
                'new_vote_count' => $entity->getVotes(),
            ]);

            $this->addFlash('success', 'Vote recorded successfully!');
        } catch (\Exception $e) {
            $entityManager->rollback();
            $this->logger->error('Error processing vote', [
                'error' => $e->getMessage(),
                'id' => $id,
                'type' => $type,
                'vote_type' => $voteType,
            ]);
            $this->addFlash('error', 'Error processing vote: ' . $e->getMessage());
        }

        if ($type === 'question') {
            return $this->redirectToRoute('forum_single_topic', ['id' => $id]);
        } else {
            $comment = $entityManager->getRepository(Commentaire::class)->find($id);
            $questionId = $comment->getQuestionId()->getQuestionId();
            return $this->redirectToRoute('forum_single_topic', ['id' => $questionId]);
        }
    }

    #[Route('/react', name: 'react_action', methods: ['POST'])]
    public function reactAction(
        Request $request,
        EntityManagerInterface $entityManager,
        UtilisateurRepository $utilisateurRepository
    ): JsonResponse {
        $id = $request->request->get('id');
        $type = $request->request->get('type');
        $emoji = $request->request->get('emoji');
        $action = $request->request->get('action', 'react'); // Default to 'react' if not specified

        if (!$id || !$type) {
            return new JsonResponse(['success' => false, 'message' => 'Missing required parameters'], 400);
        }

        $utilisateur = $this->getUser() ?? $utilisateurRepository->findOneBy([], ['id' => 'ASC']);
        if (!$utilisateur && $action !== 'fetch') {
            return new JsonResponse(['success' => false, 'message' => 'User not found'], 401);
        }

        try {
            if ($type === 'question') {
                $entity = $entityManager->getRepository(Questions::class)->find($id);
                $reactionEntityClass = QuestionReactions::class;
                $reactionEntityProperty = 'question_id';
            } else {
                $entity = $entityManager->getRepository(Commentaire::class)->find($id);
                $reactionEntityClass = CommentaireReactions::class;
                $reactionEntityProperty = 'commentaire_id';
            }

            if (!$entity) {
                return new JsonResponse(['success' => false, 'message' => ucfirst($type) . ' not found'], 404);
            }

            $reactionRepository = $entityManager->getRepository($reactionEntityClass);
            $reactions = $reactionRepository->findBy([$reactionEntityProperty => $id]);
            $reactionCounts = [];
            foreach ($reactions as $r) {
                $reactionCounts[$r->getEmoji()] = ($reactionCounts[$r->getEmoji()] ?? 0) + 1;
            }

            if ($action === 'fetch') {
                return new JsonResponse([
                    'success' => true,
                    'reactionCounts' => $reactionCounts,
                ]);
            }

            if (!$emoji) {
                return new JsonResponse(['success' => false, 'message' => 'Emoji is required for reaction'], 400);
            }

            $entityManager->beginTransaction();

            $existingReaction = $reactionRepository->findOneBy([
                $reactionEntityProperty => $id,
                'user_id' => $utilisateur->getId(),
            ]);

            if ($existingReaction) {
                if ($existingReaction->getEmoji() === $emoji) {
                    $entityManager->remove($existingReaction);
                    $this->logger->info(ucfirst($type) . ' reaction removed', [
                        'id' => $id,
                        'type' => $type,
                        'emoji' => $emoji,
                        'user_id' => $utilisateur->getId(),
                    ]);
                } else {
                    $oldEmoji = $existingReaction->getEmoji();
                    $existingReaction->setEmoji($emoji);
                    $entityManager->persist($existingReaction);
                    $this->logger->info(ucfirst($type) . ' reaction updated', [
                        'id' => $id,
                        'type' => $type,
                        'old_emoji' => $oldEmoji,
                        'new_emoji' => $emoji,
                        'user_id' => $utilisateur->getId(),
                    ]);
                }
            } else {
                $reaction = new $reactionEntityClass();
                $reaction->setUserId($utilisateur);
                $reaction->setEmoji($emoji);
                if ($type === 'question') {
                    $reaction->setQuestionId($entity);
                } else {
                    $reaction->setCommentaireId($entity);
                }
                $entityManager->persist($reaction);
                $this->logger->info(ucfirst($type) . ' reaction added', [
                    'id' => $id,
                    'type' => $type,
                    'emoji' => $emoji,
                    'user_id' => $utilisateur->getId(),
                ]);
            }

            $entityManager->flush();
            $entityManager->commit();

            $reactions = $reactionRepository->findBy([$reactionEntityProperty => $id]);
            $reactionCounts = [];
            foreach ($reactions as $r) {
                $reactionCounts[$r->getEmoji()] = ($reactionCounts[$r->getEmoji()] ?? 0) + 1;
            }

            return new JsonResponse([
                'success' => true,
                'reactionCounts' => $reactionCounts,
            ]);
        } catch (\Exception $e) {
            $entityManager->rollback();
            $this->logger->error('Error processing reaction', [
                'error' => $e->getMessage(),
                'id' => $id,
                'type' => $type,
                'emoji' => $emoji,
            ]);
            return new JsonResponse(['success' => false, 'message' => 'Error processing reaction'], 500);
        }
    }
}