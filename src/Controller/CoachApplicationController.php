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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class CoachApplicationController extends AbstractController
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    #[Route('/become-coach', name: 'app_become_coach')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $games = $entityManager->getRepository(Games::class)->findAll();
        
        return $this->render('coach/application.html.twig', [
            'games' => $games,
        ]);
    }

    #[Route('/coach-applications', name: 'app_coach_applications')]
    public function listApplications(Request $request, EntityManagerInterface $entityManager): Response
    {
        $searchQuery = $request->query->get('q');
        $demandes = $entityManager->getRepository(Demande::class)->searchByGameOrUser($searchQuery)->getResult();
        return $this->render('coach/list_applications.html.twig', [
            'demandes' => $demandes,
            'searchQuery' => $searchQuery,
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

        // Send email to the user
        $logoUrl = "https://i.postimg.cc/wxcZgCYH/level.png";
        $emailMessage = (new Email())
            ->from('levelopcorporation@gmail.com')
            ->to($user->getEmail())
            ->subject('Félicitations ! Vous êtes maintenant Coach sur LevelOP')
            ->html(
                "<html>" .
                    "<body style='background-color: #1B1B1B; color: #ffffff; font-family: Arial, sans-serif; text-align: center; padding: 20px;'>" .
                    "<div style='max-width: 500px; margin: auto; background-color: #2A2A2A; padding: 20px; border-radius: 10px;'>" .
                    "<img src='" . $logoUrl . "' alt='Logo LevelOP' style='max-width: 150px; margin-bottom: 10px;'>" .
                    "<h2 style='color: #4CAF50;'>Félicitations, " . htmlspecialchars($user->getPrenom()) . " !</h2>" .
                    "<p style='font-size: 16px;'>Votre demande pour devenir coach a été acceptée.<br>Vous êtes officiellement coach sur LevelOP !</p>" .
                    "<p style='font-size: 16px;'>Vous pouvez maintenant ajouter vos licences et commencer à coacher des joueurs.</p>" .
                    "<hr style='border: 1px solid #444;'>" .
                    "<p style='font-size: 12px; color: #777;'>Cordialement, <br> L'équipe LevelOP</p>" .
                    "</div>" .
                    "</body>" .
                    "</html>"
            );
        $this->mailer->send($emailMessage);

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