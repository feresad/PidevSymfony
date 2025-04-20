<?php

namespace App\Controller;

use App\Entity\Questions;
use App\Entity\Utilisateur;
use App\Entity\Commentaire;
use App\Entity\QuestionReactions;
use App\Form\TopicFormType;
use App\Repository\QuestionsRepository;
use App\Repository\UtilisateurRepository;
use App\Service\RedditService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class AdminForumController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/admin/forum', name: 'forum_admin', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        QuestionsRepository $questionsRepository,
        UtilisateurRepository $utilisateurRepository,
        EntityManagerInterface $entityManager,
        RedditService $redditService
    ): Response {
        $user = $this->getUser();
        // Check if user has ROLE_ADMIN using getRoles()
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('error', 'Access denied. Admin privileges required.');
            return $this->redirectToRoute('app_login_page');
        }

        $question = new Questions();
        $question->setUtilisateurId($user);
        $form = $this->createForm(TopicFormType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $errors = [];

            $title = $form->get('title')->getData();
            $content = $form->get('content')->getData();
            $mediaFile = $form->get('media_file')->getData();
            $mediaType = $form->get('media_type')->getData();
            $gameId = $form->get('game_id')->getData();

            if (empty($title)) $errors['title'] = 'The topic title cannot be blank.';
            if (empty($content)) $errors['content'] = 'The content cannot be blank.';
            if (!$gameId) $errors['game_id'] = 'Please select a game.';

            if ($mediaFile) {
                if (!$mediaType) {
                    $errors['media_type'] = 'Please select a media type if you are uploading a file.';
                } else {
                    $mimeType = $mediaFile->getMimeType();
                    $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    $allowedVideoTypes = ['video/mp4', 'video/mpeg', 'video/webm', 'video/quicktime', 'video/x-msvideo'];
                    $maxFileSize = 30 * 1024 * 1024;

                    if ($mediaFile->getSize() > $maxFileSize) {
                        $errors['media_file'] = 'The file is too large. Maximum allowed size is 30MB.';
                    }

                    if ($mediaType->value === 'image' && !in_array($mimeType, $allowedImageTypes)) {
                        $errors['media_file'] = 'Selected media type is "image," but the uploaded file is not an image.';
                    } elseif ($mediaType->value === 'video' && !in_array($mimeType, $allowedVideoTypes)) {
                        $errors['media_file'] = 'Selected media type is "video," but the uploaded file is not a video.';
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
                        }
                        $mediaFile->move($uploadsDirectory, $mediaFilename);
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

                    $this->addFlash('success', 'Topic created successfully!');
                    return $this->redirectToRoute('forum_admin');
                } catch (\Exception $e) {
                    $this->logger->error('Error creating topic: ' . $e->getMessage());
                    $this->addFlash('error', 'An error occurred while creating the topic.');
                }
            } else {
                foreach ($errors as $message) {
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

        $topics = array_map(function (Questions $question) use ($entityManager, $sentimentMap) {
            $user = $question->getUtilisateurId();
            $game = $question->getGameId();

            $updateForm = $this->createForm(TopicFormType::class, $question, [
                'action' => $this->generateUrl('admin_forum_update_topic', ['id' => $question->getQuestionId()]),
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
                'startedBy' => $user ? $user->getNickname() : 'Unknown',
                'startedById' => $user ? $user->getId() : null,
                'startedOn' => $question->getCreatedAt() ? $question->getCreatedAt()->format('F j, Y') : 'Not set',
                'postCount' => $question->getCommentaires()->count(),
                'lastActivityUser' => $user ? $user->getNickname() : 'Unknown',
                'lastActivityAvatar' => $user && $user->getPhoto() ? $user->getPhoto() : 'avatar-1.jpg',
                'lastActivityDate' => $question->getCreatedAt() ? $question->getCreatedAt()->format('F j, Y') : 'Not set',
                'gameImage' => $game && $game->getImagePath() ? $game->getImagePath() : null,
                'updateForm' => $updateForm->createView(),
                'reactionCounts' => $reactionCounts,
                'sentiment' => $sentiment,
            ];
        }, $questions);

        $trendingPosts = $redditService->fetchTopGamingPosts(5);
        $totalPages = ceil($totalQuestions / $limit);

        return $this->render('admin_forum/index.html.twig', [
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

    #[Route('/admin/forum/topic/delete/{id}', name: 'admin_forum_delete_topic', methods: ['GET'])]
    public function deleteTopic(int $id, QuestionsRepository $questionsRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        // Check if user has ROLE_ADMIN using getRoles()
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('error', 'Access denied. Admin privileges required.');
            return $this->redirectToRoute('forum_admin');
        }

        $question = $questionsRepository->find($id);
        if (!$question) {
            $this->addFlash('error', 'Topic not found.');
            return $this->redirectToRoute('forum_admin');
        }

        try {
            if ($question->getMediaPath()) {
                $mediaPath = $this->getParameter('uploads_directory') . '\\' . $question->getMediaPath();
                if (file_exists($mediaPath)) {
                    unlink($mediaPath);
                }
            }

            $entityManager->remove($question);
            $entityManager->flush();
            $this->addFlash('success', 'Topic deleted successfully!');
        } catch (\Exception $e) {
            $this->logger->error('Error deleting topic: ' . $e->getMessage());
            $this->addFlash('error', 'An error occurred while deleting the topic.');
        }

        return $this->redirectToRoute('forum_admin');
    }

    #[Route('/admin/forum/topic/update/{id}', name: 'admin_forum_update_topic', methods: ['POST'])]
    public function updateTopic(int $id, Request $request, QuestionsRepository $questionsRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        // Check if user has ROLE_ADMIN using getRoles()
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('error', 'Access denied. Admin privileges required.');
            return $this->redirectToRoute('forum_admin');
        }

        $question = $questionsRepository->find($id);
        if (!$question) {
            $this->addFlash('error', 'Topic not found.');
            return $this->redirectToRoute('forum_admin');
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

            if (empty($title)) $errors['title'] = 'The topic title cannot be blank.';
            if (empty($content)) $errors['content'] = 'The content cannot be blank.';
            if (!$gameId) $errors['game_id'] = 'Please select a game.';

            if ($mediaFile) {
                if (!$mediaType) {
                    $errors['media_type'] = 'Please select a media type if you are uploading a file.';
                } else {
                    $mimeType = $mediaFile->getMimeType();
                    $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    $allowedVideoTypes = ['video/mp4', 'video/mpeg', 'video/webm', 'video/quicktime', 'video/x-msvideo'];
                    $maxFileSize = 30 * 1024 * 1024;

                    if ($mediaFile->getSize() > $maxFileSize) {
                        $errors['media_file'] = 'The file is too large. Maximum allowed size is 30MB.';
                    }

                    if ($mediaType->value === 'image' && !in_array($mimeType, $allowedImageTypes)) {
                        $errors['media_file'] = 'Selected media type is "image," but the uploaded file is not an image.';
                    } elseif ($mediaType->value === 'video' && !in_array($mimeType, $allowedVideoTypes)) {
                        $errors['media_file'] = 'Selected media type is "video," but the uploaded file is not a video.';
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
                            }
                        }
                        $question->setMediaPath(null);
                        $question->setMediaType(null);
                    }

                    $question->setTitle($title);
                    $question->setContent($content);
                    $question->setGameId($gameId);

                    $entityManager->flush();
                    $this->addFlash('success', 'Topic updated successfully!');
                } catch (\Exception $e) {
                    $this->logger->error('Error updating topic: ' . $e->getMessage());
                    $this->addFlash('error', 'An error occurred while updating the topic.');
                }
            } else {
                foreach ($errors as $message) {
                    $this->addFlash('error', $message);
                }
            }
        }

        return $this->redirectToRoute('forum_admin');
    }
}