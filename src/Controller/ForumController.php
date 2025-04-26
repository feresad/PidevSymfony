<?php

namespace App\Controller;

use App\Entity\Questions;
use App\Entity\Utilisateur;
use App\Entity\Commentaire;
use App\Entity\QuestionReactions;
use App\Entity\CommentaireReactions;
use App\Entity\QuestionVotes;
use App\Form\TopicFormType;
use App\Form\CommentFormType;
use App\Repository\QuestionsRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\CommentaireRepository;
use App\Service\RedditService;
use App\Service\TopicSubscriptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ForumController extends AbstractController
{
    private $logger;
    private $subscriptionService;
    private $translator;

    public function __construct(LoggerInterface $logger, TopicSubscriptionService $subscriptionService, TranslatorInterface $translator)
    {
        $this->logger = $logger;
        $this->subscriptionService = $subscriptionService;
        $this->translator = $translator;
    }

    #[Route('/forum', name: 'forum_index')]
    public function index(): Response
    {
        return $this->render('forum/index.html.twig');
    }

    #[Route('/forum/topics', name: 'forum_topics', methods: ['GET', 'POST'])]
    public function topics(
        Request $request,
        QuestionsRepository $questionsRepository,
        UtilisateurRepository $utilisateurRepository,
        EntityManagerInterface $entityManager,
        RedditService $redditService
    ): Response {
        /** @var Utilisateur|null $utilisateur */
        $utilisateur = $this->getUser();
        if (!$utilisateur) {
            $this->addFlash('error', $this->translator->trans('flash.login_required'));
            return $this->redirectToRoute('app_login_page');
        }
    
        $question = new Questions();
        $question->setUtilisateurId($utilisateur);
        $form = $this->createForm(TopicFormType::class, $question);
        $form->handleRequest($request);
    
        if ($form->isSubmitted()) {
            $errors = [];
    
            // Extract form data
            $title = $form->get('title')->getData();
            $content = $form->get('content')->getData();
            $mediaFile = $form->get('media_file')->getData();
            $mediaType = $form->get('media_type')->getData();
            $gameId = $form->get('game_id')->getData();
    
            // Validate title
            if (empty($title)) {
                $errors['title'] = $this->translator->trans('form.error.title_blank');
            }
    
            if (empty($content)) {
                $errors['content'] = $this->translator->trans('form.error.content_blank');
            }
    
            if (!$gameId) {
                $errors['game_id'] = $this->translator->trans('form.error.game_required');
            }
    
            if ($mediaFile) {
                if (!$mediaType) {
                    $errors['media_type'] = $this->translator->trans('form.error.media_type_required');
                } else {
                    $mimeType = $mediaFile->getMimeType();
                    $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    $allowedVideoTypes = ['video/mp4', 'video/mpeg', 'video/webm', 'video/quicktime', 'video/x-msvideo'];
                    $maxFileSize = 30 * 1024 * 1024; 
    
                    if ($mediaFile->getSize() > $maxFileSize) {
                        $errors['media_file'] = $this->translator->trans('form.error.file_too_large');
                    }
    
                    if ($mediaType->value === 'image' && !in_array($mimeType, $allowedImageTypes)) {
                        $errors['media_file'] = $this->translator->trans('form.error.invalid_image');
                    } elseif ($mediaType->value === 'video' && !in_array($mimeType, $allowedVideoTypes)) {
                        $errors['media_file'] = $this->translator->trans('form.error.invalid_video');
                    }
                }
            }
    
            if (empty($errors)) {
                try {
                    if ($mediaFile) {
                        $mediaFilename = uniqid() . '.' . $mediaFile->guessExtension();
                        $uploadsDirectory = $this->getParameter('uploads_directory');
    
                        if (!is_dir($uploadsDirectory)) {
                            mkdir($uploadsDirectory, 0777, true);
                            $this->logger->info($this->translator->trans('log.created_uploads_directory'), ['directory' => $uploadsDirectory]);
                        }
    
                        $mediaFile->move($uploadsDirectory, $mediaFilename);
                        $this->logger->info($this->translator->trans('log.media_file_uploaded'), [
                            'filename' => $mediaFilename,
                            'path' => $uploadsDirectory . '\\' . $mediaFilename,
                        ]);
                        $question->setMediaPath($mediaFilename);
                        $question->setMediaType($mediaType);
                    } else {
                        $question->setMediaPath(null);
                    }
    
                    $question->setTitle($title);
                    $question->setContent($content);
                    $question->setGameId($gameId);
                    $question->setVotes(0);
                    $question->setCreatedAt(new \DateTime());
    
                    $entityManager->persist($question);
                    $entityManager->flush();
    
                    $this->addFlash('success', $this->translator->trans('flash.topic_created'));
                    return $this->redirectToRoute('forum_topics');
                } catch (\Exception $e) {
                    $this->logger->error($this->translator->trans('log.error_creating_topic'), [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $this->addFlash('error', $this->translator->trans('flash.error_creating_topic', ['%error%' => $e->getMessage()]));
                }
            } else {
                foreach ($errors as $field => $message) {
                    $this->addFlash('error', $message);
                }
            }
        }
    
        $sentimentMap = $request->getSession()->get('sentiment_map', [
            'positive' => ['👍', '😊', '😂', '❤️', '🎉', '😍', '👏', '🌟', '😎', '💪'],
            'negative' => ['👎', '😢', '😡', '💔', '😤', '😞', '🤬', '😣', '💢', '😠'],
            'neutral' => ['🤔', '😐', '🙂', '👀', '🤷', '😶', '🤝', '🙄', '😴', '🤓']
        ]);
    
        $page = $request->query->getInt('page', 1);
        $limit = 10;
        $threshold = -5;
    
        $totalQuestions = $questionsRepository->createQueryBuilder('q')
            ->select('COUNT(q.question_id)')
            ->innerJoin('q.utilisateur_id', 'u')
            ->getQuery()
            ->getSingleScalarResult();
    
        $highQualityQuestions = $questionsRepository->createQueryBuilder('q')
            ->innerJoin('q.utilisateur_id', 'u')
            ->where('q.votes >= :threshold')
            ->setParameter('threshold', $threshold)
            ->orderBy('q.votes', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    
        $remaining = $limit - count($highQualityQuestions);
        $lowQualityQuestions = [];
        if ($remaining > 0) {
            $lowQualityQuestions = $questionsRepository->createQueryBuilder('q')
                ->innerJoin('q.utilisateur_id', 'u')
                ->where('q.votes < :threshold')
                ->setParameter('threshold', $threshold)
                ->orderBy('q.votes', 'DESC')
                ->setFirstResult(max(0, ($page - 1) * $limit - count($highQualityQuestions)))
                ->setMaxResults($remaining)
                ->getQuery()
                ->getResult();
        }
    
        $questions = array_merge($highQualityQuestions, $lowQualityQuestions);
    
        $topics = array_map(function (Questions $question) use ($entityManager, $sentimentMap, $request) {
            $user = $question->getUtilisateurId();
            $game = $question->getGameId();
    
            $updateForm = $this->createForm(TopicFormType::class, $question, [
                'action' => $this->generateUrl('forum_update_topic', ['id' => $question->getQuestionId()]),
            ]);
    
            $reactionRepository = $entityManager->getRepository(QuestionReactions::class);
            $reactions = $reactionRepository->findBy(['question_id' => $question->getQuestionId()]);
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
                'id' => $question->getQuestionId(),
                'title' => $question->getTitle(),
                'content' => $question->getContent(),
                'votes' => $question->getVotes(),
                'image' => $question->getMediaType() && $question->getMediaType()->value === 'image' && $question->getMediaPath() ? $question->getMediaPath() : null,
                'video' => $question->getMediaType() && $question->getMediaType()->value === 'video' && $question->getMediaPath() ? $question->getMediaPath() : null,
                'icon' => 'ion-chatboxes',
                'locked' => false,
                'startedBy' => $user ? $user->getNickname() : $this->translator->trans('general.unknown_user'),
                'startedById' => $user ? $user->getId() : null,
                'startedOn' => $question->getCreatedAt() ? $question->getCreatedAt()->format('F j, Y') : $this->translator->trans('general.date_not_set'),
                'postCount' => $question->getCommentaires()->count(),
                'lastActivityUser' => $user ? $user->getNickname() : $this->translator->trans('general.unknown_user'),
                'lastActivityAvatar' => $user && $user->getPhoto() ? $user->getPhoto() : 'avatar-1.jpg',
                'lastActivityDate' => $question->getCreatedAt() ? $question->getCreatedAt()->format('F j, Y') : $this->translator->trans('general.date_not_set'),
                'gameImage' => $game && $game->getImagePath() ? $game->getImagePath() : null,
                'updateForm' => $updateForm->createView(),
                'reactionCounts' => $reactionCounts,
                'sentiment' => $sentiment,
            ];
        }, $questions);
    
        $trendingPosts = $redditService->fetchTopGamingPosts(5);
        $totalPages = ceil($totalQuestions / $limit);
    
        return $this->render('forum/topics.html.twig', [
            'topics' => $topics,
            'newTopicForm' => $form->createView(),
            'trendingPosts' => $trendingPosts,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalItems' => $totalQuestions,
                'itemsPerPage' => $limit,
            ],
        ]);
    }

    #[Route('/forum/topic/{id}', name: 'forum_single_topic', methods: ['GET', 'POST'])]
    public function singleTopic(
        int $id,
        Request $request,
        QuestionsRepository $questionsRepository,
        CommentaireRepository $commentaireRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $question = $questionsRepository->find($id);
        if (!$question) {
            $this->addFlash('error', $this->translator->trans('flash.topic_not_found'));
            return $this->redirectToRoute('forum_topics');
        }
    
        $itemsPerPage = 10; 
        $page = max(1, $request->query->getInt('page', 1));
        $threshold = -5;
    
        $totalComments = $commentaireRepository->createQueryBuilder('c')
            ->select('COUNT(c.commentaire_id)') 
            ->where('c.question_id = :questionId')
            ->andWhere('c.parent_commentaire_id IS NULL')
            ->setParameter('questionId', $question->getQuestionId())
            ->getQuery()
            ->getSingleScalarResult();
    
        $totalPages = max(1, ceil($totalComments / $itemsPerPage));
        $page = min($page, $totalPages); 
    
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
        $commentForm = $this->createForm(CommentFormType::class, $comment, [
            'action' => $this->generateUrl('comment_create', ['id' => $id]),
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
            'positive' => ['👍', '😊', '😂', '❤️', '🎉', '😍', '👏', '🌟', '😎', '💪'],
            'negative' => ['👎', '😢', '😡', '💔', '😤', '😞', '🤬', '😣', '💢', '😠'],
            'neutral' => ['🤔', '😐', '🙂', '👀', '🤷', '😶', '🤝', '🙄', '😴', '🤓']
        ]);
    
        $mapComment = function (Commentaire $comment) use (&$mapComment, $entityManager, $commentaireRepository, $sentimentMap, $request) {
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
                'content' => $comment->getContenu(),
                'createdAt' => $comment->getCreationAt(),
                'utilisateurId' => $comment->getUtilisateurId(),
                'childCommentaires' => array_map($mapComment, $childCommentaires),
                'updateForm' => $this->createForm(CommentFormType::class, $comment, [
                    'action' => $this->generateUrl('comment_update', ['id' => $comment->getCommentaireId()]),
                ])->createView(),
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
    
        /** @var Utilisateur|null $user */
        $user = $this->getUser();
        $isSubscribed = $user ? $this->subscriptionService->isSubscribed($user, $question) : false;

        return $this->render('forum/single_topic.html.twig', [
            'question' => $questionData,
            'comments' => $commentData,
            'comment_form' => $commentForm->createView(),
            'pagination' => $pagination,
            'is_subscribed' => $isSubscribed,
        ]);
    }

    #[Route('/api/get-sentiment-map', name: 'api_get_sentiment_map', methods: ['GET'])]
    public function getSentimentMap(Request $request): JsonResponse
    {
        $sentimentMap = $request->getSession()->get('sentiment_map', [
            'positive' => ['👍', '😊', '😂', '❤️', '🎉', '😍', '👏', '🌟', '😎', '💪'],
            'negative' => ['👎', '😢', '😡', '💔', '😤', '😞', '🤬', '😣', '💢', '😠'],
            'neutral' => ['🤔', '😐', '🙂', '👀', '🤷', '😶', '🤝', '🙄', '😴', '🤓']
        ]);

        return new JsonResponse(['success' => true, 'sentimentMap' => $sentimentMap]);
    }

    #[Route('/api/update-sentiment-map', name: 'api_update_sentiment_map', methods: ['POST'])]
    public function updateSentimentMap(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $sentimentMap = $data['sentimentMap'] ?? [];

        if (!isset($sentimentMap['positive']) || !isset($sentimentMap['negative']) || !isset($sentimentMap['neutral'])) {
            return new JsonResponse(['success' => false, 'message' => $this->translator->trans('api.error.invalid_sentiment_map')], 400);
        }

        $request->getSession()->set('sentiment_map', $sentimentMap);

        return new JsonResponse(['success' => true, 'message' => $this->translator->trans('api.sentiment_map_updated')]);
    }

    #[Route('/forum/topic/delete/{id}', name: 'forum_delete_topic', methods: ['GET'])]
    public function deleteTopic(int $id, QuestionsRepository $questionsRepository, EntityManagerInterface $entityManager): Response
    {
        $question = $questionsRepository->find($id);
        if (!$question) {
            $this->addFlash('error', $this->translator->trans('flash.topic_not_found'));
            return $this->redirectToRoute('forum_topics');
        }

        /** @var Utilisateur|null $utilisateur */
        $utilisateur = $this->getUser();
        if (!$utilisateur || $question->getUtilisateurId()->getId() !== $utilisateur->getId()) {
            $this->addFlash('error', $this->translator->trans('flash.unauthorized_delete'));
            return $this->redirectToRoute('forum_topics');
        }

        try {
            if ($question->getMediaPath()) {
                $mediaPath = $this->getParameter('uploads_directory') . '\\' . $question->getMediaPath();
                if (file_exists($mediaPath)) {
                    unlink($mediaPath);
                    $this->logger->info($this->translator->trans('log.deleted_media_file'), ['path' => $mediaPath]);
                }
            }

            $entityManager->remove($question);
            $entityManager->flush();
            $this->addFlash('success', $this->translator->trans('flash.topic_deleted'));
        } catch (\Exception $e) {
            $this->logger->error($this->translator->trans('log.error_deleting_topic'), ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $this->addFlash('error', $this->translator->trans('flash.error_deleting_topic', ['%error%' => $e->getMessage()]));
        }

        return $this->redirectToRoute('forum_topics');
    }

    #[Route('/forum/topic/update/{id}', name: 'forum_update_topic', methods: ['POST'])]
    public function updateTopic(int $id, Request $request, QuestionsRepository $questionsRepository, EntityManagerInterface $entityManager): Response
    {
        $question = $questionsRepository->find($id);
        if (!$question) {
            $this->addFlash('error', $this->translator->trans('flash.topic_not_found'));
            return $this->redirectToRoute('forum_topics');
        }
    
        /** @var Utilisateur|null $utilisateur */
        $utilisateur = $this->getUser();
        if (!$utilisateur || $question->getUtilisateurId()->getId() !== $utilisateur->getId()) {
            $this->addFlash('error', $this->translator->trans('flash.unauthorized_update'));
            return $this->redirectToRoute('forum_topics');
        }
    
        $updateForm = $this->createForm(TopicFormType::class, $question);
        $updateForm->handleRequest($request);
    
        if ($updateForm->isSubmitted()) {
            $errors = [];
    
            $title = $updateForm->get('title')->getData();
            $content = $updateForm->get('content')->getData();
            $mediaFile = $updateForm->get('media_file')->getData();
            $mediaType = $updateForm->get('media_type')->getData();
            $gameId = $updateForm->get('game_id')->getData();
    
            if (empty($title)) {
                $errors['title'] = $this->translator->trans('form.error.title_blank');
            }
    
            if (empty($content)) {
                $errors['content'] = $this->translator->trans('form.error.content_blank');
            }
    
            if (!$gameId) {
                $errors['game_id'] = $this->translator->trans('form.error.game_required');
            }
    
            if ($mediaFile) {
                if (!$mediaType) {
                    $errors['media_type'] = $this->translator->trans('form.error.media_type_required');
                } else {
                    $mimeType = $mediaFile->getMimeType();
                    $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    $allowedVideoTypes = ['video/mp4', 'video/mpeg', 'video/webm', 'video/quicktime', 'video/x-msvideo'];
                    $maxFileSize = 30 * 1024 * 1024; // 30MB in bytes
    
                    if ($mediaFile->getSize() > $maxFileSize) {
                        $errors['media_file'] = $this->translator->trans('form.error.file_too_large');
                    }
    
                    if ($mediaType->value === 'image' && !in_array($mimeType, $allowedImageTypes)) {
                        $errors['media_file'] = $this->translator->trans('form.error.invalid_image');
                    } elseif ($mediaType->value === 'video' && !in_array($mimeType, $allowedVideoTypes)) {
                        $errors['media_file'] = $this->translator->trans('form.error.invalid_video');
                    }
                }
            }
    
            if (empty($errors)) {
                try {
                    if ($mediaFile) {
                        if ($question->getMediaPath()) {
                            $oldMediaPath = $this->getParameter('uploads_directory') . '\\' . $question->getMediaPath();
                            if (file_exists($oldMediaPath)) {
                                unlink($oldMediaPath);
                                $this->logger->info($this->translator->trans('log.deleted_old_media_file'), ['path' => $oldMediaPath]);
                            }
                        }
    
                        $mediaFilename = uniqid() . '.' . $mediaFile->guessExtension();
                        $uploadsDirectory = $this->getParameter('uploads_directory');
    
                        $mediaFile->move($uploadsDirectory, $mediaFilename);
                        $this->logger->info($this->translator->trans('log.media_file_uploaded'), [
                            'filename' => $mediaFilename,
                            'path' => $uploadsDirectory . '\\' . $mediaFilename,
                        ]);
                        $question->setMediaPath($mediaFilename);
                        $question->setMediaType($mediaType);
                    } elseif ($mediaType === null || $mediaType->value === null) {
                        if ($question->getMediaPath()) {
                            $oldMediaPath = $this->getParameter('uploads_directory') . '\\' . $question->getMediaPath();
                            if (file_exists($oldMediaPath)) {
                                unlink($oldMediaPath);
                                $this->logger->info($this->translator->trans('log.deleted_old_media_file_no_type'), ['path' => $oldMediaPath]);
                            }
                        }
                        $question->setMediaPath(null);
                        $question->setMediaType(null);
                    }
    
                    $question->setTitle($title);
                    $question->setContent($content);
                    $question->setGameId($gameId);
    
                    $entityManager->flush();
                    $this->addFlash('success', $this->translator->trans('flash.topic_updated'));
                } catch (\Exception $e) {
                    $this->logger->error($this->translator->trans('log.error_updating_topic'), ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                    $this->addFlash('error', $this->translator->trans('flash.error_updating_topic', ['%error%' => $e->getMessage()]));
                }
            } else {
                foreach ($errors as $field => $message) {
                    $this->addFlash('error', $message);
                }
            }
        }
    
        return $this->redirectToRoute('forum_topics');
    }

    #[Route('/ajax/vote', name: 'ajax_vote_action', methods: ['POST'])]
    public function ajaxVoteAction(Request $request, EntityManagerInterface $entityManager, QuestionsRepository $questionsRepository): JsonResponse
    {
        $id = $request->request->get('id');
        $type = $request->request->get('type');
        $voteType = $request->request->get('vote_type');

        /** @var Utilisateur|null $utilisateur */
        $utilisateur = $this->getUser();
        if (!$utilisateur) {
            return new JsonResponse(['success' => false, 'message' => $this->translator->trans('api.error.login_required')], 401);
        }

        if ($type === 'question') {
            $entity = $questionsRepository->find($id);
            if (!$entity) {
                return new JsonResponse(['success' => false, 'message' => $this->translator->trans('api.error.question_not_found')], 404);
            }

            // Check if the user has already voted
            $voteRepository = $entityManager->getRepository(QuestionVotes::class);
            $existingVote = $voteRepository->findOneBy([
                'question_id' => $entity,
                'user_id' => $utilisateur,
            ]);

            $hasUpvoted = $existingVote && $existingVote->getVoteType()->value === 'UP';
            $hasDownvoted = $existingVote && $existingVote->getVoteType()->value === 'DOWN';

            // Validate vote type
            if (!in_array($voteType, ['UP', 'DOWN'])) {
                return new JsonResponse(['success' => false, 'message' => $this->translator->trans('api.error.invalid_vote_type')], 400);
            }

            // Check if the user is trying to repeat the same vote
            if ($voteType === 'UP' && $hasUpvoted) {
                return new JsonResponse(['success' => false, 'message' => $this->translator->trans('api.error.already_upvoted')], 403);
            }
            if ($voteType === 'DOWN' && $hasDownvoted) {
                return new JsonResponse(['success' => false, 'message' => $this->translator->trans('api.error.already_downvoted')], 403);
            }

            $currentVotes = $entity->getVotes() ?? 0;

            if ($existingVote) {
                // User has voted before, so they are switching or adding the opposite vote
                if ($voteType === 'UP' && $hasDownvoted) {
                    // Switch from downvote to upvote
                    $existingVote->setVoteType(\App\Enum\VoteType::from('UP'));
                    $entity->setVotes($currentVotes + 1); // Remove downvote (-1) and add upvote (+1)
                } elseif ($voteType === 'DOWN' && $hasUpvoted) {
                    // Switch from upvote to downvote
                    $existingVote->setVoteType(\App\Enum\VoteType::from('DOWN'));
                    $entity->setVotes($currentVotes - 1); // Remove upvote (+1) and add downvote (-1)
                }
            } else {
                // First vote for this user on this question
                $newVote = new QuestionVotes();
                $newVote->setQuestionId($entity);
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

            // Fetch updated voting status
            $updatedVote = $voteRepository->findOneBy([
                'question_id' => $entity,
                'user_id' => $utilisateur,
            ]);

            return new JsonResponse([
                'success' => true,
                'votes' => $entity->getVotes(),
                'hasUpvoted' => $updatedVote && $updatedVote->getVoteType()->value === 'UP',
                'hasDownvoted' => $updatedVote && $updatedVote->getVoteType()->value === 'DOWN',
            ]);
        } elseif ($type === 'comment') {
            $entity = $entityManager->getRepository(Commentaire::class)->find($id);
            if (!$entity) {
                return new JsonResponse(['success' => false, 'message' => $this->translator->trans('api.error.comment_not_found')], 404);
            }

            $currentVotes = $entity->getVotes() ?? 0;
            if ($voteType === 'UP') {
                $entity->setVotes($currentVotes + 1);
            } elseif ($voteType === 'DOWN') {
                $entity->setVotes($currentVotes - 1);
            } else {
                return new JsonResponse(['success' => false, 'message' => $this->translator->trans('api.error.invalid_vote_type')], 400);
            }

            $entityManager->persist($entity);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'votes' => $entity->getVotes(),
            ]);
        }

        return new JsonResponse(['success' => false, 'message' => $this->translator->trans('api.error.invalid_type')], 400);
    }

    #[Route('/ajax/fetch-user-votes', name: 'ajax_fetch_user_votes', methods: ['POST'])]
    public function ajaxFetchUserVotes(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var Utilisateur|null $utilisateur */
        $utilisateur = $this->getUser();
        if (!$utilisateur) {
            return new JsonResponse(['success' => false, 'message' => $this->translator->trans('api.error.login_required')], 401);
        }
    
        $topicIds = $request->request->get('topicIds', []);
        if (!is_array($topicIds) || empty($topicIds)) {
            return new JsonResponse(['success' => false, 'message' => $this->translator->trans('api.error.no_topic_ids')], 400);
        }
    
        $voteRepository = $entityManager->getRepository(QuestionVotes::class);
        $qb = $voteRepository->createQueryBuilder('v')
            ->select('v')
            ->where('v.user_id = :user')
            ->andWhere('v.question_id IN (:topicIds)')
            ->setParameter('user', $utilisateur)
            ->setParameter('topicIds', $topicIds);
    
        $votes = $qb->getQuery()->getResult();
    
        $voteData = array_map(function (QuestionVotes $vote) {
            return [
                'topicId' => $vote->getQuestionId()->getQuestionId(),
                'voteType' => $vote->getVoteType()->value,
            ];
        }, $votes);
    
        return new JsonResponse([
            'success' => true,
            'votes' => $voteData,
        ]);
    }

    #[Route('/api/share/topic', name: 'api_share_topic', methods: ['GET'])]
    public function shareTopic(Request $request, QuestionsRepository $questionsRepository): JsonResponse
    {
        $id = $request->query->get('id');
        if (!$id) {
            return new JsonResponse(['success' => false, 'message' => $this->translator->trans('api.error.topic_id_required')], 400);
        }

        $question = $questionsRepository->find($id);
        if (!$question) {
            return new JsonResponse(['success' => false, 'message' => $this->translator->trans('api.error.topic_not_found')], 404);
        }

        $topicUrl = $this->generateUrl('forum_single_topic', ['id' => $id], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
        $imageUrl = $question->getMediaType() && $question->getMediaType()->value === 'image' && $question->getMediaPath()
            ? $this->generateUrl('app_home', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL) . 'img/games/' . $question->getMediaPath()
            : $this->generateUrl('app_home', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL) . 'assets/images/default-game.jpg';

        $shareData = [
            'success' => true,
            'url' => $topicUrl,
            'title' => $question->getTitle() ?? '',
            'content' => strip_tags($question->getContent() ?? ''),
            'image' => $imageUrl,
        ];

        return new JsonResponse($shareData);
    }

    #[Route('/switch-language/{locale}', name: 'switch_language', methods: ['GET'])]
public function switchLanguage(string $locale, Request $request, SessionInterface $session): Response
{
    // Update the list of allowed locales to include 'ar'
    if (!in_array($locale, ['en', 'fr', 'es', 'ar'])) {
        $locale = 'en'; // Fallback to English
    }

    $session->set('_locale', $locale);
    $request->setLocale($locale);

    $referer = $request->headers->get('referer');
    return $this->redirect($referer ?: $this->generateUrl('forum_index'));
}
}