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
use Dompdf\Dompdf;
use Dompdf\Options;

class ReservationController extends AbstractController
{
    #[Route('/coach/reservation/{id}/send-meet-link', name: 'send_meet_link', methods: ['POST'])]
    public function sendMeetLink(
        Reservation $reservation,
        EmailService $emailService,
        LoggerInterface $logger
    ): Response {
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

        $allReservations = $reservationRepository->findAll();
        $reservations = array_filter($allReservations, function($reservation) {
            return !$reservation->getSession()->isExpired();
        });

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

    #[Route('/requests', name: 'request_list')]
    public function requestList(ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login_page');
        }
        
        // Get all reservations for the current user
        $allReservations = $reservationRepository->findBy(['client' => $user]);
        
        // Filter to only include reservations for expired sessions
        $reservations = array_filter($allReservations, function($reservation) {
            return $reservation->getSession()->isExpired();
        });

        return $this->render('reservation/request_list.html.twig', [
            'reservations' => $reservations
        ]);
    }

    #[Route('/request/{id}/accept', name: 'accept_request', methods: ['POST'])]
    public function acceptRequest(
        Reservation $reservation,
        EmailService $emailService,
        LoggerInterface $logger
    ): Response {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_COACH', $user->getRoles())) {
            $logger->warning('Accès non autorisé à accept_request par un utilisateur non-coach.');
            return $this->redirectToRoute('app_login_page');
        }

        try {
            $session = $reservation->getSession();
            $client = $reservation->getClient();
            if (!$client || !$session) {
                throw new \Exception('Données de réservation incomplètes.');
            }

            // Calculate 10% higher price
            $newPrice = $session->getPrix() * 1.1;
            $session->setPrix($newPrice);

            // Send acceptance email
            $emailService->sendAcceptanceEmail(
                $client->getEmail(),
                $client->getNom() . ' ' . $client->getPrenom(),
                $session->getGame(),
                $reservation->getDateReservation(),
                $newPrice
            );

            $this->addFlash('success', 'La demande a été acceptée et le client a été notifié.');
        } catch (\Exception $e) {
            $logger->error('Erreur lors de l\'acceptation de la réservation : ' . $e->getMessage(), [
                'reservation_id' => $reservation->getId(),
                'exception' => $e,
            ]);
            $this->addFlash('error', 'Une erreur est survenue : ' . $e->getMessage());
        }

        return $this->redirectToRoute('request_list');
    }

    #[Route('/request/{id}/reject', name: 'reject_request', methods: ['POST'])]
    public function rejectRequest(
        Reservation $reservation,
        EmailService $emailService,
        LoggerInterface $logger
    ): Response {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_COACH', $user->getRoles())) {
            $logger->warning('Accès non autorisé à reject_request par un utilisateur non-coach.');
            return $this->redirectToRoute('app_login_page');
        }

        try {
            $session = $reservation->getSession();
            $client = $reservation->getClient();
            if (!$client || !$session) {
                throw new \Exception('Données de réservation incomplètes.');
            }

            // Mark session as expired
            $session->setDateCreation(new \DateTime('-31 days'));

            // Send rejection email
            $emailService->sendRefusalEmail(
                $client->getEmail(),
                $client->getNom() . ' ' . $client->getPrenom(),
                $session->getGame(),
                $reservation->getDateReservation()
            );

            $this->addFlash('success', 'La demande a été rejetée et le client a été notifié.');
        } catch (\Exception $e) {
            $logger->error('Erreur lors du rejet de la réservation : ' . $e->getMessage(), [
                'reservation_id' => $reservation->getId(),
                'exception' => $e,
            ]);
            $this->addFlash('error', 'Une erreur est survenue : ' . $e->getMessage());
        }

        return $this->redirectToRoute('request_list');
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
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login_page');
        }

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

            try {
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
                $reservation->setClient($user);
                $reservation->setDateReservation($reservationDate);

                $reservationRepository->add($reservation);

                $this->addFlash('success', 'Votre réservation a été effectuée avec succès');
                return $this->redirectToRoute('reservation_list');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création de la réservation : ' . $e->getMessage());
                return $this->render('reservation/add.html.twig', [
                    'session' => $session,
                    'error' => 'Erreur de traitement',
                    'image_base_url' => $this->getParameter('image_base_url'),
                ]);
            }
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
        LoggerInterface $logger,
        UtilisateurRepository $utilisateurRepo,
        EmailService $emailService
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            $logger->warning('Tentative d\'annulation de réservation sans utilisateur connecté.');
            return $this->redirectToRoute('app_login_page');
        }

        if ($reservation->getClient() !== $user) {
            $logger->warning('Tentative d\'annulation non autorisée pour la réservation ID: ' . $reservation->getId());
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à annuler cette réservation.');
            return $this->redirectToRoute('reservation_list');
        }

        try {
            $userEmail = $user->getUserIdentifier();
            $utilisateur = $utilisateurRepo->findOneBy(['email' => $userEmail]);

            $coach = $reservation->getSession()->getCoach();
            $coachEmail = $coach->getEmail();
            $clientName = $utilisateur->getNom() . ' ' . $utilisateur->getPrenom();
            $game = $reservation->getSession()->getGame();
            $date = $reservation->getDateReservation()->format('d/m/Y H:i');
            $price = $reservation->getSession()->getPrix();

            $emailService->sendCancellationEmail($coachEmail, $clientName, $game, $date, $price);
            $logger->info('Email d\'annulation envoyé au coach: ' . $coachEmail);

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

            $emailService->sendRefusalEmail(
                $client->getEmail(),
                $client->getNom() . ' ' . $client->getPrenom(),
                $reservation->getSession()->getGame(),
                $reservation->getDateReservation()
            );

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

    #[Route('/coach/reservations/export-pdf', name: 'coach_reservations_export_pdf')]
    public function exportCoachReservationsPdf(ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_COACH', $user->getRoles())) {
            return $this->redirectToRoute('app_login_page');
        }

        $allReservations = $reservationRepository->findAll();
        $reservations = array_filter($allReservations, function($reservation) {
            return !$reservation->getSession()->isExpired();
        });

        $html = $this->renderView('reservation/pdf_reservations.html.twig', [
            'reservations' => $reservations
        ]);

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response(
            $dompdf->stream('coach_reservations.pdf', ["Attachment" => true]),
            200,
            ['Content-Type' => 'application/pdf']
        );
    }
}