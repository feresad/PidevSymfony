<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RedditService
{
    private $httpClient;
    private $logger;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    public function fetchTopGamingPosts(int $limit = 5): array
    {
        try {
            $response = $this->httpClient->request('GET', 'https://www.reddit.com/r/gaming/top.json', [
                'query' => [
                    'limit' => $limit,
                    't' => 'week', // Top posts from the past week
                ],
                'headers' => [
                    'User-Agent' => 'Symfony-GoodGames-App/1.0',
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->logger->error('Reddit API error', [
                    'status_code' => $response->getStatusCode(),
                ]);
                return [['title' => 'API error', 'url' => '#']];
            }

            $data = $response->toArray();
            $posts = $data['data']['children'] ?? [];

            $trendingPosts = array_map(function ($post) {
                $title = $post['data']['title'] ?? 'Untitled';
                $url = $post['data']['url'] ?? '#';
                return ['title' => $title, 'url' => $url];
            }, array_slice($posts, 0, $limit));

            return !empty($trendingPosts) ? $trendingPosts : [['title' => 'No posts found', 'url' => '#']];
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch Reddit posts', [
                'error' => $e->getMessage(),
            ]);
            return [['title' => 'Failed to load posts', 'url' => '#']];
        }
    }
}