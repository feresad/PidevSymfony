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
                "You are an expert gaming performance analyst with deep knowledge of hardware benchmarking and game optimization. " .
                "Based on extensive benchmarking data and real-world testing, provide accurate FPS estimates for the following configuration:\n\n" .
                "Game: %s\n" .
                "System Configuration:\n" .
                "- Operating System: %s\n" .
                "- CPU: %s\n" .
                "- RAM: %s GB\n" .
                "- GPU: %s\n" .
                "- Resolution: 1920x1080 (1080p)\n\n" .
                "Consider these factors in your estimation:\n" .
                "1. CPU single-core and multi-core performance\n" .
                "2. GPU memory and architecture capabilities\n" .
                "3. RAM speed and capacity impact\n" .
                "4. Game engine optimization\n" .
                "5. DirectX/Vulkan API overhead\n\n" .
                "Provide realistic FPS ranges for three quality presets at 1080p:\n" .
                "1. Low Settings (Shadows Low, Textures Low, Effects Low)\n" .
                "2. Medium Settings (Balanced preset)\n" .
                "3. High Settings (Maximum quality, with RT if supported)\n\n" .
                "Format your response exactly like this:\n" .
                "Low: [min]-[max] FPS\n" .
                "Medium: [min]-[max] FPS\n" .
                "High: [min]-[max] FPS\n\n" .
                "Ensure the estimates are realistic and account for:\n" .
                "- Frame time consistency\n" .
                "- CPU/GPU bottlenecks\n" .
                "- Game optimization level\n" .
                "- Similar hardware benchmarks\n\n" .
                "Only provide the FPS numbers in the exact format specified. No additional text.",
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
                        'temperature' => 0.3,  // Reduced temperature for more consistent results
                        'topK' => 20,          // Reduced for more focused responses
                        'topP' => 0.85,        // Adjusted for better precision
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

    public function getRecommendedSpecs(string $gameName): array
    {
        try {
            $prompt = sprintf(
                "You are a gaming hardware expert. Based on real benchmarking data and official requirements, " .
                "provide detailed recommended specifications for running '%s' at 1080p 60+ FPS with high settings.\n\n" .
                "Consider:\n" .
                "1. Modern hardware availability\n" .
                "2. Game engine requirements\n" .
                "3. Optimal performance targets\n" .
                "4. Price-to-performance ratio\n\n" .
                "Format your response exactly like this:\n" .
                "{\n" .
                "\"cpu\": \"Specific CPU model recommendation\",\n" .
                "\"gpu\": \"Specific GPU model recommendation\",\n" .
                "\"ram\": \"RAM amount and speed\",\n" .
                "\"storage\": \"Storage recommendation\",\n" .
                "\"os\": \"Minimum OS version\"\n" .
                "}\n\n" .
                "Provide only the JSON response, no additional text.",
                $gameName
            );

            $response = $this->callGeminiApi($prompt);
            return $this->parseSpecsResponse($response);
        } catch (\Exception $e) {
            throw new \Exception('Failed to get recommended specs: ' . $e->getMessage());
        }
    }

    private function parseSpecsResponse(array $response): array
    {
        try {
            if (!isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                throw new \Exception('Invalid response structure from Gemini API');
            }

            $text = $response['candidates'][0]['content']['parts'][0]['text'];
            
            // Remove any potential markdown formatting
            $text = preg_replace('/```json\s*|\s*```/', '', $text);
            
            // Decode JSON response
            $specs = json_decode($text, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response from API');
            }

            // Validate required fields
            $requiredFields = ['cpu', 'gpu', 'ram', 'storage', 'os'];
            foreach ($requiredFields as $field) {
                if (!isset($specs[$field])) {
                    throw new \Exception("Missing required field: $field");
                }
            }

            return [
                'recommended' => [
                    'cpu' => $specs['cpu'],
                    'gpu' => $specs['gpu'],
                    'ram' => $specs['ram'],
                    'storage' => $specs['storage'],
                    'os' => $specs['os']
                ],
                'performance_note' => 'These specifications target 1080p resolution at 60+ FPS with high settings.'
            ];
        } catch (\Exception $e) {
            throw new \Exception('Failed to parse recommended specs: ' . $e->getMessage());
        }
    }
} 