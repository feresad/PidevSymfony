<?php

namespace App\Controller;

use App\Entity\Questions;
use App\Entity\Utilisateur;
use App\Entity\Commentaire;
use App\Entity\QuestionReactions;
use App\Entity\CommentaireReactions;
use App\Form\TopicFormType;
use App\Form\CommentFormType;
use App\Repository\QuestionsRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\CommentaireRepository;
use App\Service\RedditService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class ForumController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
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
        $question = new Questions();
        $utilisateur = $utilisateurRepository->findOneBy([], ['id' => 'ASC']);
        if (!$utilisateur) {
            $this->addFlash('error', 'No users found in the database. Please add a user.');
            return $this->render('forum/topics.html.twig', [
                'topics' => [],
                'newTopicForm' => null,
                'trendingPosts' => [],
            ]);
        }

        $question->setUtilisateurId($utilisateur);
        $form = $this->createForm(TopicFormType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $mediaFile = $form->get('media_file')->getData();
                $mediaType = $form->get('media_type')->getData();

                $this->logger->info('Form submitted with media file and type.', [
                    'media_file_exists' => $mediaFile ? 'Yes' : 'No',
                    'media_type' => $mediaType ? $mediaType->value : 'none',
                ]);

                if ($mediaFile) {
                    $mimeType = $mediaFile->getMimeType();
                    $isImage = in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif']);
                    $isVideo = in_array($mimeType, ['video/mp4', 'video/mpeg', 'video/webm', 'video/quicktime', 'video/x-msvideo']);

                    if ($mediaType && $mediaType->value === 'image' && !$isImage) {
                        $this->logger->error('Media type mismatch: Selected image, but file is not an image.', ['mime_type' => $mimeType]);
                        throw new \Exception('Selected media type is "image," but the uploaded file is not an image.');
                    }

                    if ($mediaType && $mediaType->value === 'video' && !$isVideo) {
                        $this->logger->error('Media type mismatch: Selected video, but file is not a video.', ['mime_type' => $mimeType]);
                        throw new \Exception('Selected media type is "video," but the uploaded file is not a video.');
                    }

                    $mediaFilename = uniqid() . '.' . $mediaFile->guessExtension();
                    $uploadsDirectory = $this->getParameter('uploads_directory');

                    if (!is_dir($uploadsDirectory)) {
                        mkdir($uploadsDirectory, 0777, true);
                        $this->logger->info('Created uploads directory.', ['directory' => $uploadsDirectory]);
                    }

                    $mediaFile->move($uploadsDirectory, $mediaFilename);
                    $this->logger->info('Media file uploaded successfully.', [
                        'filename' => $mediaFilename,
                        'path' => $uploadsDirectory . '\\' . $mediaFilename,
                    ]);
                    $question->setMediaPath($mediaFilename);
                    $question->setMediaType($mediaType);
                } else {
                    $question->setMediaPath(null);
                }

                $question->setVotes(0);
                $question->setCreatedAt(new \DateTime());

                $entityManager->persist($question);
                $entityManager->flush();

                $this->addFlash('success', 'Topic created successfully!');
                return $this->redirectToRoute('forum_topics');
            } catch (\Exception $e) {
                $this->logger->error('Error creating topic.', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $this->addFlash('error', 'An error occurred while creating the topic: ' . $e->getMessage());
            }
        }

        $sentimentMap = $request->getSession()->get('sentiment_map', [
            'positive' => ['👍', '😊', '😂', '❤️', '🎉', '😍', '👏', '🌟', '😎', '💪'],
            'negative' => ['👎', '😢', '😡', '💔', '😤', '😞', '🤬', '😣', '💢', '😠'],
            'neutral' => ['🤔', '😐', '🙂', '👀', '🤷', '😶', '🤝', '🙄', '😴', '🤓']
        ]);

        $threshold = -5;

        $highQualityQuestions = $questionsRepository->createQueryBuilder('q')
            ->where('q.votes >= :threshold')
            ->setParameter('threshold', $threshold)
            ->orderBy('q.votes', 'DESC')
            ->getQuery()
            ->getResult();

        $lowQualityQuestions = $questionsRepository->createQueryBuilder('q')
            ->where('q.votes < :threshold')
            ->setParameter('threshold', $threshold)
            ->orderBy('q.votes', 'DESC')
            ->getQuery()
            ->getResult();

        $questions = array_merge($highQualityQuestions, $lowQualityQuestions);

        $topics = array_map(function (Questions $question) use ($entityManager, $sentimentMap) {
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
                'startedBy' => $user ? $user->getNickname() : 'Unknown',
                'startedOn' => $question->getCreatedAt()->format('F j, Y'),
                'postCount' => $question->getCommentaires()->count(),
                'lastActivityUser' => $user ? $user->getNickname() : 'Unknown',
                'lastActivityAvatar' => $user && $user->getPhoto() ? $user->getPhoto() : 'avatar-1.jpg',
                'lastActivityDate' => $question->getCreatedAt()->format('F j, Y'),
                'gameImage' => $game && $game->getImagePath() ? $game->getImagePath() : null,
                'updateForm' => $updateForm->createView(),
                'reactionCounts' => $reactionCounts,
                'sentiment' => $sentiment,
            ];
        }, $questions);

        $trendingPosts = $redditService->fetchTopGamingPosts(5);

        return $this->render('forum/topics.html.twig', [
            'topics' => $topics,
            'newTopicForm' => $form->createView(),
            'trendingPosts' => $trendingPosts,
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
            $this->addFlash('error', 'Topic not found.');
            return $this->redirectToRoute('forum_topics');
        }

        $threshold = -5;
        $highQualityComments = $commentaireRepository->createQueryBuilder('c')
            ->where('c.question_id = :questionId')
            ->andWhere('c.parent_commentaire_id IS NULL')
            ->andWhere('c.votes >= :threshold')
            ->setParameter('questionId', $question->getQuestionId())
            ->setParameter('threshold', $threshold)
            ->orderBy('c.votes', 'DESC')
            ->getQuery()
            ->getResult();

        $lowQualityComments = $commentaireRepository->createQueryBuilder('c')
            ->where('c.question_id = :questionId')
            ->andWhere('c.parent_commentaire_id IS NULL')
            ->andWhere('c.votes < :threshold')
            ->setParameter('questionId', $question->getQuestionId())
            ->setParameter('threshold', $threshold)
            ->orderBy('c.votes', 'DESC')
            ->getQuery()
            ->getResult();

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

        $mapComment = function (Commentaire $comment) use (&$mapComment, $entityManager, $commentaireRepository, $sentimentMap) {
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

        return $this->render('forum/single_topic.html.twig', [
            'question' => $questionData,
            'comments' => $commentData,
            'comment_form' => $commentForm->createView(),
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
            return new JsonResponse(['success' => false, 'message' => 'Invalid sentiment map format'], 400);
        }

        $request->getSession()->set('sentiment_map', $sentimentMap);

        return new JsonResponse(['success' => true, 'message' => 'Sentiment map updated']);
    }

    #[Route('/forum/topic/delete/{id}', name: 'forum_delete_topic', methods: ['GET'])]
    public function deleteTopic(int $id, QuestionsRepository $questionsRepository, EntityManagerInterface $entityManager): Response
    {
        $question = $questionsRepository->find($id);
        if (!$question) {
            $this->addFlash('error', 'Topic not found.');
            return $this->redirectToRoute('forum_topics');
        }

        try {
            if ($question->getMediaPath()) {
                $mediaPath = $this->getParameter('uploads_directory') . '\\' . $question->getMediaPath();
                if (file_exists($mediaPath)) {
                    unlink($mediaPath);
                    $this->logger->info('Deleted media file.', ['path' => $mediaPath]);
                }
            }

            $entityManager->remove($question);
            $entityManager->flush();
            $this->addFlash('success', 'Topic deleted successfully!');
        } catch (\Exception $e) {
            $this->logger->error('Error deleting topic.', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $this->addFlash('error', 'An error occurred while deleting the topic: ' . $e->getMessage());
        }

        return $this->redirectToRoute('forum_topics');
    }

    #[Route('/forum/topic/update/{id}', name: 'forum_update_topic', methods: ['POST'])]
    public function updateTopic(int $id, Request $request, QuestionsRepository $questionsRepository, EntityManagerInterface $entityManager): Response
    {
        $question = $questionsRepository->find($id);
        if (!$question) {
            $this->addFlash('error', 'Topic not found.');
            return $this->redirectToRoute('forum_topics');
        }

        $updateForm = $this->createForm(TopicFormType::class, $question);
        $updateForm->handleRequest($request);

        if ($updateForm->isSubmitted() && $updateForm->isValid()) {
            try {
                $mediaFile = $updateForm->get('media_file')->getData();
                $mediaType = $updateForm->get('media_type')->getData();

                if ($mediaFile) {
                    $mimeType = $mediaFile->getMimeType();
                    $isImage = in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif']);
                    $isVideo = in_array($mimeType, ['video/mp4', 'video/mpeg', 'video/webm', 'video/quicktime', 'video/x-msvideo']);

                    if ($mediaType && $mediaType->value === 'image' && !$isImage) {
                        $this->logger->error('Media type mismatch: Selected image, but file is not an image.', ['mime_type' => $mimeType]);
                        throw new \Exception('Selected media type is "image," but the uploaded file is not an image.');
                    }

                    if ($mediaType && $mediaType->value === 'video' && !$isVideo) {
                        $this->logger->error('Media type mismatch: Selected video, but file is not a video.', ['mime_type' => $mimeType]);
                        throw new \Exception('Selected media type is "video," but the uploaded file is not a video.');
                    }

                    if ($question->getMediaPath()) {
                        $oldMediaPath = $this->getParameter('uploads_directory') . '\\' . $question->getMediaPath();
                        if (file_exists($oldMediaPath)) {
                            unlink($oldMediaPath);
                            $this->logger->info('Deleted old media file.', ['path' => $oldMediaPath]);
                        }
                    }

                    $mediaFilename = uniqid() . '.' . $mediaFile->guessExtension();
                    $uploadsDirectory = $this->getParameter('uploads_directory');

                    $mediaFile->move($uploadsDirectory, $mediaFilename);
                    $question->setMediaPath($mediaFilename);
                    $question->setMediaType($mediaType);
                } elseif ($mediaType === null || $mediaType->value === null) {
                    if ($question->getMediaPath()) {
                        $oldMediaPath = $this->getParameter('uploads_directory') . '\\' . $question->getMediaPath();
                        if (file_exists($oldMediaPath)) {
                            unlink($oldMediaPath);
                            $this->logger->info('Deleted old media file due to media_type set to None.', ['path' => $oldMediaPath]);
                        }
                    }
                    $question->setMediaPath(null);
                    $question->setMediaType(null);
                }

                $entityManager->flush();
                $this->addFlash('success', 'Topic updated successfully!');
            } catch (\Exception $e) {
                $this->logger->error('Error updating topic.', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                $this->addFlash('error', 'An error occurred while updating the topic: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('forum_topics');
    }

    #[Route('/vote', name: 'vote_action', methods: ['POST'])]
    public function voteAction(Request $request, EntityManagerInterface $entityManager, QuestionsRepository $questionsRepository): Response
    {
        $id = $request->request->get('id');
        $type = $request->request->get('type');
        $voteType = $request->request->get('vote_type');

        if ($type === 'question') {
            $entity = $questionsRepository->find($id);
            if (!$entity) {
                $this->addFlash('error', 'Question not found.');
                return $this->redirectToRoute('forum_topics');
            }

            $currentVotes = $entity->getVotes() ?? 0;
            if ($voteType === 'UP') {
                $entity->setVotes($currentVotes + 1);
            } elseif ($voteType === 'DOWN') {
                $entity->setVotes($currentVotes - 1);
            }

            $entityManager->persist($entity);
            $entityManager->flush();

            $this->addFlash('success', 'Vote recorded successfully!');
            return $this->redirectToRoute('forum_single_topic', ['id' => $id]);
        } elseif ($type === 'comment') {
            $entity = $entityManager->getRepository(Commentaire::class)->find($id);
            if (!$entity) {
                $this->addFlash('error', 'Comment not found.');
                return $this->redirectToRoute('forum_topics');
            }

            $currentVotes = $entity->getVotes() ?? 0;
            if ($voteType === 'UP') {
                $entity->setVotes($currentVotes + 1);
            } elseif ($voteType === 'DOWN') {
                $entity->setVotes($currentVotes - 1);
            }

            $entityManager->persist($entity);
            $entityManager->flush();

            $this->addFlash('success', 'Vote recorded successfully!');
            return $this->redirectToRoute('forum_single_topic', ['id' => $entity->getQuestionId()->getQuestionId()]);
        }

        $this->addFlash('error', 'Invalid vote type.');
        return $this->redirectToRoute('forum_topics');
    }

    #[Route('/ajax/vote', name: 'ajax_vote_action', methods: ['POST'])]
    public function ajaxVoteAction(Request $request, EntityManagerInterface $entityManager, QuestionsRepository $questionsRepository): JsonResponse
    {
        $id = $request->request->get('id');
        $type = $request->request->get('type');
        $voteType = $request->request->get('vote_type');

        if ($type === 'question') {
            $entity = $questionsRepository->find($id);
            if (!$entity) {
                return new JsonResponse(['success' => false, 'message' => 'Question not found.'], 404);
            }

            $currentVotes = $entity->getVotes() ?? 0;
            if ($voteType === 'UP') {
                $entity->setVotes($currentVotes + 1);
            } elseif ($voteType === 'DOWN') {
                $entity->setVotes($currentVotes - 1);
            } else {
                return new JsonResponse(['success' => false, 'message' => 'Invalid vote type.'], 400);
            }

            $entityManager->persist($entity);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'votes' => $entity->getVotes(),
            ]);
        } elseif ($type === 'comment') {
            $entity = $entityManager->getRepository(Commentaire::class)->find($id);
            if (!$entity) {
                return new JsonResponse(['success' => false, 'message' => 'Comment not found.'], 404);
            }

            $currentVotes = $entity->getVotes() ?? 0;
            if ($voteType === 'UP') {
                $entity->setVotes($currentVotes + 1);
            } elseif ($voteType === 'DOWN') {
                $entity->setVotes($currentVotes - 1);
            } else {
                return new JsonResponse(['success' => false, 'message' => 'Invalid vote type.'], 400);
            }

            $entityManager->persist($entity);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'votes' => $entity->getVotes(),
            ]);
        }

        return new JsonResponse(['success' => false, 'message' => 'Invalid type.'], 400);
    }

    #[Route('/api/share/topic', name: 'api_share_topic', methods: ['GET'])]
    public function shareTopic(Request $request, QuestionsRepository $questionsRepository): JsonResponse
    {
        $id = $request->query->get('id');
        if (!$id) {
            return new JsonResponse(['success' => false, 'message' => 'Topic ID is required.'], 400);
        }

        $question = $questionsRepository->find($id);
        if (!$question) {
            return new JsonResponse(['success' => false, 'message' => 'Topic not found.'], 404);
        }

        $topicUrl = $this->generateUrl('forum_single_topic', ['id' => $id], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
        $imageUrl = $question->getMediaType() && $question->getMediaType()->value === 'image' && $question->getMediaPath()
            ? $this->generateUrl('app_home', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL) . 'img/games/' . $question->getMediaPath()
            : $this->generateUrl('app_home', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL) . 'assets/images/default-game.jpg';

        $shareData = [
            'success' => true,
            'url' => $topicUrl,
            'title' => $question->getTitle() ?? '',
            'content' => strip_tags($question->getContent() ?? ''), // Full content, no truncation
            'image' => $imageUrl,
        ];

        return new JsonResponse($shareData);
    }
}