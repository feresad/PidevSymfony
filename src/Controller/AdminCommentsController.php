<?php

namespace App\Controller;

use App\Entity\Questions;
use App\Entity\Commentaire;
use App\Entity\QuestionReactions;
use App\Entity\CommentaireReactions;
use App\Form\TopicFormType;
use App\Form\CommentFormType;
use App\Repository\QuestionsRepository;
use App\Repository\CommentaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class AdminCommentsController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/admin/comments/{id}/{page}', name: 'admin_comments', requirements: ['id' => '\d+', 'page' => '\d+'], defaults: ['page' => 1], methods: ['GET', 'POST'])]
    public function comments(
        int $id,
        int $page,
        Request $request,
        QuestionsRepository $questionsRepository,
        CommentaireRepository $commentaireRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $question = $questionsRepository->find($id);
        if (!$question) {
            $this->addFlash('error', 'Question not found.');
            return $this->redirectToRoute('forum_admin');
        }

        $itemsPerPage = 10;
        $threshold = -5;

        $totalComments = $commentaireRepository->createQueryBuilder('c')
            ->select('COUNT(c.commentaire_id)')
            ->where('c.question_id = :questionId')
            ->andWhere('c.parent_commentaire_id IS NULL')
            ->setParameter('questionId', $question->getQuestionId())
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = max(1, ceil($totalComments / $itemsPerPage));
        $page = min(max(1, $page), $totalPages);

        $highQualityComments = $commentaireRepository->createQueryBuilder('c')
            ->where('c.question_id = :questionId')
            ->andWhere('c.parent_commentaire_id IS NULL')
            ->andWhere('c.votes >= :threshold')
            ->setParameter('questionId', $question->getQuestionId())
            ->setParameter('threshold', $threshold)
            ->orderBy('c.votes', 'DESC')
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage)
            ->getQuery()
            ->getResult();

        $remainingSlots = $itemsPerPage - count($highQualityComments);
        $lowQualityComments = [];
        if ($remainingSlots > 0) {
            $lowQualityComments = $commentaireRepository->createQueryBuilder('c')
                ->where('c.question_id = :questionId')
                ->andWhere('c.parent_commentaire_id IS NULL')
                ->andWhere('c.votes < :threshold')
                ->setParameter('questionId', $question->getQuestionId())
                ->setParameter('threshold', $threshold)
                ->orderBy('c.votes', 'DESC')
                ->setFirstResult(0)
                ->setMaxResults($remainingSlots)
                ->getQuery()
                ->getResult();
        }

        $topLevelComments = array_merge($highQualityComments, $lowQualityComments);

        $comment = new Commentaire();
        $comment->setQuestionId($question);
        $comment->setUtilisateurId($this->getUser());
        $commentForm = $this->createForm(CommentFormType::class, $comment, [
            'action' => $this->generateUrl('admin_comments_create', ['id' => $id]),
            'allow_extra_fields' => true,
        ]);

        $game = $question->getGameId();
        $reactionRepository = $entityManager->getRepository(QuestionReactions::class);
        $reactions = $reactionRepository->findBy(['question_id' => $question->getQuestionId()]);
        $reactionCounts = [];
        foreach ($reactions as $reaction) {
            $emoji = $reaction->getEmoji();
            $reactionCounts[$emoji] = ($reactionCounts[$emoji] ?? 0) + 1;
        }

        $questionData = [
            'id' => $question->getQuestionId(),
            'title' => $question->getTitle(),
            'content' => $question->getContent(),
            'image' => $question->getMediaType() && $question->getMediaType()->value === 'image' && $question->getMediaPath() ? $question->getMediaPath() : null,
            'video' => $question->getMediaType() && $question->getMediaType()->value === 'video' && $question->getMediaPath() ? $question->getMediaPath() : null,
            'createdAt' => $question->getCreatedAt(),
            'utilisateurId' => $question->getUtilisateurId(),
            'gameImage' => $game && $game->getImagePath() ? $game->getImagePath() : null,
            'votes' => $question->getVotes(),
            'reactionCounts' => $reactionCounts,
        ];

        $sentimentMap = $request->getSession()->get('sentiment_map', [
            'positive' => ['👍', '😊', '😂', '❤️', '🎙', '😍', '👏', '🌟', '😎', '💪'],
            'negative' => ['👎', '😢', '😡', '💔', '😤', '😞', '🤬', '😣', '💢', '😠'],
            'neutral' => ['🤔', '😐', '🙂', '👀', '🤷', '😶', '🤝', '🙄', '😴', '🤓']
        ]);

        $mapComment = function (Commentaire $comment) use (&$mapComment, $entityManager, $commentaireRepository, $sentimentMap, $id) {
            $childCommentaires = $commentaireRepository->createQueryBuilder('c')
                ->where('c.parent_commentaire_id = :parentId')
                ->setParameter('parentId', $comment->getCommentaireId())
                ->orderBy('c.votes', 'DESC')
                ->getQuery()
                ->getResult();

            $reactionRepository = $entityManager->getRepository(CommentaireReactions::class);
            $reactions = $reactionRepository->findBy(['commentaire_id' => $comment->getCommentaireId()]);
            $reactionCounts = [];
            foreach ($reactions as $reaction) {
                $emoji = $reaction->getEmoji();
                $reactionCounts[$emoji] = ($reactionCounts[$emoji] ?? 0) + 1;
            }

            $positiveCount = 0;
            $negativeCount = 0;
            $neutralCount = 0;

            foreach ($reactionCounts as $emoji => $count) {
                if (in_array($emoji, $sentimentMap['positive'])) {
                    $positiveCount += $count;
                } elseif (in_array($emoji, $sentimentMap['negative'])) {
                    $negativeCount += $count;
                } elseif (in_array($emoji, $sentimentMap['neutral'])) {
                    $neutralCount += $count;
                }
            }

            $sentiment = 'neutral';
            if ($positiveCount > $negativeCount && $positiveCount > $neutralCount) {
                $sentiment = 'positive';
            } elseif ($negativeCount > $positiveCount && $negativeCount > $neutralCount) {
                $sentiment = 'negative';
            }

            return [
                'id' => $comment->getCommentaireId(),
                'content' => strip_tags($comment->getContenu()),
                'createdAt' => $comment->getCreationAt(),
                'utilisateurId' => $comment->getUtilisateurId(),
                'childCommentaires' => array_map($mapComment, $childCommentaires),
                'votes' => $comment->getVotes(),
                'reactionCounts' => $reactionCounts,
                'sentiment' => $sentiment,
            ];
        };

        $commentData = array_map($mapComment, $topLevelComments);

        $pagination = [
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalComments,
            'itemsPerPage' => $itemsPerPage,
        ];

        return $this->render('admin_comments.html.twig', [
            'question' => $questionData,
            'comments' => $commentData,
            'comment_form' => $commentForm->createView(),
            'pagination' => $pagination,
        ]);
    }

    #[Route('/admin/comments/fetch/question/{id}', name: 'admin_comments_fetch_question', methods: ['GET'])]
    public function fetchQuestion(
        int $id,
        QuestionsRepository $questionsRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $question = $questionsRepository->find($id);
        if (!$question) {
            return new JsonResponse(['success' => false, 'message' => 'Question not found.'], 404);
        }

        $game = $question->getGameId();
        $reactionRepository = $entityManager->getRepository(QuestionReactions::class);
        $reactions = $reactionRepository->findBy(['question_id' => $question->getQuestionId()]);
        $reactionCounts = [];
        foreach ($reactions as $reaction) {
            $emoji = $reaction->getEmoji();
            $reactionCounts[$emoji] = ($reactionCounts[$emoji] ?? 0) + 1;
        }

        $questionData = [
            'id' => $question->getQuestionId(),
            'title' => $question->getTitle(),
            'content' => $question->getContent(),
            'image' => $question->getMediaType() && $question->getMediaType()->value === 'image' && $question->getMediaPath() ? $question->getMediaPath() : null,
            'video' => $question->getMediaType() && $question->getMediaType()->value === 'video' && $question->getMediaPath() ? $question->getMediaPath() : null,
            'createdAt' => $question->getCreatedAt(),
            'utilisateurId' => $question->getUtilisateurId(),
            'gameImage' => $game && $game->getImagePath() ? $game->getImagePath() : null,
            'votes' => $question->getVotes(),
            'reactionCounts' => $reactionCounts,
        ];

        $html = $this->renderView('admin_comments/_question_card.html.twig', [
            'question' => $questionData,
        ]);

        return new JsonResponse(['success' => true, 'html' => $html]);
    }

    #[Route('/admin/comments/fetch/comments/{id}/{page}', name: 'admin_comments_fetch_comments', requirements: ['id' => '\d+', 'page' => '\d+'], defaults: ['page' => 1], methods: ['GET'])]
    public function fetchComments(
        int $id,
        int $page,
        QuestionsRepository $questionsRepository,
        CommentaireRepository $commentaireRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ): JsonResponse {
        $question = $questionsRepository->find($id);
        if (!$question) {
            return new JsonResponse(['success' => false, 'message' => 'Question not found.'], 404);
        }

        $itemsPerPage = 10;
        $threshold = -5;

        $totalComments = $commentaireRepository->createQueryBuilder('c')
            ->select('COUNT(c.commentaire_id)')
            ->where('c.question_id = :questionId')
            ->andWhere('c.parent_commentaire_id IS NULL')
            ->setParameter('questionId', $question->getQuestionId())
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = max(1, ceil($totalComments / $itemsPerPage));
        $page = min(max(1, $page), $totalPages);

        $highQualityComments = $commentaireRepository->createQueryBuilder('c')
            ->where('c.question_id = :questionId')
            ->andWhere('c.parent_commentaire_id IS NULL')
            ->andWhere('c.votes >= :threshold')
            ->setParameter('questionId', $question->getQuestionId())
            ->setParameter('threshold', $threshold)
            ->orderBy('c.votes', 'DESC')
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage)
            ->getQuery()
            ->getResult();

        $remainingSlots = $itemsPerPage - count($highQualityComments);
        $lowQualityComments = [];
        if ($remainingSlots > 0) {
            $lowQualityComments = $commentaireRepository->createQueryBuilder('c')
                ->where('c.question_id = :questionId')
                ->andWhere('c.parent_commentaire_id IS NULL')
                ->andWhere('c.votes < :threshold')
                ->setParameter('questionId', $question->getQuestionId())
                ->setParameter('threshold', $threshold)
                ->orderBy('c.votes', 'DESC')
                ->setFirstResult(0)
                ->setMaxResults($remainingSlots)
                ->getQuery()
                ->getResult();
        }

        $topLevelComments = array_merge($highQualityComments, $lowQualityComments);

        $sentimentMap = $request->getSession()->get('sentiment_map', [
            'positive' => ['👍', '😊', '😂', '❤️', '🎙', '😍', '👏', '🌟', '😎', '💪'],
            'negative' => ['👎', '😢', '😡', '💔', '😤', '😞', '🤬', '😣', '💢', '😠'],
            'neutral' => ['🤔', '😐', '🙂', '👀', '🤷', '😶', '🤝', '🙄', '😴', '🤓']
        ]);

        $mapComment = function (Commentaire $comment) use (&$mapComment, $entityManager, $commentaireRepository, $sentimentMap, $id) {
            $childCommentaires = $commentaireRepository->createQueryBuilder('c')
                ->where('c.parent_commentaire_id = :parentId')
                ->setParameter('parentId', $comment->getCommentaireId())
                ->orderBy('c.votes', 'DESC')
                ->getQuery()
                ->getResult();

            $reactionRepository = $entityManager->getRepository(CommentaireReactions::class);
            $reactions = $reactionRepository->findBy(['commentaire_id' => $comment->getCommentaireId()]);
            $reactionCounts = [];
            foreach ($reactions as $reaction) {
                $emoji = $reaction->getEmoji();
                $reactionCounts[$emoji] = ($reactionCounts[$emoji] ?? 0) + 1;
            }

            $positiveCount = 0;
            $negativeCount = 0;
            $neutralCount = 0;

            foreach ($reactionCounts as $emoji => $count) {
                if (in_array($emoji, $sentimentMap['positive'])) {
                    $positiveCount += $count;
                } elseif (in_array($emoji, $sentimentMap['negative'])) {
                    $negativeCount += $count;
                } elseif (in_array($emoji, $sentimentMap['neutral'])) {
                    $neutralCount += $count;
                }
            }

            $sentiment = 'neutral';
            if ($positiveCount > $negativeCount && $positiveCount > $neutralCount) {
                $sentiment = 'positive';
            } elseif ($negativeCount > $positiveCount && $negativeCount > $neutralCount) {
                $sentiment = 'negative';
            }

            return [
                'id' => $comment->getCommentaireId(),
                'content' => strip_tags($comment->getContenu()),
                'createdAt' => $comment->getCreationAt(),
                'utilisateurId' => $comment->getUtilisateurId(),
                'childCommentaires' => array_map($mapComment, $childCommentaires),
                'votes' => $comment->getVotes(),
                'reactionCounts' => $reactionCounts,
                'sentiment' => $sentiment,
            ];
        };

        $commentData = array_map($mapComment, $topLevelComments);

        $html = $this->renderView('admin_comments/_comments_section.html.twig', [
            'comments' => $commentData,
            'question_id' => $id,
        ]);

        return new JsonResponse(['success' => true, 'html' => $html]);
    }

    #[Route('/admin/comments/fetch/update-form/{id}', name: 'admin_comments_fetch_update_form', methods: ['GET'])]
    public function fetchUpdateForm(int $id, CommentaireRepository $commentaireRepository, LoggerInterface $logger): JsonResponse
    {
        try {
            $comment = $commentaireRepository->find($id);
            if (!$comment) {
                return new JsonResponse(['success' => false, 'message' => 'Comment not found.'], 404);
            }
    
            $updateForm = $this->createForm(CommentFormType::class, $comment, [
                'action' => $this->generateUrl('admin_comments_update', ['id' => $comment->getCommentaireId()]),
                'method' => 'POST',
            ]);
    
            $commentData = [
                'id' => $comment->getCommentaireId(),
                'updateForm' => $updateForm->createView(),
            ];
    
            $html = $this->renderView('forum/_update_comment_form.html.twig', [
                'comment' => $commentData,
            ]);
    
            return new JsonResponse(['success' => true, 'html' => $html]);
        } catch (\Exception $e) {
            $logger->error('Error fetching update form for comment ID: ' . $id, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return new JsonResponse(['success' => false, 'message' => 'An error occurred while loading the update form: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/admin/comments/create/{id}', name: 'admin_comments_create', methods: ['POST'])]
    public function createComment(
        Request $request,
        int $id,
        QuestionsRepository $questionsRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $question = $questionsRepository->find($id);
        if (!$question) {
            return new JsonResponse(['success' => false, 'message' => 'Question not found.'], 404);
        }

        $utilisateur = $this->getUser();
        if (!$utilisateur) {
            return new JsonResponse(['success' => false, 'message' => 'You must be logged in to comment.'], 403);
        }

        $isReplyForm = $request->query->get('reply') === 'true';
        $comment = new Commentaire();
        $comment->setQuestionId($question);
        $comment->setUtilisateurId($utilisateur);

        $form = $this->createForm(CommentFormType::class, $comment, [
            'allow_extra_fields' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $content = $form->get('contenu')->getData();
                $parentCommentId = $form->has('parent_commentaire_id') ? $form->get('parent_commentaire_id')->getData() : null;

                if ($isReplyForm && $parentCommentId) {
                    $parentComment = $entityManager->getRepository(Commentaire::class)->find($parentCommentId);
                    if (!$parentComment) {
                        $this->logger->warning('Parent comment not found', ['parentCommentId' => $parentCommentId]);
                        return new JsonResponse(['success' => false, 'message' => 'Parent comment not found.'], 404);
                    }
                    $comment->setParentCommentaireId($parentComment);
                    $originalCommenter = $parentComment->getUtilisateurId()->getNickname();
                    $taggedContent = "@{$originalCommenter} {$content}";
                    $comment->setContenu($taggedContent);
                } else {
                    $comment->setContenu($content);
                }

                $comment->setVotes(0);
                $comment->setCreationAt(new \DateTimeImmutable());
                $entityManager->persist($comment);
                $entityManager->flush();

                $commentData = [
                    'id' => $comment->getCommentaireId(),
                    'content' => strip_tags($comment->getContenu()),
                    'createdAt' => $comment->getCreationAt()->format('F j, Y'),
                    'utilisateurId' => [
                        'nickname' => $comment->getUtilisateurId()->getNickname(),
                        'photo' => $comment->getUtilisateurId()->getPhoto() ? 'http://localhost/img/users/' . $comment->getUtilisateurId()->getPhoto() : null,
                    ],
                    'votes' => $comment->getVotes(),
                    'reactionCounts' => [],
                    'childCommentaires' => [],
                ];

                return new JsonResponse([
                    'success' => true,
                    'message' => 'Comment created successfully',
                    'comment' => $commentData,
                    'level' => $parentCommentId ? 1 : 0,
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Error saving comment to database', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return new JsonResponse(['success' => false, 'message' => 'Failed to save comment: ' . $e->getMessage()], 500);
            }
        }

        $formErrors = [];
        foreach ($form->getErrors(true) as $error) {
            $formErrors[] = $error->getMessage() . ' (Field: ' . $error->getOrigin()->getName() . ')';
        }
        $this->logger->error('Form submission failed:', ['errors' => $formErrors, 'formData' => $request->request->all()]);
        return new JsonResponse(['success' => false, 'message' => 'Form submission failed. Errors: ' . implode('; ', $formErrors)], 400);
    }

    #[Route('/admin/comments/update/{id}', name: 'admin_comments_update', methods: ['POST'])]
    public function updateComment(
        int $id,
        Request $request,
        CommentaireRepository $commentaireRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $comment = $commentaireRepository->find($id);
        if (!$comment) {
            return new JsonResponse(['success' => false, 'message' => 'Comment not found.'], 404);
        }

        $utilisateur = $this->getUser();
        if (!$utilisateur) {
            return new JsonResponse(['success' => false, 'message' => 'You must be logged in to update a comment.'], 403);
        }

        if ($comment->getUtilisateurId() !== $utilisateur && !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['success' => false, 'message' => 'You do not have permission to update this comment.'], 403);
        }

        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                return new JsonResponse(['success' => true, 'message' => 'Comment updated successfully!']);
            } catch (\Exception $e) {
                $this->logger->error('Error updating comment.', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return new JsonResponse(['success' => false, 'message' => 'An error occurred while updating the comment.'], 500);
            }
        }

        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        return new JsonResponse(['success' => false, 'message' => implode('; ', $errors)], 400);
    }

    #[Route('/admin/comments/delete/question/{id}', name: 'admin_comments_delete_question', methods: ['GET'])]
    public function deleteQuestion(
        int $id,
        QuestionsRepository $questionsRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $question = $questionsRepository->find($id);
        if (!$question) {
            $this->addFlash('error', 'Question not found.');
            return $this->redirectToRoute('forum_admin');
        }

        $utilisateur = $this->getUser();
        if (!$utilisateur) {
            $this->addFlash('error', 'You must be logged in to delete a question.');
            return $this->redirectToRoute('forum_admin');
        }

        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'You do not have permission to delete this question.');
            return $this->redirectToRoute('forum_admin');
        }

        try {
            $reactionRepository = $entityManager->getRepository(QuestionReactions::class);
            $reactions = $reactionRepository->findBy(['question_id' => $question->getQuestionId()]);
            foreach ($reactions as $reaction) {
                $entityManager->remove($reaction);
            }

            $commentRepository = $entityManager->getRepository(Commentaire::class);
            $comments = $commentRepository->findBy(['question_id' => $question->getQuestionId()]);
            foreach ($comments as $comment) {
                $commentReactions = $entityManager->getRepository(CommentaireReactions::class)
                    ->findBy(['commentaire_id' => $comment->getCommentaireId()]);
                foreach ($commentReactions as $commentReaction) {
                    $entityManager->remove($commentReaction);
                }
                $entityManager->remove($comment);
            }

            $entityManager->remove($question);
            $entityManager->flush();

            $this->addFlash('success', 'Question deleted successfully!');
        } catch (\Exception $e) {
            $this->logger->error('Error deleting question.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->addFlash('error', 'An error occurred while deleting the question.');
        }

        return $this->redirectToRoute('forum_admin');
    }

    #[Route('/admin/comments/delete/comment/{id}', name: 'admin_comments_delete_comment', methods: ['GET'])]
    public function deleteComment(
        int $id,
        CommentaireRepository $commentaireRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $comment = $commentaireRepository->find($id);
        if (!$comment) {
            return new JsonResponse(['success' => false, 'message' => 'Comment not found.'], 404);
        }

        $utilisateur = $this->getUser();
        if (!$utilisateur) {
            return new JsonResponse(['success' => false, 'message' => 'You must be logged in to delete a comment.'], 403);
        }

        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['success' => false, 'message' => 'You do not have permission to delete this comment.'], 403);
        }

        try {
            $reactionRepository = $entityManager->getRepository(CommentaireReactions::class);
            $reactions = $reactionRepository->findBy(['commentaire_id' => $comment->getCommentaireId()]);
            foreach ($reactions as $reaction) {
                $entityManager->remove($reaction);
            }

            $childComments = $commentaireRepository->findBy(['parent_commentaire_id' => $comment->getCommentaireId()]);
            foreach ($childComments as $childComment) {
                $childReactions = $reactionRepository->findBy(['commentaire_id' => $childComment->getCommentaireId()]);
                foreach ($childReactions as $childReaction) {
                    $entityManager->remove($childReaction);
                }
                $entityManager->remove($childComment);
            }

            $entityManager->remove($comment);
            $entityManager->flush();

            return new JsonResponse(['success' => true, 'message' => 'Comment deleted successfully!']);
        } catch (\Exception $e) {
            $this->logger->error('Error deleting comment.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return new JsonResponse(['success' => false, 'message' => 'An error occurred while deleting the comment.'], 500);
        }
    }

    #[Route('/admin/comments/fetch/question-update-form/{id}', name: 'admin_comments_fetch_question_update_form', methods: ['GET'])]
    public function fetchQuestionUpdateForm(
        int $id,
        QuestionsRepository $questionsRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $question = $questionsRepository->find($id);
        if (!$question) {
            return new JsonResponse(['success' => false, 'message' => 'Question not found.'], 404);
        }

        $updateForm = $this->createForm(TopicFormType::class, $question, [
            'action' => $this->generateUrl('forum_update_topic', ['id' => $question->getQuestionId()]),
        ]);

        $questionData = [
            'id' => $question->getQuestionId(),
            'updateForm' => $updateForm->createView(),
        ];

        $html = $this->renderView('admin_comments/_update_question_form.html.twig', [
            'question' => $questionData,
        ]);

        return new JsonResponse(['success' => true, 'html' => $html]);
    }
}