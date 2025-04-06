<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use App\Repository\Session_gameRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReservationController extends AbstractController
{

    #[Route('/reservations', name: 'reservation_list')]
    public function index(ReservationRepository $reservationRepository): Response
    {
        $reservations = $reservationRepository->findAll();
        return $this->render('reservation/index.html.twig', ['reservations' => $reservations]);
    }

    #[Route('/reservation/add/{sessionId}', name: 'reservation_add', methods: ['GET', 'POST'])]
    public function add(
        Request $request,
        int $sessionId,
        Session_gameRepository $sessionRepository,
        ReservationRepository $reservationRepository
    ): Response {
        $session = $sessionRepository->find($sessionId);
        if (!$session) {
            throw $this->createNotFoundException('Session non trouvée');
        }
        
        $reservation = new Reservation();
        if ($request->isMethod('POST')) {
            $dateReservation = $request->request->get('date_reservation');
            
            if (!$dateReservation) {
                $this->addFlash('error', 'Veuillez sélectionner une date de réservation');
                return $this->render('reservation/add.html.twig', [
                    'session' => $session,
                    'error' => 'La date est requise',
                    'image_base_url' => $this->getParameter('image_base_url'),
                ]);
            }

            $reservationDate = new \DateTime($dateReservation);
            $today = new \DateTime();
            
            if ($reservationDate < $today) {
                $this->addFlash('error', 'La date de réservation ne peut pas être dans le passé');
                return $this->render('reservation/add.html.twig', [
                    'session' => $session,
                    'error' => 'Date invalide',
                    'image_base_url' => $this->getParameter('image_base_url'),
                ]);
            }

            if ($reservationRepository->findBySessionAndDate($sessionId, $reservationDate)) {
                $this->addFlash('error', 'Cette date est déjà réservée pour cette session');
                return $this->render('reservation/add.html.twig', [
                    'session' => $session,
                    'error' => 'Date non disponible',
                    'image_base_url' => $this->getParameter('image_base_url'),
                ]);
            }

            $reservation->setSession($session);
            $reservation->setClientId(0); // Static client ID for anonymous reservations
            $reservation->setDateReservation($reservationDate);

            $reservationRepository->add($reservation);
            $this->addFlash('success', 'Votre réservation a été effectuée avec succès');
            return $this->redirectToRoute('reservation_list');
        }

        return $this->render('reservation/add.html.twig', ['session' => $session,
    'image_base_url' => $this->getParameter('image_base_url'),
]);
    }
}