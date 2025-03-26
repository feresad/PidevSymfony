<?php

namespace App\Service;

use OpenAI;

class ChatGPTService
{
    private $client;

    public function __construct(string $apiKey)
    {
        $this->client = OpenAI::client($apiKey);
    }

    public function getResponse(string $message): string
    {
        $response = $this->client->chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'Tu es un assistant utile.'],
                ['role' => 'user', 'content' => $message],
            ],
            'max_tokens' => 150, // Limite de tokens pour la réponse
        ]);

        return $response->choices[0]->message->content;
    }
}