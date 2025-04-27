<?php

namespace App\Controller;

use App\Entity\Demande;
use App\Entity\Games;
use App\Entity\Utilisateur;
use App\Entity\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class CoachApplicationController extends AbstractController
{
    #[Route('/become-coach', name: 'app_become_coach')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $games = $entityManager->getRepository(Games::class)->findAll();
        
        return $this->render('coach/application.html.twig', [
            'games' => $games,
        ]);
    }

    #[Route('/coach-applications', name: 'app_coach_applications')]
    public function listApplications(EntityManagerInterface $entityManager): Response
    {
        $demandes = $entityManager->getRepository(Demande::class)->findAll();
        
        return $this->render('coach/list_applications.html.twig', [
            'demandes' => $demandes,
        ]);
    }

    #[Route('/submit-coach-application', name: 'app_submit_coach_application', methods: ['POST'])]
    public function submitApplication(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        $game = $request->request->get('game');
        $description = $request->request->get('description');
        $cvFile = $request->files->get('cv');

        if (!$game || !$description || !$cvFile) {
            $this->addFlash('error', 'Please fill in all fields and upload your CV.');
            return $this->redirectToRoute('app_become_coach');
        }

        // Handle file upload
        $originalFilename = pathinfo($cvFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = 'cv_' . $safeFilename . '_' . uniqid() . '.' . $cvFile->guessExtension();

        try {
            $cvFile->move(
                $this->getParameter('cv_directory'),
                $newFilename
            );
        } catch (FileException $e) {
            $this->addFlash('error', 'There was an error uploading your CV.');
            return $this->redirectToRoute('app_become_coach');
        }

        // Create and persist the application
        $demande = new Demande();
        $demande->setUserId($user);
        $demande->setGame($game);
        $demande->setDescription($description);
        $demande->setFile($newFilename);
        $demande->setDate(new \DateTime());

        $entityManager->persist($demande);
        $entityManager->flush();

        $this->addFlash('success', 'Your application has been submitted successfully!');
        return $this->redirectToRoute('app_become_coach');
    }

    #[Route('/coach-application/{id}/accept', name: 'app_coach_application_accept', methods: ['POST'])]
    public function acceptApplication(Demande $demande, EntityManagerInterface $entityManager): Response
    {
        // Update user role to COACH
        $user = $demande->getUserId();
        $user->setRole(Role::COACH);
        
        // Remove the application since it's been accepted
        $entityManager->remove($demande);
        $entityManager->flush();

        $this->addFlash('success', sprintf('Application for %s %s has been accepted.', $user->getNom(), $user->getPrenom()));
        return $this->redirectToRoute('app_coach_applications');
    }

    #[Route('/coach-application/{id}/refuse', name: 'app_coach_application_refuse', methods: ['POST'])]
    public function refuseApplication(Demande $demande, EntityManagerInterface $entityManager): Response
    {
        // Get user info for flash message
        $userName = $demande->getUserId()->getNom() . ' ' . $demande->getUserId()->getPrenom();
        
        // Remove the application
        $entityManager->remove($demande);
        $entityManager->flush();

        $this->addFlash('info', sprintf('Application for %s has been refused.', $userName));
        return $this->redirectToRoute('app_coach_applications');
    }
} 