<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Repository\EvenementRepository;
use App\Repository\ClientEvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

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
}

