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

        // Générer le contenu HTML pour le PDF
        $html = $this->renderView('evenement/pdf_reservations.html.twig', [
            'evenement' => $evenement,
            'reservations' => $reservations,
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
      EntityManagerInterface $entityManager): Response
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

    
    $this->addFlash('success', 'Réservation effectuée avec succès ! Un e-mail de confirmation vous a été envoyé.');
    return $this->redirectToRoute('evenement_list');
    }
}
