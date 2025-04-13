<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Utilisateur;
use App\Repository\ReservationRepository;
use App\Repository\Session_gameRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\EmailService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Email;

class ReservationController extends AbstractController
{
    #[Route('/coach/reservation/{id}/send-meet-link', name: 'send_meet_link', methods: ['POST'])]
    public function sendMeetLink(Reservation $reservation, EmailService $emailService, LoggerInterface $logger): Response
    {
        $logger->debug('Appel de send_meet_link pour la réservation ID: ' . $reservation->getId());

        $user = $this->getUser();
        if (!$user || !in_array('ROLE_COACH', $user->getRoles())) {
            $logger->warning('Accès non autorisé à send_meet_link par un utilisateur non-coach.');
            return $this->redirectToRoute('app_login_page');
        }

        try {
            $client = $reservation->getClient();
            if (!$client) {
                throw new \Exception('Aucun client associé à cette réservation.');
            }

            $logger->info('Envoi du lien Google Meet à : ' . $client->getEmail());

            $emailService->sendMeetingLink(
                $client->getEmail(),
                $client->getNom() . ' ' . $client->getPrenom(),
                $reservation->getSession()->getGame(),
                $reservation->getDateReservation(),
                $reservation->getSession()->getDureeSession()
            );

            $this->addFlash('success', 'Le lien Google Meet a été envoyé avec succès');
        } catch (\Exception $e) {
            $logger->error('Erreur lors de l\'envoi du lien Google Meet : ' . $e->getMessage(), [
                'reservation_id' => $reservation->getId(),
                'exception' => $e,
            ]);
            $this->addFlash('error', 'Une erreur est survenue : ' . $e->getMessage());
        }

        return $this->redirectToRoute('coach_reservations');
    }

    #[Route('/coach/reservations', name: 'coach_reservations')]
    public function coachView(ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_COACH', $user->getRoles())) {
            return $this->redirectToRoute('app_login_page');
        }

        $reservations = $reservationRepository->findAll();

        return $this->render('reservation/coach_view.html.twig', [
            'reservations' => $reservations
        ]);
    }

    #[Route('/reservations', name: 'reservation_list')]
    public function index(ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login_page');
        }

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservationRepository->findBy(['client' => $user]),
            'image_base_url' => $this->getParameter('image_base_url'),
            'stripe_public_key' => $this->getParameter('stripe.public_key')
        ]);
    }

    #[Route('/reservation/add/{sessionId}', name: 'reservation_add', methods: ['POST', 'GET'])]
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

            $user = $this->getUser();
            if (!$user) {
                return $this->redirectToRoute('app_login_page');
            }

            $reservation->setClient($user);
            $reservation->setDateReservation($reservationDate);

            $reservationRepository->add($reservation);

            $this->addFlash('success', 'Votre réservation a été effectuée avec succès');
            return $this->redirectToRoute('reservation_list');
        }

        return $this->render('reservation/add.html.twig', [
            'session' => $session,
            'image_base_url' => $this->getParameter('image_base_url'),
        ]);
    }

    #[Route('/reservation/cancel/{id}', name: 'reservation_cancel', methods: ['GET'])]
    public function cancelReservation(
        Reservation $reservation,
        ReservationRepository $reservationRepository,
        LoggerInterface $logger,UtilisateurRepository $utilisateurRepo,
        EmailService $emailService
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            $logger->warning('Tentative d\'annulation de réservation sans utilisateur connecté.');
            return $this->redirectToRoute('app_login_page');
        }

        // Vérifier que l'utilisateur est le client associé à la réservation
        if ($reservation->getClient() !== $user) {
            $logger->warning('Tentative d\'annulation non autorisée pour la réservation ID: ' . $reservation->getId());
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à annuler cette réservation.');
            return $this->redirectToRoute('reservation_list');
        }
        $userEmail=$user->getUserIdentifier();
        $Utilisateur = $utilisateurRepo->findOneBy(['email' => $userEmail]);
        try {
            // Get coach's email before removing the reservation
            $coach = $reservation->getSession()->getCoach();
            $coachEmail = $coach->getEmail();
            $clientName = $Utilisateur->getnom() . ' ' . $Utilisateur->getprenom();
            $game = $reservation->getSession()->getGame();
            $date = $reservation->getDateReservation()->format('d/m/Y H:i');
            $price = $reservation->getSession()->getPrix();

            // Send cancellation email to coach
            $emailService->sendCancellationEmail($coachEmail, $clientName, $game, $date, $price);
            $logger->info('Email d\'annulation envoyé au coach: ' . $coachEmail);

            // Remove the reservation
            $reservationRepository->remove($reservation);
            $logger->info('Réservation annulée avec succès pour ID: ' . $reservation->getId());
            $this->addFlash('success', 'La réservation a été annulée avec succès et le coach a été notifié.');

        } catch (\Exception $e) {
            $logger->error('Erreur lors de l\'annulation de la réservation : ' . $e->getMessage(), [
                'reservation_id' => $reservation->getId(),
                'exception' => $e,
            ]);
            $this->addFlash('error', 'Une erreur est survenue lors de l\'annulation : ' . $e->getMessage());
        }

        return $this->redirectToRoute('reservation_list');
    }
    
    #[Route('/coach/reservation/{id}/refuse', name: 'refuse_reservation', methods: ['POST'])]
    public function refuseReservation(
        Reservation $reservation,
        ReservationRepository $reservationRepository,
        EmailService $emailService,
        LoggerInterface $logger
    ): Response {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_COACH', $user->getRoles())) {
            $logger->warning('Accès non autorisé à refuse_reservation par un utilisateur non-coach.');
            return $this->redirectToRoute('app_login_page');
        }

        try {
            $client = $reservation->getClient();
            if (!$client) {
                throw new \Exception('Aucun client associé à cette réservation.');
            }

            // Send refusal email to client
            $emailService->sendRefusalEmail(
                $client->getEmail(),
                $client->getNom() . ' ' . $client->getPrenom(),
                $reservation->getSession()->getGame(),
                $reservation->getDateReservation()
            );

            // Remove the reservation
            $reservationRepository->remove($reservation);
            
            $this->addFlash('success', 'La réservation a été refusée et le client a été notifié');
            $logger->info('Réservation refusée avec succès pour ID: ' . $reservation->getId());
        } catch (\Exception $e) {
            $logger->error('Erreur lors du refus de la réservation : ' . $e->getMessage(), [
                'reservation_id' => $reservation->getId(),
                'exception' => $e,
            ]);
            $this->addFlash('error', 'Une erreur est survenue lors du refus : ' . $e->getMessage());
        }

        return $this->redirectToRoute('coach_reservations');
    }
}