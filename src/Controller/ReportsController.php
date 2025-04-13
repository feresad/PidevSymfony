<?php

namespace App\Controller;

use App\Entity\Reports;
use App\Entity\Utilisateur;
use App\Form\ReportType;
use App\Repository\ReportsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class ReportsController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Create a new report
     */
    #[Route('/api/report/create', name: 'report_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'You must be logged in to report.'], 401);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid JSON payload'], 400);
        }

        $this->logger->debug('Received report data', ['data' => $data]);

        if (!isset($data['reportedUserId']) || !is_numeric($data['reportedUserId'])) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid reported user ID'], 400);
        }

        $report = new Reports();
        $form = $this->createForm(ReportType::class, $report);

        // Ensure status is always set
        $data['status'] = $data['status'] ?? 'PENDING';
        $form->submit($data);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $report->setReporterId($user); // Set authenticated user as reporter
                $reportedUser = $entityManager->getRepository(Utilisateur::class)->find($data['reportedUserId']);
                if (!$reportedUser) {
                    $this->logger->warning('Reported user not found', ['reported_user_id' => $data['reportedUserId']]);
                    return new JsonResponse(['success' => false, 'message' => 'Reported user not found'], 404);
                }
                $report->setReportedUserId($reportedUser);

                $this->logger->debug('Report before persist', [
                    'reporter_id' => $user->getId(),
                    'reported_user_id' => $reportedUser->getId(),
                    'reason' => $report->getReason(),
                    'evidence' => $report->getEvidence(),
                    'status' => $report->getStatus(),
                    'created_at' => $report->getCreatedAt()->format('Y-m-d H:i:s'),
                ]);

                $entityManager->persist($report);
                $entityManager->flush();

                $this->logger->info('Report created successfully', [
                    'report_id' => $report->getReportId(),
                    'reporter_id' => $user->getId(),
                    'reported_user_id' => $reportedUser->getId(),
                ]);

                return new JsonResponse(['success' => true, 'message' => 'Report submitted successfully', 'report_id' => $report->getReportId()]);
            } catch (\Exception $e) {
                $this->logger->error('Error creating report', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return new JsonResponse(['success' => false, 'message' => 'Error submitting report: ' . $e->getMessage()], 500);
            }
        }

        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        $this->logger->debug('Form validation failed', ['errors' => $errors]);
        return new JsonResponse(['success' => false, 'message' => 'Invalid form data', 'errors' => $errors], 400);
    }

    /**
     * List all reports (admin only)
     */
    #[Route('/api/report/list', name: 'report_list', methods: ['GET'])]
    public function list(ReportsRepository $reportsRepository): JsonResponse
    {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();
        if (!$user || $user->getPrivilege() !== 'ROLE_ADMIN') {
            return new JsonResponse(['success' => false, 'message' => 'Access denied. Admin privileges required.'], 403);
        }

        $reports = $reportsRepository->findAll();
        $reportData = array_map(function (Reports $report) {
            return [
                'report_id' => $report->getReportId(),
                'reporter_id' => $report->getReporterId()->getId(),
                'reporter_nickname' => $report->getReporterId()->getNickname(),
                'reported_user_id' => $report->getReportedUserId()->getId(),
                'reported_user_nickname' => $report->getReportedUserId()->getNickname(),
                'reason' => $report->getReason(),
                'evidence' => $report->getEvidence(),
                'status' => $report->getStatus(),
                'created_at' => $report->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $reports);

        $this->logger->info('Reports listed', ['count' => count($reportData)]);
        return new JsonResponse(['success' => true, 'reports' => $reportData]);
    }

    /**
     * Get a single report by ID (admin only)
     */
    #[Route('/api/report/{id}', name: 'report_show', methods: ['GET'])]
    public function show(int $id, ReportsRepository $reportsRepository): JsonResponse
    {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();
        if (!$user || $user->getPrivilege() !== 'ROLE_ADMIN') {
            return new JsonResponse(['success' => false, 'message' => 'Access denied. Admin privileges required.'], 403);
        }

        $report = $reportsRepository->find($id);
        if (!$report) {
            $this->logger->warning('Report not found', ['report_id' => $id]);
            return new JsonResponse(['success' => false, 'message' => 'Report not found'], 404);
        }

        $reportData = [
            'report_id' => $report->getReportId(),
            'reporter_id' => $report->getReporterId()->getId(),
            'reporter_nickname' => $report->getReporterId()->getNickname(),
            'reported_user_id' => $report->getReportedUserId()->getId(),
            'reported_user_nickname' => $report->getReportedUserId()->getNickname(),
            'reason' => $report->getReason(),
            'evidence' => $report->getEvidence(),
            'status' => $report->getStatus(),
            'created_at' => $report->getCreatedAt()->format('Y-m-d H:i:s'),
        ];

        $this->logger->info('Report retrieved', ['report_id' => $id]);
        return new JsonResponse(['success' => true, 'report' => $reportData]);
    }

    /**
     * Update a report (admin only)
     */
    #[Route('/api/report/{id}', name: 'report_update', methods: ['PUT'])]
    public function update(int $id, Request $request, ReportsRepository $reportsRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();
        if (!$user || $user->getPrivilege() !== 'ROLE_ADMIN') {
            return new JsonResponse(['success' => false, 'message' => 'Access denied. Admin privileges required.'], 403);
        }

        $report = $reportsRepository->find($id);
        if (!$report) {
            $this->logger->warning('Report not found for update', ['report_id' => $id]);
            return new JsonResponse(['success' => false, 'message' => 'Report not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid JSON payload'], 400);
        }

        $this->logger->debug('Received update data', ['data' => $data]);

        $form = $this->createForm(ReportType::class, $report);
        $form->submit($data, false); // false clears missing fields instead of throwing errors

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if (isset($data['reportedUserId']) && is_numeric($data['reportedUserId'])) {
                    $reportedUser = $entityManager->getRepository(Utilisateur::class)->find($data['reportedUserId']);
                    if (!$reportedUser) {
                        $this->logger->warning('Reported user not found during update', ['reported_user_id' => $data['reportedUserId']]);
                        return new JsonResponse(['success' => false, 'message' => 'Reported user not found'], 404);
                    }
                    $report->setReportedUserId($reportedUser);
                }

                $entityManager->persist($report);
                $entityManager->flush();

                $this->logger->info('Report updated successfully', [
                    'report_id' => $report->getReportId(),
                    'status' => $report->getStatus(),
                ]);

                return new JsonResponse(['success' => true, 'message' => 'Report updated successfully']);
            } catch (\Exception $e) {
                $this->logger->error('Error updating report', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return new JsonResponse(['success' => false, 'message' => 'Error updating report: ' . $e->getMessage()], 500);
            }
        }

        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }
        $this->logger->debug('Form validation failed during update', ['errors' => $errors]);
        return new JsonResponse(['success' => false, 'message' => 'Invalid form data', 'errors' => $errors], 400);
    }

    /**
     * Delete a report (admin only)
     */
    #[Route('/api/report/{id}', name: 'report_delete', methods: ['DELETE'])]
    public function delete(int $id, ReportsRepository $reportsRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();
        if (!$user || $user->getPrivilege() !== 'ROLE_ADMIN') {
            return new JsonResponse(['success' => false, 'message' => 'Access denied. Admin privileges required.'], 403);
        }

        $report = $reportsRepository->find($id);
        if (!$report) {
            $this->logger->warning('Report not found for deletion', ['report_id' => $id]);
            return new JsonResponse(['success' => false, 'message' => 'Report not found'], 404);
        }

        try {
            $entityManager->remove($report);
            $entityManager->flush();

            $this->logger->info('Report deleted successfully', ['report_id' => $id]);
            return new JsonResponse(['success' => true, 'message' => 'Report deleted successfully']);
        } catch (\Exception $e) {
            $this->logger->error('Error deleting report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return new JsonResponse(['success' => false, 'message' => 'Error deleting report: ' . $e->getMessage()], 500);
        }
    }
}