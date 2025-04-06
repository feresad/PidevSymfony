<?php

namespace App\Service;

use OpenAI;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiService
{
    private $httpClient;
    private $apiKey;

    public function __construct(HttpClientInterface $httpClient, string $apiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
    }

    public function getResponse(string $message): string
    {
        $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $this->apiKey;

        $payload = [
            'contents' => [
                ['parts' => [['text' => $message]]]
            ]
        ];

        $response = $this->httpClient->request('POST', $endpoint, [
            'json' => $payload
        ]);

        $data = $response->toArray();

        return $data['candidates'][0]['content']['parts'][0]['text'] ?? "Désolé, je n'ai pas de réponse.";
    }
}