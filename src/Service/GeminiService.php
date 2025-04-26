<?php

namespace App\Service;

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

        $prompt = "Vous êtes un chatbot spécialisé dans le gaming. Répondez uniquement aux questions liées aux jeux vidéo, aux consoles, aux tournois de gaming, aux genres de jeux, aux stratégies, ou à la culture gaming. Si la question n'est pas liée au gaming, répondez : 'Désolé, je ne réponds qu'aux questions liées au gaming.' Voici la question : " . $message;

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ];

        try {
            $response = $this->httpClient->request('POST', $endpoint, [
                'json' => $payload
            ]);

            $data = $response->toArray();

            return $data['candidates'][0]['content']['parts'][0]['text'] ?? "Désolé, je n'ai pas de réponse.";
        } catch (\Exception $e) {
            return "Erreur lors de la communication avec l'API : " . $e->getMessage();
        }
    }
}