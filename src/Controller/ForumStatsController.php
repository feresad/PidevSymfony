<?php

namespace App\Controller;

use App\Repository\QuestionsRepository;
use App\Repository\CommentaireRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\QuestionReactionsRepository;
use App\Repository\CommentaireReactionsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

class ForumStatsController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/admin/forum/stats', name: 'forum_stats_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->render('forum_stats.html.twig', [
            'image_base_url' => $this->getParameter('image_base_url'),
        ]);
    }

    #[Route('/api/forum/stats', name: 'api_forum_stats', methods: ['GET'])]
    public function getStats(
        Request $request,
        QuestionsRepository $questionsRepository,
        CommentaireRepository $commentaireRepository,
        UtilisateurRepository $utilisateurRepository,
        QuestionReactionsRepository $questionReactionsRepository,
        CommentaireReactionsRepository $commentaireReactionsRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        try {
            $timeRange = $request->query->getInt('timeRange', 7);
            if ($timeRange <= 0) {
                throw new \InvalidArgumentException('Time range must be a positive integer');
            }
            $dateThreshold = new \DateTime();
            $dateThreshold->modify("-$timeRange days");

            // Total Topics
            $totalTopicsQuery = $questionsRepository->createQueryBuilder('q')
                ->select('COUNT(q.question_id)')
                ->where('q.created_at >= :threshold')
                ->setParameter('threshold', $dateThreshold)
                ->getQuery();
            $totalTopics = $totalTopicsQuery->getSingleScalarResult();

            // Total Comments
            $totalCommentsQuery = $commentaireRepository->createQueryBuilder('c')
                ->select('COUNT(c.commentaire_id)')
                ->where('c.creation_at >= :threshold')
                ->setParameter('threshold', $dateThreshold)
                ->getQuery();
            $totalComments = $totalCommentsQuery->getSingleScalarResult();

            // Active Users (users who posted topics or comments in the time range)
            $activeUsersQuery = $entityManager->createQueryBuilder()
                ->select('COUNT(DISTINCT u.id)')
                ->from('App\Entity\Utilisateur', 'u')
                ->leftJoin('App\Entity\Questions', 'q', 'WITH', 'q.utilisateur_id = u')
                ->leftJoin('App\Entity\Commentaire', 'c', 'WITH', 'c.utilisateur_id = u')
                ->where('q.created_at >= :threshold OR c.creation_at >= :threshold')
                ->setParameter('threshold', $dateThreshold)
                ->getQuery();
            $activeUsers = $activeUsersQuery->getSingleScalarResult();

            // Total Reactions
            $totalQuestionReactionsQuery = $questionReactionsRepository->createQueryBuilder('qr')
                ->select('COUNT(qr.reaction_id)')
                ->where('qr.created_at >= :threshold')
                ->setParameter('threshold', $dateThreshold)
                ->getQuery();
            $totalQuestionReactions = $totalQuestionReactionsQuery->getSingleScalarResult();

            $totalCommentReactionsQuery = $commentaireReactionsRepository->createQueryBuilder('cr')
                ->select('COUNT(1)')
                ->getQuery();
            $totalCommentReactions = $totalCommentReactionsQuery->getSingleScalarResult();

            $totalReactions = $totalQuestionReactions + $totalCommentReactions;

            // Engagement Rate: (Total Reactions + Total Comments) / Total Topics
            $engagementRate = $totalTopics > 0 ? round(($totalReactions + $totalComments) / $totalTopics, 2) : 0;

            // Topics Trend
            $topicsTrend = [];
            $labels = [];
            for ($i = $timeRange - 1; $i >= 0; $i--) {
                $date = new \DateTime();
                $date->modify("-$i days");
                $labels[] = $date->format('Y-m-d');
                $count = $questionsRepository->createQueryBuilder('q')
                    ->select('COUNT(q.question_id)')
                    ->where('q.created_at >= :start')
                    ->andWhere('q.created_at < :end')
                    ->setParameter('start', $date->format('Y-m-d 00:00:00'))
                    ->setParameter('end', $date->format('Y-m-d 23:59:59'))
                    ->getQuery()
                    ->getSingleScalarResult();
                $topicsTrend[] = $count;
            }

            // Comments Trend
            $commentsTrend = [];
            for ($i = $timeRange - 1; $i >= 0; $i--) {
                $date = new \DateTime();
                $date->modify("-$i days");
                $count = $commentaireRepository->createQueryBuilder('c')
                    ->select('COUNT(c.commentaire_id)')
                    ->where('c.creation_at >= :start')
                    ->andWhere('c.creation_at < :end')
                    ->setParameter('start', $date->format('Y-m-d 00:00:00'))
                    ->setParameter('end', $date->format('Y-m-d 23:59:59'))
                    ->getQuery()
                    ->getSingleScalarResult();
                $commentsTrend[] = $count;
            }

            // Reactions Distribution
            $reactionsDistribution = [
                'positive' => 0,
                'negative' => 0,
                'neutral' => 0
            ];
            $sentimentMap = [
                'positive' => ['👍', '😊', '😂', '❤️', '🎉', '😍', '👏', '🌟', '😎', '💪', '😀', '😅', '😄', '😆', '😁'],
                'negative' => ['👎', '😢', '😡', '💔', '😤', '😞', '🤬', '😣', '💢', '😠'],
                'neutral' => ['🤔', '😐', '🙂', '👀', '🤷', '😶', '🤝', '🙄', '😴', '🤓']
            ];

            $questionReactionsQuery = $questionReactionsRepository->createQueryBuilder('qr')
                ->select('qr.emoji, COUNT(qr.reaction_id) as count')
                ->where('qr.created_at >= :threshold')
                ->setParameter('threshold', $dateThreshold)
                ->groupBy('qr.emoji')
                ->getQuery();
            $questionReactions = $questionReactionsQuery->getResult();

            $commentReactionsQuery = $commentaireReactionsRepository->createQueryBuilder('cr')
                ->select('cr.emoji, COUNT(1) as count')
                ->groupBy('cr.emoji')
                ->getQuery();
            $commentReactions = $commentReactionsQuery->getResult();

            foreach ($questionReactions as $reaction) {
                $emoji = $reaction['emoji'];
                $count = $reaction['count'];
                if (in_array($emoji, $sentimentMap['positive'])) {
                    $reactionsDistribution['positive'] += $count;
                } elseif (in_array($emoji, $sentimentMap['negative'])) {
                    $reactionsDistribution['negative'] += $count;
                } elseif (in_array($emoji, $sentimentMap['neutral'])) {
                    $reactionsDistribution['neutral'] += $count;
                }
            }

            foreach ($commentReactions as $reaction) {
                $emoji = $reaction['emoji'];
                $count = $reaction['count'];
                if (in_array($emoji, $sentimentMap['positive'])) {
                    $reactionsDistribution['positive'] += $count;
                } elseif (in_array($emoji, $sentimentMap['negative'])) {
                    $reactionsDistribution['negative'] += $count;
                } elseif (in_array($emoji, $sentimentMap['neutral'])) {
                    $reactionsDistribution['neutral'] += $count;
                }
            }

            // Reaction Sentiment Trend
            $reactionSentimentTrend = [
                'positive' => [],
                'negative' => [],
                'neutral' => [],
                'labels' => $labels
            ];
            for ($i = $timeRange - 1; $i >= 0; $i--) {
                $date = new \DateTime();
                $date->modify("-$i days");
                $start = $date->format('Y-m-d 00:00:00');
                $end = $date->format('Y-m-d 23:59:59');

                $dailyQuestionReactions = $questionReactionsRepository->createQueryBuilder('qr')
                    ->select('qr.emoji, COUNT(qr.reaction_id) as count')
                    ->where('qr.created_at >= :start')
                    ->andWhere('qr.created_at < :end')
                    ->setParameter('start', $start)
                    ->setParameter('end', $end)
                    ->groupBy('qr.emoji')
                    ->getQuery()
                    ->getResult();

                $dailyPositive = 0;
                $dailyNegative = 0;
                $dailyNeutral = 0;

                foreach ($dailyQuestionReactions as $reaction) {
                    $emoji = $reaction['emoji'];
                    $count = $reaction['count'];
                    if (in_array($emoji, $sentimentMap['positive'])) {
                        $dailyPositive += $count;
                    } elseif (in_array($emoji, $sentimentMap['negative'])) {
                        $dailyNegative += $count;
                    } elseif (in_array($emoji, $sentimentMap['neutral'])) {
                        $dailyNeutral += $count;
                    }
                }

                $reactionSentimentTrend['positive'][] = $dailyPositive;
                $reactionSentimentTrend['negative'][] = $dailyNegative;
                $reactionSentimentTrend['neutral'][] = $dailyNeutral;
            }

            // Top Active Users
            $topActiveUsersQuery = $entityManager->createQueryBuilder()
                ->select('u.id, u.nickname, COUNT(q.question_id) + COUNT(c.commentaire_id) as activity_count')
                ->from('App\Entity\Utilisateur', 'u')
                ->leftJoin('App\Entity\Questions', 'q', 'WITH', 'q.utilisateur_id = u')
                ->leftJoin('App\Entity\Commentaire', 'c', 'WITH', 'c.utilisateur_id = u')
                ->where('q.created_at >= :threshold OR c.creation_at >= :threshold')
                ->setParameter('threshold', $dateThreshold)
                ->groupBy('u.id, u.nickname')
                ->orderBy('activity_count', 'DESC')
                ->setMaxResults(5)
                ->getQuery();
            $topActiveUsers = $topActiveUsersQuery->getResult();

            // Most Reacted Topics
            $mostReactedTopicsQuery = $questionsRepository->createQueryBuilder('q')
                ->select('q.question_id, q.title, COUNT(qr.reaction_id) as reaction_count')
                ->leftJoin('App\Entity\QuestionReactions', 'qr', 'WITH', 'qr.question_id = q')
                ->where('q.created_at >= :threshold')
                ->andWhere('qr.created_at >= :threshold')
                ->setParameter('threshold', $dateThreshold)
                ->groupBy('q.question_id, q.title')
                ->orderBy('reaction_count', 'DESC')
                ->setMaxResults(3)
                ->getQuery();
            $mostReactedTopics = $mostReactedTopicsQuery->getResult();

            $mostReactedTopicsDetails = [];
            foreach ($mostReactedTopics as $topic) {
                $reactions = $questionReactionsRepository->createQueryBuilder('qr')
                    ->select('qr.emoji, COUNT(qr.reaction_id) as count')
                    ->where('qr.question_id = :questionId')
                    ->andWhere('qr.created_at >= :threshold')
                    ->setParameter('questionId', $topic['question_id'])
                    ->setParameter('threshold', $dateThreshold)
                    ->groupBy('qr.emoji')
                    ->getQuery()
                    ->getResult();

                $topicReactions = ['positive' => 0, 'negative' => 0, 'neutral' => 0];
                foreach ($reactions as $reaction) {
                    $emoji = $reaction['emoji'];
                    $count = $reaction['count'];
                    if (in_array($emoji, $sentimentMap['positive'])) {
                        $topicReactions['positive'] += $count;
                    } elseif (in_array($emoji, $sentimentMap['negative'])) {
                        $topicReactions['negative'] += $count;
                    } elseif (in_array($emoji, $sentimentMap['neutral'])) {
                        $topicReactions['neutral'] += $count;
                    }
                }

                $mostReactedTopicsDetails[] = [
                    'title' => $topic['title'],
                    'reaction_count' => $topic['reaction_count'],
                    'reactions' => $topicReactions
                ];
            }

            return new JsonResponse([
                'success' => true,
                'stats' => [
                    'totalTopics' => $totalTopics,
                    'totalComments' => $totalComments,
                    'activeUsers' => $activeUsers,
                    'totalReactions' => $totalReactions,
                    'engagementRate' => $engagementRate,
                    'topicsTrend' => [
                        'labels' => $labels,
                        'values' => $topicsTrend
                    ],
                    'commentsTrend' => [
                        'labels' => $labels,
                        'values' => $commentsTrend
                    ],
                    'reactionsDistribution' => $reactionsDistribution,
                    'reactionSentimentTrend' => $reactionSentimentTrend,
                    'topActiveUsers' => $topActiveUsers,
                    'mostReactedTopics' => $mostReactedTopicsDetails
                ]
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching forum stats', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return new JsonResponse([
                'success' => false,
                'message' => 'Error fetching stats: ' . $e->getMessage()
            ], 500);
        }
    }
}