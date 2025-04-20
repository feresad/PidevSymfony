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

class AdminController extends AbstractController
{
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
            'searchQuery' => $searchQuery
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
        
        if ($duration > 0) {
            $banTime = new \DateTime();
            $banTime->modify("+{$duration} days");
            $user->setBanTime($banTime);
        } else {
            // Permanent ban
            $user->setBanTime(null);
        }

        $entityManager->flush();

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