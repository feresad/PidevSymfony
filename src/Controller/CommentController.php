<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\Commentaire;
use App\Entity\Notification;
use App\Entity\Questions;
use App\Entity\QuestionVotes;
use App\Entity\CommentaireVotes;
use App\Entity\QuestionReactions;
use App\Entity\CommentaireReactions;
use App\Enum\VoteType;
use App\Form\CommentFormType;
use App\Repository\QuestionsRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\NotificationRepository;
use App\Service\TopicSubscriptionService;
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
        EntityManagerInterface $entityManager,
        TopicSubscriptionService $subscriptionService
    ): Response {
        $question = $questionsRepository->find($id);
        if (!$question) {
            $this->addFlash('error', 'Topic not found.');
            return $this->redirectToRoute('forum_topics');
        }

        /** @var Utilisateur|null $utilisateur */
        $utilisateur = $this->getUser();
        if (!$utilisateur) {
            $this->addFlash('error', 'Vous devez être connecté pour commenter.');
            return $this->redirectToRoute('app_login_page');
        }

        $comment = new Commentaire();
        $comment->setQuestionId($question);
        $comment->setUtilisateurId($utilisateur);

        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $errors = [];

            $content = $form->get('contenu')->getData();
            $plainText = strip_tags($content);
            $plainText = trim($plainText);
            if (empty($plainText)) {
                $errors['contenu'] = 'Comment content cannot be empty.';
            }

            if (empty($errors)) {
                $type = $request->request->get('type');
                $parentId = $request->request->get('parent_id');
                $parentComment = null;

                if ($type === 'comment' && $parentId) {
                    $parentComment = $entityManager->getRepository(Commentaire::class)->find($parentId);
                    if ($parentComment) {
                        $comment->setParentCommentaireId($parentComment);
                        $originalCommenter = $parentComment->getUtilisateurId()->getNickname();
                        $taggedContent = "@{$originalCommenter} {$content}";
                        $comment->setContenu($taggedContent);
                    } else {
                        $this->addFlash('error', 'Parent comment not found.');
                        return $this->redirectToRoute('forum_single_topic', ['id' => $id]);
                    }
                } else {
                    $comment->setContenu($content);
                }

                try {
                    $comment->setVotes(0);
                    $comment->setCreationAt(new \DateTime());
                    $entityManager->persist($comment);

                    $targetUser = $parentComment ? $parentComment->getUtilisateurId() : $question->getUtilisateurId();
                    if ($targetUser && $targetUser->getId() !== $utilisateur->getId()) {
                        $notification = new Notification();
                        $notification->setUser($targetUser);
                        $notification->setMessage("Quelqu'un a commenté votre " . ($parentComment ? "commentaire" : "question") . ": '{$question->getTitle()}'");
                        $entityManager->persist($notification);
                    }

                    $entityManager->flush();

                    if (isset($notification)) {
                        $notification->setLink($this->generateUrl('forum_single_topic', ['id' => $question->getQuestionId()]) . '#comment-' . $comment->getCommentaireId());
                        $entityManager->flush();
                    }

                    // Notify subscribers of the new comment
                    $subscriptionService->notifySubscribers($question, $comment, $utilisateur);

                    $this->addFlash('success', 'Commentaire ajouté avec succès !');
                } catch (\Exception $e) {
                    $this->logger->error('Error adding comment or notification', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $this->addFlash('error', 'Error adding comment: ' . $e->getMessage());
                }
            } else {
                foreach ($errors as $message) {
                    $this->addFlash('error', $message);
                }
            }
        }

        return $this->redirectToRoute('forum_single_topic', [
            'id' => $id,
            'image_base_url2' => $this->getParameter('image_base_url2')
        ]);
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

        /** @var Utilisateur|null $utilisateur */
        $utilisateur = $this->getUser();
        if (!$utilisateur || $comment->getUtilisateurId()->getId() !== $utilisateur->getId()) {
            $this->addFlash('error', 'You are not authorized to update this comment.');
            return $this->redirectToRoute('forum_single_topic', ['id' => $comment->getQuestionId()->getQuestionId(), 'image_base_url2' => $this->getParameter('image_base_url2')]);
        }

        $questionId = $comment->getQuestionId()->getQuestionId();

        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $errors = [];

            $content = $form->get('contenu')->getData();
            $plainText = strip_tags($content);
            $plainText = trim($plainText);
            if (empty($plainText)) {
                $errors['contenu'] = 'Comment content cannot be empty.';
            }

            if (empty($errors)) {
                try {
                    $entityManager->beginTransaction();
                    $comment->setContenu($content);
                    $entityManager->flush();
                    $entityManager->commit();
                    $this->addFlash('success', 'Comment updated successfully!');
                } catch (\Exception $e) {
                    $entityManager->rollback();
                    $this->logger->error('Error updating comment', ['error' => $e->getMessage()]);
                    $this->addFlash('error', 'Error updating comment: ' . $e->getMessage());
                }
            } else {
                foreach ($errors as $message) {
                    $this->addFlash('error', $message);
                }
            }
        }

        return $this->redirectToRoute('forum_single_topic', ['id' => $questionId]);
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

        /** @var Utilisateur|null $utilisateur */
        $utilisateur = $this->getUser();
        if (!$utilisateur || $comment->getUtilisateurId()->getId() !== $utilisateur->getId()) {
            $this->addFlash('error', 'You are not authorized to delete this comment.');
            return $this->redirectToRoute('forum_single_topic', ['id' => $comment->getQuestionId()->getQuestionId()]);
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

    #[Route('/vote', name: 'vote_action', methods: ['POST'])]
    public function voteAction(
        Request $request,
        EntityManagerInterface $entityManager,
        UtilisateurRepository $utilisateurRepository
    ): RedirectResponse {
        $id = $request->request->get('id');
        $type = $request->request->get('type');
        $voteType = $request->request->get('vote_type');

        /** @var Utilisateur|null $utilisateur */
        $utilisateur = $this->getUser();
        if (!$utilisateur) {
            $this->addFlash('error', 'You must be logged in to vote.');
            return $this->redirectToRoute('app_login_page');
        }

        if (!$this->isCsrfTokenValid('vote_action', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
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

    #[Route('/ajax/vote-comment', name: 'ajax_vote_comment_action', methods: ['POST'])]
    public function ajaxVoteCommentAction(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $id = $request->request->get('id');
        $voteType = $request->request->get('vote_type');

        /** @var Utilisateur|null $utilisateur */
        $utilisateur = $this->getUser();
        if (!$utilisateur) {
            return new JsonResponse(['success' => false, 'message' => 'You must be logged in to vote.'], 401);
        }

        $entity = $entityManager->getRepository(Commentaire::class)->find($id);
        if (!$entity) {
            return new JsonResponse(['success' => false, 'message' => 'Comment not found.'], 404);
        }

        $voteRepository = $entityManager->getRepository(CommentaireVotes::class);
        $existingVote = $voteRepository->findOneBy([
            'commentaire_id' => $entity,
            'user_id' => $utilisateur,
        ]);

        $hasUpvoted = $existingVote && $existingVote->getVoteType()->value === 'UP';
        $hasDownvoted = $existingVote && $existingVote->getVoteType()->value === 'DOWN';

        if (!in_array($voteType, ['UP', 'DOWN'])) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid vote type.'], 400);
        }

        if ($voteType === 'UP' && $hasUpvoted) {
            return new JsonResponse(['success' => false, 'message' => 'You have already upvoted this comment.'], 403);
        }
        if ($voteType === 'DOWN' && $hasDownvoted) {
            return new JsonResponse(['success' => false, 'message' => 'You have already downvoted this comment.'], 403);
        }

        $currentVotes = $entity->getVotes() ?? 0;

        if ($existingVote) {
            if ($voteType === 'UP' && $hasDownvoted) {
                $existingVote->setVoteType(\App\Enum\VoteType::from('UP'));
                $entity->setVotes($currentVotes + 1);
            } elseif ($voteType === 'DOWN' && $hasUpvoted) {
                $existingVote->setVoteType(\App\Enum\VoteType::from('DOWN'));
                $entity->setVotes($currentVotes - 1); 
            }
        } else {
            $newVote = new CommentaireVotes();
            $newVote->setCommentaireId($entity);
            $newVote->setUserId($utilisateur);
            $newVote->setVoteType(\App\Enum\VoteType::from($voteType));

            if ($voteType === 'UP') {
                $entity->setVotes($currentVotes + 1);
            } elseif ($voteType === 'DOWN') {
                $entity->setVotes($currentVotes - 1);
            }

            $entityManager->persist($newVote);
        }

        $entityManager->persist($entity);
        $entityManager->flush();

        $updatedVote = $voteRepository->findOneBy([
            'commentaire_id' => $entity,
            'user_id' => $utilisateur,
        ]);

        return new JsonResponse([
            'success' => true,
            'votes' => $entity->getVotes(),
            'hasUpvoted' => $updatedVote && $updatedVote->getVoteType()->value === 'UP',
            'hasDownvoted' => $updatedVote && $updatedVote->getVoteType()->value === 'DOWN',
        ]);
    }

    #[Route('/ajax/fetch-user-comment-votes', name: 'ajax_fetch_user_comment_votes', methods: ['POST'])]
    public function ajaxFetchUserCommentVotes(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var Utilisateur|null $utilisateur */
        $utilisateur = $this->getUser();
        if (!$utilisateur) {
            return new JsonResponse(['success' => false, 'message' => 'You must be logged in to fetch votes.'], 401);
        }
    
        $commentIds = $request->request->get('commentIds', []);
        if (!is_array($commentIds) || empty($commentIds)) {
            return new JsonResponse(['success' => false, 'message' => 'No comment IDs provided.'], 400);
        }
    
        $voteRepository = $entityManager->getRepository(CommentaireVotes::class);
        $qb = $voteRepository->createQueryBuilder('v')
            ->select('v')
            ->where('v.user_id = :user')
            ->andWhere('v.commentaire_id IN (:commentIds)')
            ->setParameter('user', $utilisateur)
            ->setParameter('commentIds', $commentIds);
    
        $votes = $qb->getQuery()->getResult();
    
        $voteData = array_map(function (CommentaireVotes $vote) {
            return [
                'commentId' => $vote->getCommentaireId()->getCommentaireId(),
                'voteType' => $vote->getVoteType()->value,
            ];
        }, $votes);
    
        return new JsonResponse([
            'success' => true,
            'votes' => $voteData,
        ]);
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
        $action = $request->request->get('action', 'react');

        if (!$id || !$type) {
            return new JsonResponse(['success' => false, 'message' => 'Missing required parameters'], 400);
        }

        /** @var Utilisateur|null $utilisateur */
        $utilisateur = $this->getUser();
        if (!$utilisateur && $action !== 'fetch') {
            return new JsonResponse(['success' => false, 'message' => 'You must be logged in to react'], 401);
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

    #[Route('/api/notifications', name: 'api_notifications', methods: ['GET'])]
    public function getNotifications(NotificationRepository $notificationRepository, LoggerInterface $logger): JsonResponse
    {
        /** @var Utilisateur|null $utilisateur */
        $utilisateur = $this->getUser();
        if (!$utilisateur) {
            $logger->warning('Unauthenticated access to /api/notifications');
            return new JsonResponse(['success' => false, 'message' => 'You must be logged in.'], 401);
        }

        try {
            $logger->info('Fetching unread notifications for user', ['userId' => $utilisateur->getId()]);
            $notifications = $notificationRepository->findUnreadByUser($utilisateur->getId());
            $logger->info('Found notifications', ['count' => count($notifications)]);

            $data = array_map(function (Notification $notification) use ($logger) {
                $logger->debug('Processing notification', ['id' => $notification->getId()]);
                return [
                    'id' => $notification->getId(),
                    'message' => $notification->getMessage(),
                    'link' => $notification->getLink(),
                    'createdAt' => $notification->getCreatedAt()->format('Y-m-d H:i:s'),
                ];
            }, $notifications);

            return new JsonResponse(['success' => true, 'notifications' => $data]);
        } catch (\Exception $e) {
            $logger->error('Error fetching notifications', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'userId' => $utilisateur->getId()
            ]);
            return new JsonResponse(['success' => false, 'message' => 'Error fetching notifications: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/api/notifications/read/{id}', name: 'api_notifications_read', methods: ['POST'])]
    public function markNotificationRead(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var Utilisateur|null $utilisateur */
        $utilisateur = $this->getUser();
        if (!$utilisateur) {
            return new JsonResponse(['success' => false, 'message' => 'You must be logged in.'], 401);
        }

        $notification = $entityManager->getRepository(Notification::class)->find($id);
        if (!$notification || $notification->getUser()->getId() !== $utilisateur->getId()) {
            return new JsonResponse(['success' => false, 'message' => 'Notification not found or not authorized.'], 404);
        }

        $notification->setIsRead(true);
        $entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Notification marked as read']);
    }

    #[Route('/api/notifications/read-all', name: 'api_notifications_mark_all_read', methods: ['POST'])]
    public function markAllNotificationsAsRead(NotificationRepository $notificationRepository, LoggerInterface $logger): JsonResponse
    {
        /** @var Utilisateur|null $utilisateur */
        $utilisateur = $this->getUser();
        if (!$utilisateur) {
            $logger->warning('Unauthenticated access to /api/notifications/read-all');
            return new JsonResponse(['success' => false, 'message' => 'You must be logged in.'], 401);
        }

        try {
            $logger->info('Marking all notifications as read for user', ['userId' => $utilisateur->getId()]);

            $qb = $notificationRepository->createQueryBuilder('n')
                ->update()
                ->set('n.isRead', ':isRead')
                ->where('n.user = :userId')
                ->andWhere('n.isRead = :isReadFalse')
                ->setParameter('isRead', true)
                ->setParameter('isReadFalse', false)
                ->setParameter('userId', $utilisateur->getId());

            $affectedRows = $qb->getQuery()->execute();
            $logger->info('Updated notifications', ['affectedRows' => $affectedRows]);

            return new JsonResponse(['success' => true, 'message' => sprintf('Marked %d notifications as read', $affectedRows)]);
        } catch (\Exception $e) {
            $logger->error('Error marking all notifications as read', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'userId' => $utilisateur->getId()
            ]);
            return new JsonResponse(['success' => false, 'message' => 'Error marking all notifications as read: ' . $e->getMessage()], 500);
        }
    }
}