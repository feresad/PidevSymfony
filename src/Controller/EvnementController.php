<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Form\EvenementType;

use App\Repository\EvenementRepository;
use App\Repository\ClientEvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
#[Route("/evenement")]
final class EvnementController extends AbstractController
{
    #[Route('/all',name: 'evenement_list')]
    public function gettAll(EvenementRepository $repo, Request $request, ClientEvenementRepository $clientEvenementRepo):Response{
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', 'nom_asc');
        $page = $request->query->getInt('page', 1);
        $limit = 6;
        $evenements = $repo->findBySearchAndSort($search, $sort, $page, $limit);
        $totalEvenements = $repo->countBySearch($search);
        $maxPages = ceil($totalEvenements / $limit);

        // Récupérer les réservations de l'utilisateur connecté
        $userReservations = [];
        if ($this->getUser()) {
            $reservations = $clientEvenementRepo->findBy(['client' => $this->getUser()]);
            foreach ($reservations as $reservation) {
                $userReservations[$reservation->getEvenement()->getId()] = true;
            }
        }

        return $this->render('evenement/ListeEvenement.html.twig', [
            'evenements' => $evenements,
            'image_base_url' => $this->getParameter('image_base_url'),
            'current_page' => $page,
            'max_pages' => $maxPages,
            'search' => $search,
            'sort' => $sort,
            'userReservations' => $userReservations,
        ]);
    }
    #[Route('/add', name: 'evenement_ajouter')]
    public function ajouter(Request $request, EntityManagerInterface $entityManager): Response
    {
        $evenement = new Evenement();
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $photoFile */
            $photoFile = $form->get('photoFile')->getData();
    
            if ($photoFile) {
                $newFilename = uniqid().'.'.$photoFile->guessExtension();
    
                try {
                    $photoFile->move('C:/xampp/htdocs/img', $newFilename);
                    $evenement->setPhotoEvent($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du fichier.');
                    return $this->redirectToRoute('evenement_ajouter');
                }
            }
    
            $entityManager->persist($evenement);
            $entityManager->flush();
    
            $this->addFlash('success', 'Événement ajouté avec succès !');
            return $this->redirectToRoute('evenement_list_admin');
        }
    
        return $this->render('evenement/ajouterEvenement.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/delete/{id}', name: 'evenement_supprimer')]
    public function supprimer(int $id, EvenementRepository $repo, EntityManagerInterface $entityManager): Response
    {
        $evenement = $repo->find($id);
    
        if (!$evenement) {
            $this->addFlash('error', 'Événement non trouvé.');
            return $this->redirectToRoute('evenement_list');
        }

        $entityManager->remove($evenement);
        $entityManager->flush();

        $this->addFlash('success', 'Événement supprimé avec succès !');
        return $this->redirectToRoute('evenement_list_admin');
}
#[Route('/edit/{id}', name: 'evenement_modifier')]
    public function modifier(int $id, Request $request, EvenementRepository $repo, EntityManagerInterface $entityManager): Response
    {
        $evenement = $repo->find($id);

        if (!$evenement) {
            $this->addFlash('error', 'Événement non trouvé.');
            return $this->redirectToRoute('evenement_list_admin');
        }

        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $photoFile */
            $photoFile = $form->get('photoFile')->getData();

            if ($photoFile) {
                $newFilename = uniqid() . '.' . $photoFile->guessExtension();

                try {
                    $uploadDir = $this->getParameter('dossier_upload');

                    // Supprimer l'ancienne photo si elle existe
                    if ($evenement->getPhotoEvent()) {
                        $oldFile = $uploadDir . '/' . $evenement->getPhotoEvent();
                        if (file_exists($oldFile)) {
                            unlink($oldFile);
                        }
                    }

                    // Déplacer la nouvelle photo
                    $photoFile->move($uploadDir, $newFilename);
                    $evenement->setPhotoEvent($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du fichier : ' . $e->getMessage());
                    return $this->redirectToRoute('evenement_modifier', ['id' => $id]);
                }
            }

            $entityManager->persist($evenement);
            $entityManager->flush();

            $this->addFlash('success', 'Événement modifié avec succès !');
            return $this->redirectToRoute('evenement_list_admin');
        }

        return $this->render('evenement/modifierEvenement.html.twig', [
            'form' => $form->createView(),
            'evenement' => $evenement,
            'image_base_url' => $this->getParameter('image_base_url'),
        ]);
    }
    #[Route('/show/{id}', name: 'evenement_detailles')]
    public function Detaills(int $id, EvenementRepository $repo): Response
    {
        $evenement = $repo->find($id);

        if (!$evenement) {
            $this->addFlash('error', 'Événement non trouvé.');
            return $this->redirectToRoute('evenement_list');
        }

        return $this->render('evenement/DetailsEvenement.html.twig', [
            'evenement' => $evenement,
            'image_base_url' => $this->getParameter('image_base_url'),
        ]);
    }
    #[Route('/qrcode/{id}', name: 'evenement_qrcode')]
    public function generateQrCode(int $id, EvenementRepository $repo): Response
    {
        $evenement = $repo->find($id);
        if (!$evenement) {
            $this->addFlash('error', 'Événement non trouvé.');
            return $this->redirectToRoute('evenement_list');
        }
    
        $baseUrl = $this->getParameter('git_url');
        $url = $baseUrl . '?' .
        http_build_query([
            'id' => $evenement->getId(),
            'nom' => $evenement->getNomEvent(),
            'date' => $evenement->getDateEvent()->format('Y-m-d H:i'),
            'lieu' => $evenement->getLieuEvent(),
            'places' => $evenement->getMaxPlacesEvent(),
        ]);
        $qrCode = new QrCode($url);
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        return new Response(
            $result->getString(),
            Response::HTTP_OK,
            ['Content-Type' => 'image/png']
        );
    }
    #[Route('/admin/all',name: 'evenement_list_admin')]
    public function getAll(EvenementRepository $repo, Request $request):Response{
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', 'nom_asc');
        $page = $request->query->getInt('page', 1); // Page actuelle, par défaut 1
        $limit = 5; // Nombre d'événements par page

        // Récupérer les événements paginés
        $evenements = $repo->findBySearchAndSort($search, $sort, $page, $limit);
        $totalEvenements = $repo->countBySearch($search); // Méthode à ajouter dans le repository
        $maxPages = ceil($totalEvenements / $limit);

        return $this->render('evenement/ListeEvenementadmin.html.twig', [
            'evenements' => $evenements,
            'image_base_url' => $this->getParameter('image_base_url'),
            'current_page' => $page,
            'max_pages' => $maxPages,
            'search' => $search,
            'sort' => $sort,
        ]);
    }
    #[Route('/admin/show/{id}', name: 'evenement_detailles_admin')]
    public function DetaillsAdmib(int $id, EvenementRepository $repo): Response
    {
        $evenement = $repo->find($id);

        if (!$evenement) {
            $this->addFlash('error', 'Événement non trouvé.');
            return $this->redirectToRoute('evenement_list');
        }

        return $this->render('evenement/DetailsEvenementAdmin.html.twig', [
            'evenement' => $evenement,
            'image_base_url' => $this->getParameter('image_base_url'),
        ]);
    }
    #[Route('/calendar', name: 'evenement_calendar')]
public function calendar(): Response
{
    return $this->render('evenement/calendar.html.twig');
}
#[Route('/load-events', name: 'evenement_load_events')]
    public function loadEvents(Request $request, EvenementRepository $repo): Response
    {
        $start = new \DateTime($request->query->get('start'));
        $end = new \DateTime($request->query->get('end'));

        // Récupérer les événements dans l'intervalle de dates
        $evenements = $repo->findByDateRange($start, $end);
        $events = [];

        foreach ($evenements as $evenement) {
            $events[] = [
                'title' => $evenement->getNomEvent(),
                'start' => $evenement->getDateEvent()->format('c'), // Format ISO 8601
                'url' => $this->generateUrl('evenement_detailles', ['id' => $evenement->getId()]),
                'backgroundColor' => '#3788d8',
                'borderColor' => '#3788d8',
                'textColor' => '#ffffff',
            ];
        }

        return new Response(json_encode($events), 200, ['Content-Type' => 'application/json']);
    }
}

