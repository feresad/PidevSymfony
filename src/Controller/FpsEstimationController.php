<?php

namespace App\Controller;

use App\Service\GeminiFpsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class FpsEstimationController extends AbstractController
{
    private $geminiFpsService;

    public function __construct(GeminiFpsService $geminiFpsService)
    {
        $this->geminiFpsService = $geminiFpsService;
    }

    #[Route('/api/estimate-fps', name: 'app_estimate_fps', methods: ['POST'])]
    public function estimateFps(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return new JsonResponse(['error' => 'Invalid JSON data received'], 400);
            }

            if (!isset($data['cpu']) || !isset($data['ram']) || !isset($data['gpu']) || !isset($data['game_name'])) {
                return new JsonResponse([
                    'error' => 'Missing required specifications',
                    'required' => ['cpu', 'ram', 'gpu', 'game_name'],
                    'received' => $data
                ], 400);
            }

            try {
                $estimates = $this->geminiFpsService->estimateFps($data);
                return new JsonResponse($estimates);
            } catch (\Exception $e) {
                return new JsonResponse([
                    'error' => 'Failed to estimate FPS',
                    'message' => $e->getMessage()
                ], 500);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
} 