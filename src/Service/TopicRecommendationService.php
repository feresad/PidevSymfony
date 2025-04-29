<?php

namespace App\Service;

use Phpml\FeatureExtraction\TfIdfTransformer;
use Phpml\Clustering\KMeans;
use App\Entity\Questions;
use App\Entity\QuestionVotes;
use App\Entity\Commentaire;
use App\Entity\CommentaireReactions;
use App\Entity\QuestionReactions;
use App\Entity\Utilisateur;
use App\Repository\QuestionsRepository;
use App\Repository\CommentaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class TopicRecommendationService
{
    private $questionsRepository;
    private $commentaireRepository;
    private $entityManager;
    private $logger;

    public function __construct(
        QuestionsRepository $questionsRepository,
        CommentaireRepository $commentaireRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->questionsRepository = $questionsRepository;
        $this->commentaireRepository = $commentaireRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function recommend(Utilisateur $user, int $limit = 5, array $sentimentMap = []): array
    {
        $this->logger->info('Starting topic recommendation for user', ['user_id' => $user->getId()]);

        // Use the provided sentimentMap or a default one
        $sentimentMap = !empty($sentimentMap) ? $sentimentMap : [
            'positive' => ['👍', '😊', '😂', '❤️', '🎉', '😍', '👏', '🌟', '😎', '💪'],
            'negative' => ['👎', '😢', '😡', '💔', '😤', '😞', '🤬', '😣', '💢', '😠'],
            'neutral' => ['🤔', '😐', '🙂', '👀', '🤷', '😶', '🤝', '🙄', '😴', '🤓']
        ];

        // Step 1: Fetch all topics
        $allQuestions = $this->questionsRepository->findAll();
        $this->logger->info('Total questions fetched', ['count' => count($allQuestions)]);

        if (count($allQuestions) === 0) {
            $this->logger->warning('No questions available for recommendation');
            return [];
        }

        // Step 2: Prepare data for clustering (combine title and content)
        $texts = [];
        $questionMap = [];
        foreach ($allQuestions as $index => $question) {
            $title = $question->getTitle() ?? '';
            $content = $question->getContent() ?? '';
            $text = trim($title . ' ' . $content);
            if (empty($text)) {
                $this->logger->debug('Skipping question due to empty text', ['question_id' => $question->getQuestionId()]);
                continue;
            }
            $texts[] = $text;
            $questionMap[$index] = $question;
        }

        $this->logger->info('Questions with valid text', ['count' => count($texts)]);

        if (count($texts) === 0) {
            $this->logger->warning('No questions with valid text for clustering');
            return $this->fallbackRecommendations($user, $questionMap, [], $limit);
        }

        // Step 3: Tokenize and create word frequency arrays
        $samples = [];
        foreach ($texts as $text) {
            $words = array_count_values(str_word_count(strtolower($text), 1));
            if (empty($words)) {
                continue;
            }
            $samples[] = $words;
        }

        $this->logger->info('Samples prepared for TF-IDF', ['count' => count($samples)]);

        if (count($samples) < 2) {
            $this->logger->warning('Not enough samples for clustering', ['sample_count' => count($samples)]);
            return $this->fallbackRecommendations($user, $questionMap, [], $limit);
        }

        // Step 4: Normalize samples to have the same keys
        $vocabulary = [];
        foreach ($samples as $sample) {
            $vocabulary = array_merge($vocabulary, array_keys($sample));
        }
        $vocabulary = array_unique($vocabulary);

        $normalizedSamples = [];
        foreach ($samples as $sample) {
            $normalizedSample = array_fill_keys($vocabulary, 0);
            foreach ($sample as $word => $count) {
                $normalizedSample[$word] = $count;
            }
            $normalizedSamples[] = $normalizedSample;
        }

        // Step 5: Apply TF-IDF transformation
        $transformer = new TfIdfTransformer($normalizedSamples);
        $vectors = $transformer->transform($normalizedSamples);

        if (!is_array($vectors) || empty($vectors)) {
            $this->logger->error('TF-IDF transformation failed');
            return $this->fallbackRecommendations($user, $questionMap, [], $limit);
        }

        // Check for identical vectors
        $uniqueVectors = array_unique(array_map('serialize', $vectors));
        if (count($uniqueVectors) < 2) {
            $this->logger->warning('Vectors are identical, skipping clustering');
            return $this->fallbackRecommendations($user, $questionMap, [], $limit);
        }

        // Step 6: Cluster topics using K-Means
        $numClusters = min(5, count($normalizedSamples));
        $numClusters = max(2, $numClusters);
        $kmeans = new KMeans($numClusters);
        try {
            $clusters = $kmeans->cluster($vectors);
            if (!is_array($clusters) || empty($clusters)) {
                throw new \Exception('Clustering returned invalid result');
            }
            $this->logger->info('Clustering successful', ['clusters' => array_map('count', $clusters)]);
        } catch (\Exception $e) {
            $this->logger->error('Clustering failed', ['error' => $e->getMessage()]);
            return $this->fallbackRecommendations($user, $questionMap, [], $limit);
        }

        // Step 7: Identify topics the user has interacted with positively
        $interactedTopicIndices = $this->getUserInteractedTopicIndices($user, $questionMap, $sentimentMap);
        $this->logger->info('User interacted topics', ['interacted_count' => count($interactedTopicIndices)]);

        if (empty($interactedTopicIndices)) {
            $this->logger->info('User has no interactions, returning random topics');
            return $this->fallbackRecommendations($user, $questionMap, [], $limit);
        }

        // Step 8: Find clusters of the user's interacted topics
        $userClusters = [];
        foreach ($interactedTopicIndices as $index) {
            foreach ($clusters as $clusterId => $indices) {
                if (in_array($index, $indices)) {
                    $userClusters[$clusterId] = true;
                    break;
                }
            }
        }

        $this->logger->info('User clusters identified', ['cluster_count' => count($userClusters)]);

        // Step 9: Recommend topics from the same clusters
        $recommendations = [];
        $usedQuestionIds = [];
        foreach (array_keys($userClusters) as $clusterId) {
            foreach ($clusters[$clusterId] as $index) {
                $question = $questionMap[$index];
                $questionId = $question->getQuestionId();
                if (!in_array($questionId, $usedQuestionIds) && !in_array($index, $interactedTopicIndices)) {
                    $recommendations[] = $question;
                    $usedQuestionIds[] = $questionId;
                    if (count($recommendations) >= $limit) {
                        break 2;
                    }
                }
            }
        }

        $this->logger->info('Recommendations from clusters', ['count' => count($recommendations)]);

        // Step 10: If we don't have enough recommendations, fill with other topics
        if (count($recommendations) < $limit) {
            $this->logger->info('Not enough cluster-based recommendations, adding more topics');
            $remaining = $limit - count($recommendations);
            $additionalRecommendations = $this->fallbackRecommendations($user, $questionMap, $usedQuestionIds, $remaining);
            $recommendations = array_merge($recommendations, $additionalRecommendations);
        }

        $this->logger->info('Final recommendations', ['count' => count($recommendations)]);
        return $recommendations;
    }

    private function fallbackRecommendations(Utilisateur $user, array $questionMap, array $usedQuestionIds, int $limit): array
    {
        $this->logger->info('Falling back to basic recommendations', ['used_question_ids' => $usedQuestionIds, 'limit' => $limit]);
        $recommendations = [];
        $interactedTopicIndices = $this->getUserInteractedTopicIndices($user, $questionMap, [
            'positive' => ['👍', '😊', '😂', '❤️', '🎉', '😍', '👏', '🌟', '😎', '💪'],
            'negative' => ['👎', '😢', '😡', '💔', '😤', '😞', '🤬', '😣', '💢', '😠'],
            'neutral' => ['🤔', '😐', '🙂', '👀', '🤷', '😶', '🤝', '🙄', '😴', '🤓']
        ]);

        // Sort questions by votes and recency as a fallback
        $sortedQuestions = $questionMap;
        usort($sortedQuestions, function($a, $b) {
            $voteDiff = $b->getVotes() - $a->getVotes();
            if ($voteDiff !== 0) {
                return $voteDiff;
            }
            return $b->getCreatedAt() <=> $a->getCreatedAt();
        });

        foreach ($sortedQuestions as $index => $question) {
            $questionId = $question->getQuestionId();
            if (!in_array($questionId, $usedQuestionIds) && !in_array($index, $interactedTopicIndices)) {
                $recommendations[] = $question;
                $usedQuestionIds[] = $questionId;
                if (count($recommendations) >= $limit) {
                    break;
                }
            }
        }

        $this->logger->info('Fallback recommendations generated', ['count' => count($recommendations)]);
        return $recommendations;
    }

    private function getUserInteractedTopicIndices(Utilisateur $user, array $questionMap, array $sentimentMap): array
    {
        $interactedIndices = [];

        // Topics created by the user
        foreach ($questionMap as $index => $question) {
            if ($question->getUtilisateurId()->getId() === $user->getId()) {
                $interactedIndices[] = $index;
            }
        }

        // Topics the user upvoted
        $voteRepository = $this->entityManager->getRepository(QuestionVotes::class);
        $votes = $voteRepository->findBy(['user_id' => $user, 'vote_type' => \App\Enum\VoteType::UP]);
        foreach ($votes as $vote) {
            $question = $vote->getQuestionId();
            foreach ($questionMap as $index => $q) {
                if ($q->getQuestionId() === $question->getQuestionId()) {
                    $interactedIndices[] = $index;
                    break;
                }
            }
        }

        // Topics where the user added positive comments
        $comments = $this->commentaireRepository->findBy(['utilisateur_id' => $user]);
        foreach ($comments as $comment) {
            $question = $comment->getQuestionId();
            foreach ($questionMap as $index => $q) {
                if ($q->getQuestionId() === $question->getQuestionId()) {
                    $interactedIndices[] = $index;
                    break;
                }
            }
        }

        // Topics where the user added positive emojis
        $questionReactionRepository = $this->entityManager->getRepository(QuestionReactions::class);
        $questionReactions = $questionReactionRepository->findBy(['user_id' => $user]);
        foreach ($questionReactions as $reaction) {
            if (in_array($reaction->getEmoji(), $sentimentMap['positive'])) {
                $question = $reaction->getQuestionId();
                foreach ($questionMap as $index => $q) {
                    if ($q->getQuestionId() === $question->getQuestionId()) {
                        $interactedIndices[] = $index;
                        break;
                    }
                }
            }
        }

        // Comments where the user added positive emojis
        $commentReactionRepository = $this->entityManager->getRepository(CommentaireReactions::class);
        $commentReactions = $commentReactionRepository->findBy(['user_id' => $user]);
        foreach ($commentReactions as $reaction) {
            if (in_array($reaction->getEmoji(), $sentimentMap['positive'])) {
                $comment = $reaction->getCommentaireId();
                $question = $comment->getQuestionId();
                foreach ($questionMap as $index => $q) {
                    if ($q->getQuestionId() === $question->getQuestionId()) {
                        $interactedIndices[] = $index;
                        break;
                    }
                }
            }
        }

        return array_unique($interactedIndices);
    }
}