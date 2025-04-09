<?php

namespace App\Controller;

use App\Entity\ClientEvenement;
use App\Entity\Evenement;
use App\Entity\Utilisateur;
use App\Repository\EvenementRepository;
use App\Repository\ClientEvenementRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route("/reser")]
final class ClientEvenementController extends AbstractController
{
    #[Route('/admin/export-pdf/{id}', name: 'evenement_export_pdf')]
    public function exportPdf(int $id, EvenementRepository $evenementRepo, ClientEvenementRepository $clientEvenementRepo): Response
    {
        // Récupérer l'événement
        $evenement = $evenementRepo->find($id);
        if (!$evenement) {
            $this->addFlash('error', 'Événement non trouvé.');
            return $this->redirectToRoute('evenement_list_admin');
        }
        
        // Récupérer les réservations pour cet événement
        $reservations = $clientEvenementRepo->findBy(['evenement' => $evenement]);

        // Configurer Dompdf
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($pdfOptions);

        // Date d'exportation dynamique (aujourd'hui)
        $dateExportation = new \DateTime();
        
        // Chemin absolu vers le logo
        $logoPath = $this->getParameter('kernel.project_dir') . '/assets/images/lev.png';
        
        // Convertir l'image en base64
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoBase64 = base64_encode(file_get_contents($logoPath));
        }

        // Générer le contenu HTML pour le PDF
        $html = $this->renderView('evenement/pdf_reservations.html.twig', [
            'evenement' => $evenement,
            'reservations' => $reservations,
            'logo_base64' => $logoBase64,
            'date_exportation' => $dateExportation->format('d/m/Y'), // Format JJ/MM/AAAA
        ]);

        // Charger le HTML dans Dompdf
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Générer le nom du fichier
        $filename = 'Reservations_' . $evenement->getNomEvent() . '_' . date('Ymd') . '.pdf';

        // Retourner le PDF en tant que réponse
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }
    #[Route('/evenement/reserver/{id}', name: 'reserver_evenement', methods: ['POST'])]
    public function reserver(int $id, EvenementRepository $evenementRepo,
     ClientEvenementRepository $clientEvenementRepo,
      EntityManagerInterface $entityManager,
      MailerInterface $mailer): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour réserver un événement.');
            return $this->redirectToRoute('app_login_page');
        }

        // Récupérer l'événement
        $evenement = $evenementRepo->find($id);
        if (!$evenement) {
            $this->addFlash('error', 'Événement non trouvé.');
            return $this->redirectToRoute('evenement_list');
        }

        if ($evenement->getMaxPlacesEvent() <= 0) {
            $this->addFlash('error', 'Aucune place disponible pour cet événement.');
            return $this->redirectToRoute('evenement_list');
        }

        $existingReservation = $clientEvenementRepo->findOneBy([
            'client' => $user,
            'evenement' => $evenement,
        ]);
        if ($existingReservation) {
            $this->addFlash('error', 'Vous avez déjà réservé cet événement.');
            return $this->redirectToRoute('evenement_list');
        }

        $reservation = new ClientEvenement();
        $reservation->setClient($user);
        $reservation->setEvenement($evenement);

        $evenement->setMaxPlacesEvent($evenement->getMaxPlacesEvent() - 1);

        $entityManager->persist($reservation);
        $entityManager->persist($evenement);
        $entityManager->flush();

        // Récupérer l'email de l'utilisateur depuis la base de données
        $userEmail = $user->getUserIdentifier();
        
        // Envoyer l'email de confirmation
        $email = (new Email())
            ->from('noreply@votredomaine.com')
            ->to($userEmail)
            ->subject('Confirmation de réservation - ' . $evenement->getNomEvent())
            ->html($this->renderView('evenement/reservation_confirmation.html.twig', [
                'user' => $user,
                'evenement' => $evenement,
                'reservation' => $reservation
            ]));

        // Joindre l'image du logo
        $logoPath = $this->getParameter('kernel.project_dir') . '/assets/images/level.png';
        if (file_exists($logoPath)) {
            $email->embedFromPath($logoPath, 'logo');
        }

        try {
            $mailer->send($email);
            $this->addFlash('success', 'Réservation effectuée avec succès ! Un e-mail de confirmation vous a été envoyé.');
        } catch (\Exception $e) {
            // Log l'erreur mais ne bloque pas la réservation
            $this->addFlash('warning', 'La réservation a été effectuée mais l\'email de confirmation n\'a pas pu être envoyé.');
        }
    
        return $this->redirectToRoute('evenement_list');
    }

    #[Route('/evenement/annuler-reservation/{id}', name: 'evenement_annuler_reservation')]
    public function annulerReservation(Request $request, Evenement $evenement, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $reservation = $entityManager->getRepository(ClientEvenement::class)->findOneBy([
            'evenement' => $evenement,
            'client' => $user
        ]);

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }

        $now = new \DateTime();
        $eventDate = $evenement->getDateEvent();
        $interval = $now->diff($eventDate);

        if ($interval->days < 1 && $eventDate > $now) {
            $this->addFlash('error', 'Impossible d\'annuler la réservation moins de 24h avant l\'événement.');
            return $this->redirectToRoute('evenement_list');
        }
        $evenement->setMaxPlacesEvent($evenement->getMaxPlacesEvent() + 1);
        $entityManager->remove($reservation);
        $entityManager->flush();

        $email = (new Email())
            ->from('noreply@votredomaine.com')
            ->to($user->getUserIdentifier())
            ->subject('Annulation de réservation - ' . $evenement->getNomEvent())
            ->html($this->renderView('evenement/reservation_annulation.html.twig', [
                'user' => $user,
                'evenement' => $evenement,
                'reservation' => $reservation
            ]));

        $logoPath = $this->getParameter('kernel.project_dir') . '/assets/images/level.png';
        if (file_exists($logoPath)) {
            $email->embedFromPath($logoPath, 'logo');
        }

        try {
            $mailer->send($email);
            $this->addFlash('success', 'Votre réservation a été annulée avec succès. Un email de confirmation vous a été envoyé.');
        } catch (\Exception $e) {
            $this->addFlash('warning', 'La réservation a été annulée mais l\'email de confirmation n\'a pas pu être envoyé.');
        }

        return $this->redirectToRoute('evenement_list');
    }
}
