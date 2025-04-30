<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Reports;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class AdminController extends AbstractController
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function dashboard(
        Request $request,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator,
        UtilisateurRepository $utilisateurRepository
    ): Response {
        // Check if user is admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $searchQuery = $request->query->get('q');
        
        // Get query for users based on search
        $query = $utilisateurRepository->searchByNickname($searchQuery);

        // Paginate the results
        $users = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/dashboard.html.twig', [
            'users' => $users,
            'searchQuery' => $searchQuery,
            'image_base_url' => $this->getParameter('image_base_url'),
        ]);
    }

    #[Route('/admin/user/{id}/reports', name: 'admin_user_reports', methods: ['GET'])]
    public function getUserReports(Utilisateur $user, EntityManagerInterface $entityManager): JsonResponse
    {
        $reports = $entityManager->getRepository(Reports::class)->findBy([
            'reportedUserId' => $user
        ]);

        $reportsData = array_map(function($report) {
            return [
                'reason' => $report->getReason(),
                'status' => $report->getStatus(),
                'evidence' => $report->getEvidence(),
                'created_at' => $report->getCreatedAt()->format('Y-m-d H:i:s'),
                'reporter' => [
                    'id' => $report->getReporterId()->getId(),
                    'name' => $report->getReporterId()->getPrenom() . ' ' . $report->getReporterId()->getNom(),
                    'nickname' => $report->getReporterId()->getNickname()
                ]
            ];
        }, $reports);

        return new JsonResponse($reportsData);
    }

    #[Route('/admin/user/{id}/ban', name: 'admin_user_ban', methods: ['POST'])]
    public function banUser(Utilisateur $user, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $duration = $data['duration'] ?? 0;

        $user->setBan(true);
        $banEnd = null;
        if ($duration > 0) {
            $banTime = new \DateTime();
            $banTime->modify("+{$duration} days");
            $user->setBanTime($banTime);
            $banEnd = $banTime;
        } else {
            // Permanent ban
            $user->setBanTime(null);
        }

        $entityManager->flush();

        // Send ban email
        $logoUrl = "https://i.postimg.cc/wxcZgCYH/level.png";
        $banMessage = $banEnd
            ? "Votre compte a été banni jusqu'au <b>" . $banEnd->format('d/m/Y H:i') . "</b>."
            : "Votre compte a été banni de façon permanente.";
        $emailMessage = (new Email())
            ->from('levelopcorporation@gmail.com')
            ->to($user->getEmail())
            ->subject('Notification de bannissement')
            ->html(
                "<html>" .
                    "<body style='background-color: #1B1B1B; color: #ffffff; font-family: Arial, sans-serif; text-align: center; padding: 20px;'>" .
                    "<div style='max-width: 500px; margin: auto; background-color: #2A2A2A; padding: 20px; border-radius: 10px;'>" .
                    "<img src='" . $logoUrl . "' alt='Logo LevelOP' style='max-width: 150px; margin-bottom: 10px;'>" .
                    "<h2 style='color: #ff4444;'>Bannissement de votre compte</h2>" .
                    "<p style='font-size: 16px;'>" . $banMessage . "</p>" .
                    "<p style='font-size: 14px; color: #aaaaaa;'>Si vous pensez qu'il s'agit d'une erreur, veuillez contacter le support.</p>" .
                    "<hr style='border: 1px solid #444;'>" .
                    "<p style='font-size: 12px; color: #777;'>Cordialement, <br> L'équipe LevelOP</p>" .
                    "</div>" .
                    "</body>" .
                    "</html>"
            );
        $this->mailer->send($emailMessage);

        return new JsonResponse(['success' => true]);
    }

    #[Route('/admin/user/{id}/unban', name: 'admin_user_unban', methods: ['POST'])]
    public function unbanUser(Utilisateur $user, EntityManagerInterface $entityManager): JsonResponse
    {
        $user->setBan(false);
        $user->setBanTime(null);
        
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}