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
    public function indexAdmin(
        \App\Repository\ReservationRepository $reservationRepository,
        \App\Repository\Session_gameRepository $sessionRepository,
        \App\Repository\UtilisateurRepository $utilisateurRepository,
        \Doctrine\ORM\EntityManagerInterface $entityManager
    ): Response
    {
        // Get total reservations
        $totalReservations = $entityManager
            ->createQuery('SELECT COUNT(r) FROM App\\Entity\\Reservation r')
            ->getSingleScalarResult();

        // Get most active customers
        $activeCustomers = $entityManager
            ->createQuery(
                'SELECT u.nickname, COUNT(r) as reservationCount 
                 FROM App\\Entity\\Utilisateur u 
                 JOIN App\\Entity\\Reservation r WITH r.client = u 
                 GROUP BY u.id 
                 ORDER BY reservationCount DESC'
            )
            ->setMaxResults(5)
            ->getResult();

        // Get top coaches
        $topCoaches = $entityManager
            ->createQuery(
                'SELECT u.nickname, COUNT(r) as sessionCount 
                 FROM App\\Entity\\Utilisateur u 
                 JOIN App\\Entity\\Session_game s WITH s.coach = u 
                 JOIN App\\Entity\\Reservation r WITH r.session = s 
                 GROUP BY u.id 
                 ORDER BY sessionCount DESC'
            )
            ->setMaxResults(5)
            ->getResult();

        // Monthly revenue (last 12 months)
        $connection = $entityManager->getConnection();
        $stmt = $connection->prepare("
            SELECT MONTH(r.date_reservation) as month,
                   YEAR(r.date_reservation) as year,
                   SUM(s.prix) as revenue
            FROM reservation r
            JOIN session_game s ON r.session_id_id = s.id
            WHERE r.date_reservation >= :startDate
            GROUP BY year, month
            ORDER BY year, month
        ");
        $monthlyResult = $stmt->executeQuery([
            'startDate' => (new \DateTime('-11 months'))->modify('first day of this month')->format('Y-m-d'),
        ])->fetchAllAssociative();

        $monthLabels = [];
        $monthlyRevenue = [];
        $currentDate = new \DateTime();
        $monthlyDataMap = [];

        foreach ($monthlyResult as $row) {
            $key = $row['year'] . '-' . str_pad($row['month'], 2, '0', STR_PAD_LEFT);
            $monthlyDataMap[$key] = $row['revenue'];
        }

        for ($i = 11; $i >= 0; $i--) {
            $date = (clone $currentDate)->modify("-$i months");
            $label = $date->format('F');
            $key = $date->format('Y-m');
            $monthLabels[] = $label;
            $monthlyRevenue[] = isset($monthlyDataMap[$key]) ? (float)$monthlyDataMap[$key] : 0;
        }

        return $this->render('home/indexadmin.html.twig', [
            'controller_name' => 'HomeController',
            'totalReservations' => $totalReservations,
            'activeCustomers' => $activeCustomers,
            'topCoaches' => $topCoaches,
            'monthLabels' => json_encode($monthLabels),
            'monthlyRevenue' => json_encode($monthlyRevenue),
            'image_base_url' => $this->getParameter('image_base_url'),
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
