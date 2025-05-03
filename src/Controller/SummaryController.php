<?php

namespace App\Controller;

use App\Repository\ReservationRepository;
use App\Repository\Session_gameRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SummaryController extends AbstractController
{
    #[Route('/summary', name: 'app_summary')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(
        ReservationRepository $reservationRepository,
        Session_gameRepository $sessionRepository,
        UtilisateurRepository $utilisateurRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $totalReservations = $entityManager
            ->createQuery('SELECT COUNT(r) FROM App\\Entity\\Reservation r')
            ->getSingleScalarResult();

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

        // Revenus mensuels (12 derniers mois)
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

        // Fetch all sessions with their reservation count
        $sessionsWithReservationCount = $sessionRepository->findAllWithReservationCount();

        return $this->render('summary/index.html.twig', [
            'totalReservations' => $totalReservations,
            'activeCustomers' => $activeCustomers,
            'topCoaches' => $topCoaches,
            'monthLabels' => json_encode($monthLabels),
            'monthlyRevenue' => json_encode($monthlyRevenue),
            'sessionsWithReservationCount' => $sessionsWithReservationCount
        ]);
    }
}
