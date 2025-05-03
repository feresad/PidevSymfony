<?php

namespace App\Controller;

use App\Repository\EvenementRepository;
use App\Repository\StockRepository;
use App\Service\GeminiService;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(EvenementRepository $evenementRepo, StockRepository $stockRepo): Response
    {
        // Get recent events
        $recentEvenements = $evenementRepo->findRecentEvents(new \DateTime(), 3);
        
        // Get featured products sorted by price (highest to lowest)
        $featuredProducts = $stockRepo->findFeaturedProductsByPrice(6);
        
        return $this->render('home/index.html.twig', [
            'recentEvenements' => $recentEvenements,
            'featuredProducts' => $featuredProducts,
            'image_base_url' => 'http://localhost/img'
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
