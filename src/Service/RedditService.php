<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RedditService
{
    private $httpClient;
    private $logger;
    private $clientId;
    private $clientSecret;
    private $userAgent;
    private $accessToken = null;
    private $tokenExpiry = 0;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        string $clientId,
        string $clientSecret,
        string $userAgent
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->userAgent = $userAgent;
    }

    private function getAccessToken(): string
    {
        if ($this->accessToken && time() < $this->tokenExpiry) {
            return $this->accessToken;
        }

        try {
            $response = $this->httpClient->request('POST', 'https://www.reddit.com/api/v1/access_token', [
                'auth_basic' => [$this->clientId, $this->clientSecret],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'User-Agent' => $this->userAgent,
                ],
                'body' => [
                    'grant_type' => 'client_credentials',
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Failed to get access token: ' . $response->getContent(false));
            }

            $data = $response->toArray();
            $this->accessToken = $data['access_token'];
            $this->tokenExpiry = time() + $data['expires_in'] - 60; // Subtract 60 seconds for safety

            return $this->accessToken;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get Reddit access token', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function fetchTopGamingPosts(int $limit = 5): array
    {
        try {
            $accessToken = $this->getAccessToken();

            $response = $this->httpClient->request('GET', 'https://oauth.reddit.com/r/gaming/top', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'User-Agent' => $this->userAgent,
                ],
                'query' => [
                    'limit' => $limit,
                    't' => 'week',
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->logger->error('Reddit API error', [
                    'status_code' => $response->getStatusCode(),
                    'response' => $response->getContent(false),
                ]);
                return [['title' => 'API error', 'url' => '#']];
            }

            $data = $response->toArray();
            $posts = $data['data']['children'] ?? [];

            $trendingPosts = array_map(function ($post) {
                $title = $post['data']['title'] ?? 'Untitled';
                $url = $post['data']['url'] ?? '#';
                $score = $post['data']['score'] ?? 0;
                $numComments = $post['data']['num_comments'] ?? 0;
                
                return [
                    'title' => $title,
                    'url' => $url,
                    'score' => $score,
                    'comments' => $numComments
                ];
            }, array_slice($posts, 0, $limit));

            return !empty($trendingPosts) ? $trendingPosts : [['title' => 'No posts found', 'url' => '#']];
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch Reddit posts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [['title' => 'Failed to load posts', 'url' => '#']];
        }
    }
}