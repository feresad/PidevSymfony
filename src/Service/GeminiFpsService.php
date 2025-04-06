<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiFpsService
{
    private $apiKey;
    private $httpClient;
    private const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

    public function __construct(HttpClientInterface $httpClient, string $apiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
    }

    public function estimateFps(array $specs): array
    {
        try {
            $osInfo = php_uname('s') . ' ' . php_uname('m');
            $prompt = sprintf(
                "You are a gaming performance analysis AI. I need detailed FPS estimates for the following configuration:\n\n" .
                "Game: %s\n" .
                "System Details:\n" .
                "- Operating System: %s\n" .
                "- CPU: %s\n" .
                "- RAM: %s\n" .
                "- GPU: %s\n\n" .
                "Please provide FPS estimates for three quality settings at 1080p resolution:\n" .
                "1. Low Settings\n" .
                "2. Medium Settings\n" .
                "3. High Settings\n\n" .
                "Format your response exactly like this example:\n" .
                "Low: 80-90 FPS\n" .
                "Medium: 60-70 FPS\n" .
                "High: 40-50 FPS\n\n" .
                "Only provide the FPS numbers in this exact format. Do not include any other text or explanations.",
                $specs['game_name'] ?? 'Unknown Game',
                $osInfo,
                $specs['cpu'],
                $specs['ram'],
                $specs['gpu']
            );

            $response = $this->callGeminiApi($prompt);
            return $this->parseResponse($response);
        } catch (\Exception $e) {
            throw new \Exception('FPS Estimation failed: ' . $e->getMessage());
        }
    }

    private function callGeminiApi(string $prompt): array
    {
        try {
            $url = self::API_URL . '?key=' . $this->apiKey;
            
            $response = $this->httpClient->request('POST', $url, [
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => $prompt
                                ]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'topK' => 40,
                        'topP' => 0.95,
                        'maxOutputTokens' => 1024,
                    ]
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                $content = $response->getContent(false);
                throw new \Exception('API Error: ' . $content);
            }

            return $response->toArray();
        } catch (\Exception $e) {
            throw new \Exception('Gemini API call failed: ' . $e->getMessage());
        }
    }

    private function parseResponse(array $response): array
    {
        try {
            if (!isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                throw new \Exception('Invalid response structure from Gemini API');
            }

            $text = $response['candidates'][0]['content']['parts'][0]['text'];
            
            // Extract FPS ranges using regex
            preg_match_all('/(\d+)-(\d+)/', $text, $matches);
            
            if (count($matches[0]) < 3) {
                throw new \Exception('Could not extract valid FPS ranges from response');
            }

            return [
                'low' => [
                    'min' => (int)$matches[1][0],
                    'max' => (int)$matches[2][0]
                ],
                'medium' => [
                    'min' => (int)$matches[1][1],
                    'max' => (int)$matches[2][1]
                ],
                'high' => [
                    'min' => (int)$matches[1][2],
                    'max' => (int)$matches[2][2]
                ]
            ];
        } catch (\Exception $e) {
            throw new \Exception('Failed to parse FPS data: ' . $e->getMessage());
        }
    }
} 