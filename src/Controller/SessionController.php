<?php

namespace App\Controller;

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
    #[Route('/coach/sessions', name: 'session_list')]
    public function index(Request $request, Session_gameRepository $sessionRepository): Response
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_COACH', $user->getRoles())) {
            return $this->redirectToRoute('app_login_page');
        }

        $searchQuery = $request->query->get('search');
        $sessions = $searchQuery
            ? $sessionRepository->findByGameNameAndCoach($searchQuery, $user)
            : $sessionRepository->findBy(['coach' => $user]);

        return $this->render('session/index.html.twig', [
            'sessions' => $sessions,
            'searchQuery' => $searchQuery,
            'image_base_url' => $this->getParameter('image_base_url'),
        ]);
    }

    #[Route('/sessions/available', name: 'session_client_list')]
    public function clientIndex(Request $request, Session_gameRepository $sessionRepository, ReservationRepository $reservationRepository): Response
    {
        $searchQuery = $request->query->get('search');
        $sessions = $searchQuery
            ? $sessionRepository->findByGameName($searchQuery)
            : $sessionRepository->findAll();

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
    public function ajouter(Request $request, EntityManagerInterface $entityManager,UtilisateurRepository $utilisateurRepository): Response
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
                    $photoFile->move('C:/xampp/htdocs/img', $newFilename);
                    $session->setImageName($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de lupload du fichier.');
                    return $this->redirectToRoute('session_add');
                }
            }

            $entityManager->persist($session);
            $entityManager->flush();

            $this->addFlash('success', 'session ajouté avec succès !');
            return $this->redirectToRoute('session_list');
        }

        return $this->render('session/add.html.twig', [
            'form' => $form->createView(),
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
        }
*/
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
                    $imageFile->move('C:/xampp/htdocs/img', $newFilename);
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
        $user = $this->getUser();
       /* if (!$user || !in_array('ROLE_COACH', $user->getRoles()) || $session->getCoach() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer cette session.');
        }*/

        if ($this->isCsrfTokenValid('delete' . $session->getId(), $request->request->get('_token'))) {
            $entityManager->remove($session);
            $entityManager->flush();

            $this->addFlash('success', 'Session supprimée avec succès.');
        }

        return $this->redirectToRoute('session_list');
    }
}
