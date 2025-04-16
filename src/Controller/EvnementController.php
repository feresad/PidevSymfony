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
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
#[Route("/evenement")]
final class EvnementController extends AbstractController
{
    #[Route('/all', name: 'evenement_list')]
public function gettAll(EvenementRepository $repo, Request $request, ClientEvenementRepository $clientEvenementRepo): Response
{
    $search = $request->query->get('search', '');
    $sort = $request->query->get('sort', 'nom_asc');
    $page = $request->query->getInt('page', 1);
    $limit = 6;
    $evenements = $repo->findBySearchAndSort($search, $sort, $page, $limit);
    $totalEvenements = $repo->countBySearch($search);
    $maxPages = ceil($totalEvenements / $limit);

    $userReservations = [];
    $reservationCounts = [];
    if ($this->getUser()) {
        $reservations = $clientEvenementRepo->findBy(['client' => $this->getUser()]);
        foreach ($reservations as $reservation) {
            $userReservations[$reservation->getEvenement()->getId()] = true;
        }
    }
    foreach ($evenements as $evenement) {
        $reservationCounts[$evenement->getId()] = $repo->getReservationCountForEvent($evenement->getId());
    }

    return $this->render('evenement/ListeEvenement.html.twig', [
        'evenements' => $evenements,
        'image_base_url' => $this->getParameter('image_base_url'),
        'current_page' => $page,
        'max_pages' => $maxPages,
        'search' => $search,
        'sort' => $sort,
        'userReservations' => $userReservations,
        'now' => new \DateTime(),
        'reservationCounts' => $reservationCounts,
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
    public function supprimer(int $id, EvenementRepository $repo, ClientEvenementRepository $clientEvenementRepo, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $evenement = $repo->find($id);

        if (!$evenement) {
            $this->addFlash('error', 'Événement non trouvé.');
            return $this->redirectToRoute('evenement_list');
        }

        $now = new \DateTime();
        $eventDate = $evenement->getDateEvent();
        if ($eventDate > $now) {
            $reservations = $clientEvenementRepo->findBy(['evenement' => $evenement]);

            if (!empty($reservations)) {
                foreach ($reservations as $reservation) {
                    $user = $reservation->getClient();
                    $userEmail = $user->getUserIdentifier();

                    $email = (new Email())
                        ->from('noreply@votredomaine.com')
                        ->to($userEmail)
                        ->subject('Annulation de l\'événement - ' . $evenement->getNomEvent())
                        ->html($this->renderView('evenement/email_suppression_evenement.html.twig', [
                            'user' => $user,
                            'evenement' => $evenement,
                            'reservation' => $reservation,
                        ]));

                
                    $logoPath = $this->getParameter('kernel.project_dir') . '/assets/images/level.png';
                    if (file_exists($logoPath)) {
                        $email->embedFromPath($logoPath, 'logo');
                    }

                    try {
                        $mailer->send($email);
                    } catch (\Exception $e) {
                        $this->addFlash('warning', 'Événement supprimé, mais l\'email à ' . $userEmail . ' n\'a pas pu être envoyé.');
                    }
                }
            }
        }

        // Supprimer l'événement et toutes ses réservations associées
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

                    if ($evenement->getPhotoEvent()) {
                        $oldFile = $uploadDir . '/' . $evenement->getPhotoEvent();
                        if (file_exists($oldFile)) {
                            unlink($oldFile);
                        }
                    }

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
        $imageUrl = '';

        // Si une image existe, la téléverser sur ImgBB
        if ($evenement->getPhotoEvent()) {
            $imagePath = 'C:/xampp/htdocs/img/' . $evenement->getPhotoEvent();
            if (file_exists($imagePath)) {
                try {
                    $client = HttpClient::create();
                    $response = $client->request('POST', 'https://api.imgbb.com/1/upload', [
                        'body' => [
                            'key' => $this->getParameter('imgbb_api_key'), // Utiliser la clé API depuis les paramètres
                            'image' => base64_encode(file_get_contents($imagePath)), // Encoder l'image en base64
                        ],
                    ]);

                    $data = $response->toArray();
                    if (isset($data['data']['url'])) {
                        $imageUrl = $data['data']['url']; // URL de l'image hébergée
                    } else {
                        $this->addFlash('warning', 'Erreur lors du téléversement de l\'image sur ImgBB.');
                    }
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Erreur lors du téléversement de l\'image : ' . $e->getMessage());
                }
            }
        }

        // Construire l'URL pour le QR code
        $url = $baseUrl . '?' . http_build_query([
            'id' => $evenement->getId(),
            'nom' => $evenement->getNomEvent(),
            'date' => $evenement->getDateEvent()->format('Y-m-d H:i'),
            'lieu' => $evenement->getLieuEvent(),
            'places' => $evenement->getMaxPlacesEvent(),
            'image' => $imageUrl,
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
        $page = $request->query->getInt('page', 1);
        $limit = 5;

       
        $evenements = $repo->findBySearchAndSort($search, $sort, $page, $limit);
        $totalEvenements = $repo->countBySearch($search);
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
public function loadEvents(Request $request, EvenementRepository $repo, ClientEvenementRepository $clientEvenementRepo): Response
{
    $start = new \DateTime($request->query->get('start'));
    $end = new \DateTime($request->query->get('end'));

    $evenements = $repo->findByDateRange($start, $end);
    $events = [];

    // Récupérer l'utilisateur connecté
    $user = $this->getUser();
    $userReservations = [];
    if ($user) {
        $reservations = $clientEvenementRepo->findBy(['client' => $user]);
        foreach ($reservations as $reservation) {
            $userReservations[$reservation->getEvenement()->getId()] = true;
        }
    }

    foreach ($evenements as $evenement) {
        $dateDebut = $evenement->getDateEvent();
        // Calculer la date de fin : date de début + 2 heures
        $dateFin = clone $dateDebut;
        $dateFin->modify('+2 hours');

        $isReserved = $user && isset($userReservations[$evenement->getId()]);
        $events[] = [
           'title' => $evenement->getNomEvent(),
            'start' => $dateDebut->format('c'),
            'end' => $dateFin->format('c'),
            'url' => $this->generateUrl('evenement_detailles', ['id' => $evenement->getId()]),
            'backgroundColor' => $isReserved ? ' #5cb85c' : ' #3788d8',
            'borderColor' => $isReserved ? ' #5cb85c' : ' #3788d8',
            'textColor' => '#ffffff',
        ];
    }

    return new Response(json_encode($events), 200, ['Content-Type' => 'application/json']);
}
}

