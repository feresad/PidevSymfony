<?php

namespace App\Controller;

use App\Entity\Session_game;
use App\Form\SessionType;
use App\Repository\Session_gameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class SessionController extends AbstractController
{
    #[Route('/sessions', name: 'session_list')]
    public function index(Request $request, Session_gameRepository $sessionRepository): Response
    {
        $searchQuery = $request->query->get('search');
        $sessions = $searchQuery ? $sessionRepository->findByGameName($searchQuery) : $sessionRepository->findAll();
        return $this->render('session/index.html.twig', [
            'sessions' => $sessions,
            'searchQuery' => $searchQuery,
            'image_base_url' => $this->getParameter('image_base_url'),
        ]);
    }

    #[Route('/sessions/available', name: 'session_client_list')]
    public function clientIndex(Request $request, Session_gameRepository $sessionRepository): Response
    {
        $searchQuery = $request->query->get('search');
        $sessions = $searchQuery ? $sessionRepository->findByGameName($searchQuery) : $sessionRepository->findAll();
        return $this->render('session/client_index.html.twig', [
            'sessions' => $sessions,
            'searchQuery' => $searchQuery,
            'image_base_url' => $this->getParameter('image_base_url'),
        ]);
    }

    #[Route('/session/add', name: 'session_add', methods: ['GET', 'POST'])]
   
    public function ajouter(Request $request, EntityManagerInterface $entityManager): Response
    {
        $session = new Session_game();
        $form = $this->createForm(SessionType::class, $session);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $session->setDateCreation(new \DateTime());
            $session->setCoachId(1); // Valeur statique
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
        return $this->render('session/promo.html.twig', ['sessions' => $promoSessions,
    'image_base_url' => $this->getParameter('image_base_url'),
]);
    }

    #[Route('/session/{id}/edit', name: 'session_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Session_game $session, Session_gameRepository $sessionRepository, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(SessionType::class, $session);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('image_base_url'),
                        $newFilename
                    );
                    $session->setImageName($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image');
                }
                return $this->render('session/edit.html.twig', [
                    'form' => $form->createView(),
                    'session' => $session,
                    'image_base_url' => $this->getParameter('image_base_url'), 
                ]);
            }

            $sessionRepository->add($session);
            return $this->redirectToRoute('session_list');
        }

        return $this->render('session/edit.html.twig', [
            'form' => $form->createView(),
            'session' => $session
        ]);
    }

    #[Route('/session/{id}/delete', name: 'session_delete', methods: ['POST'])]
    public function delete(Request $request, Session_game $session, Session_gameRepository $sessionRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$session->getId(), $request->request->get('_token'))) {
            $sessionRepository->remove($session);
        }

        return $this->redirectToRoute('session_list');
    }
}