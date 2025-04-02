<?php

namespace App\Controller;

use App\Service\GeminiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }
    #[Route('/admin', name: 'app_home_admin')]
    public function indexAdmin(): Response
    {
        return $this->render('home/indexadmin.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }
    #[Route('/chatbot', name: 'chatbot', methods: ['POST'])]
    public function chatbot(Request $request, GeminiService $geminiService): Response
    {
        $message = $request->request->get('message');

        if ($message) {
            $response = $geminiService->getResponse($message);
            if ($request->isXmlHttpRequest()) {
                // Retourner uniquement la réponse texte pour AJAX
                return new Response($response);
            }
            // Cas non-AJAX (facultatif)
            return $this->render('evenement/chatbot.html.twig', [
                'message' => $message,
                'response' => $response,
            ]);
        }

        return new Response('Aucun message fourni', 400);
    }
}
