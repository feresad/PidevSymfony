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
        // Get total number of reservations
        $totalReservations = $entityManager
            ->createQuery('SELECT COUNT(r) FROM App\\Entity\\Reservation r')
            ->getSingleScalarResult();

        // Get most active customers (with most reservations)
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

        // Get coaches with most sessions reserved
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

        // Get weekly revenue data for the last 8 weeks using native SQL
        $connection = $entityManager->getConnection();

        $sql = "
            SELECT WEEK(r.date_reservation) as week, 
                   YEAR(r.date_reservation) as year, 
                   SUM(s.prix) as revenue
            FROM reservation r
            JOIN session_game s ON r.session_id_id = s.id
            WHERE r.date_reservation >= :startDate
            GROUP BY week, year
            ORDER BY year DESC, week DESC
            LIMIT 8
        ";

        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery([
            'startDate' => (new \DateTime('-8 weeks'))->format('Y-m-d H:i:s'),
        ]);

        $weeklyRevenue = $result->fetchAllAssociative();

        // Format weekly revenue for the chart
        $labels = [];
        $revenueData = [];
        foreach (array_reverse($weeklyRevenue) as $week) {
            $labels[] = "Week {$week['week']}";
            $revenueData[] = $week['revenue'];
        }

        return $this->render('summary/index.html.twig', [
            'totalReservations' => $totalReservations,
            'activeCustomers' => $activeCustomers,
            'topCoaches' => $topCoaches,
            'weekLabels' => json_encode($labels),
            'weeklyRevenue' => json_encode($revenueData),
        ]);
    }
}
