<?php

namespace App\Controller;

use App\Entity\Reservation; // Ajout de l'importation manquante
use App\Entity\Session_game;
use App\Form\SessionType;
use App\Repository\Session_gameRepository;
use App\Repository\ReservationRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SessionController extends AbstractController
{
    private $logger;

    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/coach/sessions', name: 'session_list')]
    public function index(Request $request, Session_gameRepository $sessionRepository, \Knp\Component\Pager\PaginatorInterface $paginator): Response
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_COACH', $user->getRoles())) {
            return $this->redirectToRoute('app_login_page');
        }

        $searchQuery = $request->query->get('search');
        $queryBuilder = $searchQuery
            ? $sessionRepository->findByGameNameAndCoach($searchQuery, $user)
            : $sessionRepository->findBy(['coach' => $user]);

        // Pagination
        $sessions = $paginator->paginate(
            $queryBuilder, // Query or QueryBuilder
            $request->query->getInt('page', 1), // Current page number
            10 // Limit per page
        );

        return $this->render('session/index.html.twig', [
            'sessions' => $sessions,
            'searchQuery' => $searchQuery,
            'image_base_url' => $this->getParameter('image_base_url'),
        ]);
    }

    #[Route('/session/{id}/expired', name: 'session_expired')]
    public function expiredSession(Session_game $session): Response
    {
        if (!$session->isExpired()) {
            return $this->redirectToRoute('session_client_list');
        }

        return $this->render('session/expired_session.html.twig', [
            'session' => $session,
            'image_base_url' => $this->getParameter('image_base_url'),
        ]);
    }

    #[Route('/session/{id}/request-reactivation', name: 'session_request_reactivation', methods: ['POST'])]
    public function requestReactivation(Session_game $session, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login_page');
        }

        $reservation = new Reservation();
        $reservation->setClient($user);
        $reservation->setSession($session);
        $reservation->setDateReservation(new \DateTime());
       

        $entityManager->persist($reservation);
        $entityManager->flush();

        $this->addFlash('success', 'Votre demande de réactivation a été enregistrée avec succès.');

        return $this->redirectToRoute('session_client_list');
    }

    #[Route('/sessions/available', name: 'session_client_list')]
    public function clientIndex(Request $request, Session_gameRepository $sessionRepository, ReservationRepository $reservationRepository, \Knp\Component\Pager\PaginatorInterface $paginator): Response
    {
        $searchQuery = $request->query->get('search');
        $queryBuilder = $searchQuery
            ? $sessionRepository->findByGameName($searchQuery)
            : $sessionRepository->findAll();

        // Pagination
        $sessions = $paginator->paginate(
            $queryBuilder, // Query or QueryBuilder
            $request->query->getInt('page', 1), // Current page number
            10 // Limit per page
        );

        // Logic for checking reserved sessions
        $reservedSessions = [];
        foreach ($sessions as $session) {
            $reservedSessions[$session->getId()] = $reservationRepository->findOneBy(['session' => $session]) !== null;
        }

        return $this->render('session/client_index.html.twig', [
            'sessions' => $sessions,
            'searchQuery' => $searchQuery,
            'image_base_url' => $this->getParameter('image_base_url'),
            'reservedSessions' => $reservedSessions,
        ]);
    }

    #[Route('/session/add', name: 'session_add', methods: ['GET', 'POST'])]
    public function ajouter(Request $request, EntityManagerInterface $entityManager, UtilisateurRepository $utilisateurRepository): Response
    {
        $user = $this->getUser();
        $email = $user->getUserIdentifier();
        $utilisateur = $utilisateurRepository->findOneBy(['email' => $email]);
        $session = new Session_game();
        $form = $this->createForm(SessionType::class, $session);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $session->setDateCreation(new \DateTime());
            $session->setCoach($utilisateur);

            /** @var UploadedFile $photoFile */
            $photoFile = $form->get('image')->getData();

            if ($photoFile) {
                $newFilename = uniqid().'.'.$photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('uploads_directory'),
                        $newFilename
                    );
                    $session->setImageName($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du fichier.');
                    return $this->redirectToRoute('session_add');
                }
            }

            $entityManager->persist($session);
            $entityManager->flush();

            $this->addFlash('success', 'Session ajoutée avec succès !');
            return $this->redirectToRoute('session_list');
        }

        return $this->render('session/add.html.twig', [
            'form' => $form->createView(),
            'image_base_url' => $this->getParameter('image_base_url'),
        ]);
    }

    #[Route('/session/promo', name: 'session_promo')]
    public function promo(Session_gameRepository $sessionRepository): Response
    {
        $promoSessions = $sessionRepository->getSessionsInPromo();

        return $this->render('session/promo.html.twig', [
            'sessions' => $promoSessions,
            'image_base_url' => $this->getParameter('image_base_url'),
        ]);
    }

    #[Route('/session/{id}/edit', name: 'session_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Session_game $session, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();
        /* if (!$user || !in_array('ROLE_COACH', $user->getRoles()) || $session->getCoach() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cette session.');
        } */

        $form = $this->createForm(SessionType::class, $session);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('uploads_directory'),
                        $newFilename
                    );
                    
                    // Supprimer l'ancienne image si elle existe
                    $oldImage = $session->getImageName();
                    if ($oldImage) {
                        $oldImagePath = $this->getParameter('uploads_directory') . '/' . $oldImage;
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    
                    $session->setImageName($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du fichier.');
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Session modifiée avec succès.');
            return $this->redirectToRoute('session_list');
        }

        return $this->render('session/edit.html.twig', [
            'form' => $form->createView(),
            'session' => $session,
            'image_base_url' => $this->getParameter('image_base_url'),
        ]);
    }

    #[Route('/session/{id}/delete', name: 'session_delete', methods: ['POST'])]
    public function delete(Request $request, Session_game $session, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur est connecté et a le rôle de coach
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_COACH', $user->getRoles())) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer cette session.');
        }

        // Vérifier que le coach est bien le propriétaire de la session
        if ($session->getCoach() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer cette session.');
        }

        // Vérifier la validité du token CSRF
        if (!$this->isCsrfTokenValid('delete', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide. La session n\'a pas été supprimée.');
            return $this->redirectToRoute('session_list');
        }

        try {
            // Supprimer l'image associée si elle existe
            $imageName = $session->getImageName();
            if ($imageName) {
                $imagePath = $this->getParameter('uploads_directory') . '/' . $imageName;
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            // Supprimer la session de la base de données
            $entityManager->remove($session);
            $entityManager->flush();

            $this->addFlash('success', 'Session supprimée avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression de la session.');
            // Log l'erreur pour le debugging
            $this->logger->error('Erreur lors de la suppression de la session: ' . $e->getMessage());
        }

        return $this->redirectToRoute('session_list');
    }
}