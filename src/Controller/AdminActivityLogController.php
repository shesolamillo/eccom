<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminActivityLogController extends AbstractController
{
    #[Route('/admin/activity-logs', name: 'admin_activity_logs')]
    public function index(
        Request $request,
        ActivityLogRepository $activityLogRepository
    ): Response {
        $page = $request->query->getInt('page', 1);
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $logs = $activityLogRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            $limit,
            $offset
        );

        $totalLogs = $activityLogRepository->count([]);
        $totalPages = ceil($totalLogs / $limit);

        return $this->render('admin/activity_logs/index.html.twig', [
            'logs' => $logs,
            'totalLogs' => $totalLogs, // THIS LINE IS CRITICAL
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'limit' => $limit, // Optional but useful for pagination display
        ]);
    }
}