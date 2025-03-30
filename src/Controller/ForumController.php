<?php

namespace App\Controller;

use App\Entity\Questions;
use App\Entity\Utilisateur;
use App\Entity\Commentaire;
use App\Form\TopicFormType;
use App\Form\CommentFormType;
use App\Repository\QuestionsRepository;
use App\Repository\UtilisateurRepository;
use App\Service\RedditService; // Add this
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
                        $this->logger->error('Media type mismatch: Selected image, but file is not an image.', [
                            'mime_type' => $mimeType,
                        ]);
                        throw new \Exception('Selected media type is "image," but the uploaded file is not an image.');
                    }

                    if ($mediaType && $mediaType->value === 'video' && !$isVideo) {
                        $this->logger->error('Media type mismatch: Selected video, but file is not a video.', [
                            'mime_type' => $mimeType,
                        ]);
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

                $this->logger->info('Topic saved to database.', [
                    'question_id' => $question->getQuestionId(),
                    'media_path' => $question->getMediaPath(),
                    'media_type' => $question->getMediaType() ? $question->getMediaType()->value : 'none',
                ]);

                $this->addFlash('success', 'Topic created successfully!');
                return $this->redirectToRoute('forum_topics');
            } catch (\Exception $e) {
                $this->logger->error('Error creating topic.', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $this->addFlash('error', 'An error occurred while creating the topic: ' . $e->getMessage());
            }
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $this->logger->warning('Form submission failed validation.', [
                'errors' => $form->getErrors(true, true)->__toString(),
            ]);
            $this->addFlash('error', 'Form validation failed. Please check your inputs.');
        }

        $questions = $questionsRepository->findAll();

        $topics = array_map(function (Questions $question) {
            $user = $question->getUtilisateurId();
            $game = $question->getGameId();

            $updateForm = $this->createForm(TopicFormType::class, $question, [
                'action' => $this->generateUrl('forum_update_topic', ['id' => $question->getQuestionId()]),
            ]);

            $gameName = $game ? $game->getGameName() : 'No game';
            $gameImagePath = $game ? $game->getImagePath() : 'No image path';
            $gameFilePath = $game && $game->getImagePath() ? 'C:\\xampp\\htdocs\\img\\games\\' . $game->getImagePath() : 'No file path';
            $gameFileExists = $game && $game->getImagePath() ? file_exists($gameFilePath) : false;
            $gameFileReadable = $gameFileExists ? (is_readable($gameFilePath) ? 'Yes' : 'No') : 'N/A';

            $topicMediaPath = $question->getMediaPath() ? $question->getMediaPath() : 'No topic media';
            $topicFilePath = $topicMediaPath !== 'No topic media' ? 'C:\\xampp\\htdocs\\img\\games\\' . $topicMediaPath : 'No file path';
            $topicFileExists = $topicMediaPath !== 'No topic media' ? file_exists($topicFilePath) : false;
            $topicFileReadable = $topicFileExists ? (is_readable($topicFilePath) ? 'Yes' : 'No') : 'N/A';

            $this->logger->debug('Topic data prepared for rendering.', [
                'question_id' => $question->getQuestionId(),
                'game' => $gameName,
                'game_image_path' => $gameImagePath,
                'game_file_path' => $gameFilePath,
                'game_file_exists' => $gameFileExists ? 'Yes' : 'No',
                'game_file_readable' => $gameFileReadable,
                'topic_media_path' => $topicMediaPath,
                'topic_file_path' => $topicFilePath,
                'topic_file_exists' => $topicFileExists ? 'Yes' : 'No',
                'topic_file_readable' => $topicFileReadable,
            ]);

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
        QuestionsRepository $questionsRepository
    ): Response {
        $question = $questionsRepository->find($id);
        if (!$question) {
            $this->addFlash('error', 'Topic not found.');
            return $this->redirectToRoute('forum_topics');
        }

        $allComments = $question->getCommentaires()->toArray();
        $topLevelComments = array_filter($allComments, function (Commentaire $comment) {
            return $comment->getParentCommentaireId() === null;
        });

        $comment = new Commentaire();
        $commentForm = $this->createForm(CommentFormType::class, $comment, [
            'action' => $this->generateUrl('comment_create', ['id' => $id]),
        ]);

        $game = $question->getGameId();

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
        ];

        $mapComment = function (Commentaire $comment) use (&$mapComment) {
            $childCommentaires = $comment->getChildCommentaires()->toArray();
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
            ];
        };

        $commentData = array_map($mapComment, $topLevelComments);

        return $this->render('forum/single_topic.html.twig', [
            'question' => $questionData,
            'comments' => $commentData,
            'comment_form' => $commentForm->createView(),
        ]);
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
            $this->logger->error('Error deleting topic.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
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

        if ($updateForm->isSubmitted()) {
            $this->logger->info('Update form submitted for topic.', [
                'topic_id' => $id,
                'form_data' => $request->request->all(),
            ]);

            if ($updateForm->isValid()) {
                try {
                    $mediaFile = $updateForm->get('media_file')->getData();
                    $mediaType = $updateForm->get('media_type')->getData();

                    if ($mediaFile) {
                        $mimeType = $mediaFile->getMimeType();
                        $isImage = in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif']);
                        $isVideo = in_array($mimeType, ['video/mp4', 'video/mpeg', 'video/webm', 'video/quicktime', 'video/x-msvideo']);

                        if ($mediaType && $mediaType->value === 'image' && !$isImage) {
                            $this->logger->error('Media type mismatch: Selected image, but file is not an image.', [
                                'mime_type' => $mimeType,
                            ]);
                            throw new \Exception('Selected media type is "image," but the uploaded file is not an image.');
                        }

                        if ($mediaType && $mediaType->value === 'video' && !$isVideo) {
                            $this->logger->error('Media type mismatch: Selected video, but file is not a video.', [
                                'mime_type' => $mimeType,
                            ]);
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
                        $this->logger->info('Media file updated successfully.', [
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
                                $this->logger->info('Deleted old media file due to media_type set to None.', ['path' => $oldMediaPath]);
                            }
                        }
                        $question->setMediaPath(null);
                        $question->setMediaType(null);
                    }

                    $entityManager->flush();
                    $this->addFlash('success', 'Topic updated successfully!');
                } catch (\Exception $e) {
                    $this->logger->error('Error updating topic.', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $this->addFlash('error', 'An error occurred while updating the topic: ' . $e->getMessage());
                }
            } else {
                $this->logger->warning('Update form submission failed validation.', [
                    'topic_id' => $id,
                    'errors' => $updateForm->getErrors(true, true)->__toString(),
                ]);
                $this->addFlash('error', 'Form validation failed. Please check your inputs.');
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
            'title' => $question->getTitle(),
            'description' => substr(strip_tags($question->getContent()), 0, 200) . (strlen($question->getContent()) > 200 ? '...' : ''),
            'image' => $imageUrl,
        ];

        return new JsonResponse($shareData);
    }
}