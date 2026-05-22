<?php

namespace App\Controller;


use App\Entity\ActivityLog;
use App\Repository\ActivityLogRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;


class AdminActivityLogController extends AbstractController
{
    #[Route('/admin/activity-logs', name: 'admin_activity_logs')]
    public function index(
        Request $request,
        ActivityLogRepository $activityLogRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Get filter parameters
        $dateRange = $request->query->get('date_range', 'week');
        $userId = $request->query->get('user');
        $actionType = $request->query->get('action_type');
        $page = $request->query->getInt('page', 1);
        $limit = 20;
        
        $queryBuilder = $activityLogRepository->createQueryBuilder('a')
            ->leftJoin('a.user', 'u')
            ->addSelect('u')
            ->orderBy('a.createdAt', 'DESC');
        
        // Apply date filter
        $startDate = $this->getStartDate($dateRange);
        if ($startDate) {
            $queryBuilder->andWhere('a.createdAt >= :startDate')
                ->setParameter('startDate', $startDate);
        }
        
        // Apply user filter
        if ($userId) {
            $queryBuilder->andWhere('a.user = :userId')
                ->setParameter('userId', $userId);
        }
        
        // Apply action filter
        if ($actionType) {
            $queryBuilder->andWhere('a.action = :actionType')
                ->setParameter('actionType', $actionType);
        }
        
        // Get total count
        $totalLogs = count($queryBuilder->getQuery()->getResult());

        if ($totalLogs == 0) {
            $testLog = new ActivityLog();
            $testLog->setUser($this->getUser());
            $testLog->setAction(ActivityLog::ACTION_LOGIN);
            $testLog->setEntity('User');
            $testLog->setEntityId($this->getUser() ? $this->getUser()->getId() : null);
            $testLog->setDescription('First system login');
            $testLog->setIpAddress($request->getClientIp());
            $testLog->setUserAgent($request->headers->get('User-Agent'));
            $testLog->setCreatedAt(new \DateTimeImmutable());
            
           
            $entityManager->persist($testLog);
            $entityManager->flush();
            
            // Refresh logs
            return $this->redirectToRoute('admin_activity_logs');
        }




        $totalPages = ceil($totalLogs / $limit);
        
        // Get paginated logs
        $logs = $queryBuilder->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
        
        // Get statistics
        $todayCount = $activityLogRepository->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.createdAt >= :today')
            ->setParameter('today', new \DateTime('today'))
            ->getQuery()
            ->getSingleScalarResult();
        
        $errorCount = 0; // You can implement error logging if needed
        
        // Get active users (logged in last 7 days)
        $activeUsers = $activityLogRepository->createQueryBuilder('a')
            ->select('COUNT(DISTINCT a.user)')
            ->where('a.action = :action')
            ->andWhere('a.createdAt >= :lastWeek')
            ->setParameter('action', ActivityLog::ACTION_LOGIN)
            ->setParameter('lastWeek', new \DateTime('-7 days'))
            ->getQuery()
            ->getSingleScalarResult();
        
        // Get activity summary for chart
        $activitySummary = $this->getActivitySummary($activityLogRepository);
        
        // Get all users for filter dropdown
        $allUsers = $userRepository->findAll();
        
        return $this->render('admin/activity_logs/index.html.twig', [
            'logs' => $logs,
            'totalLogs' => $totalLogs,
            'todayCount' => $todayCount,
            'errorCount' => $errorCount,
            'activeUsers' => $activeUsers,
            'allUsers' => $allUsers,
            'activitySummary' => $activitySummary,
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);
    }
    
    private function getStartDate(string $dateRange): ?\DateTime
    {
        return match($dateRange) {
            'today' => new \DateTime('today'),
            'week' => new \DateTime('-7 days'),
            'month' => new \DateTime('-30 days'),
            'all' => null,
            default => new \DateTime('-7 days'),
        };
    }
    
    private function getActivitySummary(ActivityLogRepository $repository): array
    {
        $dates = [];
        $logins = [];
        $updates = [];
        $creates = [];
        
        for ($i = 29; $i >= 0; $i--) {
            $date = new \DateTime("-$i days");
            $dates[] = $date->format('M d');
            
            $start = (clone $date)->setTime(0, 0, 0);
            $end = (clone $date)->setTime(23, 59, 59);
            
            $logins[] = $repository->createQueryBuilder('a')
                ->select('COUNT(a.id)')
                ->where('a.action = :action')
                ->andWhere('a.createdAt BETWEEN :start AND :end')
                ->setParameter('action', ActivityLog::ACTION_LOGIN)
                ->setParameter('start', $start)
                ->setParameter('end', $end)
                ->getQuery()
                ->getSingleScalarResult();
                
            $updates[] = $repository->createQueryBuilder('a')
                ->select('COUNT(a.id)')
                ->where('a.action = :action')
                ->andWhere('a.createdAt BETWEEN :start AND :end')
                ->setParameter('action', ActivityLog::ACTION_UPDATE)
                ->setParameter('start', $start)
                ->setParameter('end', $end)
                ->getQuery()
                ->getSingleScalarResult();
                
            $creates[] = $repository->createQueryBuilder('a')
                ->select('COUNT(a.id)')
                ->where('a.action = :action')
                ->andWhere('a.createdAt BETWEEN :start AND :end')
                ->setParameter('action', ActivityLog::ACTION_CREATE)
                ->setParameter('start', $start)
                ->setParameter('end', $end)
                ->getQuery()
                ->getSingleScalarResult();
        }
        
        return [
            'dates' => $dates,
            'logins' => $logins,
            'updates' => $updates,
            'creates' => $creates,
        ];
    }
    
    #[Route('/admin/activity-log/{id}/details', name: 'admin_activity_log_details')]
    public function details(int $id, ActivityLogRepository $repository): Response
    {
        $log = $repository->find($id);
        
        if (!$log) {
            return $this->json(['error' => 'Log not found'], 404);
        }
        
        return $this->json([
            'id' => $log->getId(),
            'actionType' => $log->getAction(),
            'entityType' => $log->getEntity(),
            'entityId' => $log->getEntityId(),
            'userName' => $log->getUser() ? $log->getUser()->getFirstName() . ' ' . $log->getUser()->getLastName() : 'System',
            'ipAddress' => $log->getIpAddress(),
            'userAgent' => $log->getUserAgent(),
            'description' => $log->getDescription(),
            'changes' => null,
        ]);
    }

    #[Route('/admin/activity-logs/export', name: 'admin_activity_logs_export')]
public function export(Request $request, ActivityLogRepository $activityLogRepository): Response
{
    // Get filter parameters (same as index)
    $dateRange = $request->query->get('date_range', 'week');
    $userId = $request->query->get('user');
    $actionType = $request->query->get('action_type');
    
    $queryBuilder = $activityLogRepository->createQueryBuilder('a')
        ->leftJoin('a.user', 'u')
        ->addSelect('u')
        ->orderBy('a.createdAt', 'DESC');
    
    // Apply date filter
    $startDate = $this->getStartDate($dateRange);
    if ($startDate) {
        $queryBuilder->andWhere('a.createdAt >= :startDate')
            ->setParameter('startDate', $startDate);
    }
    
    // Apply user filter
    if ($userId) {
        $queryBuilder->andWhere('a.user = :userId')
            ->setParameter('userId', $userId);
    }
    
    // Apply action filter
    if ($actionType) {
        $queryBuilder->andWhere('a.action = :actionType')
            ->setParameter('actionType', $actionType);
    }
    
    $logs = $queryBuilder->getQuery()->getResult();
    
    $format = $request->query->get('format', 'csv');
    
    if ($format === 'csv') {
        return $this->exportAsCSV($logs);
    } elseif ($format === 'excel') {
        return $this->exportAsExcel($logs);
    } else {
        return $this->exportAsCSV($logs);
    }
}

private function exportAsCSV(array $logs): Response
{
    $filename = sprintf('activity_logs_%s.csv', (new \DateTimeImmutable())->format('Y-m-d_His'));
    
    $response = new Response();
    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $filename));
    
    $handle = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($handle, ['ID', 'User', 'Action', 'Entity', 'Entity ID', 'Description', 'IP Address', 'User Agent', 'Timestamp']);
    
    // Add data rows
    foreach ($logs as $log) {
        fputcsv($handle, [
            $log->getId(),
            $log->getUser() ? $log->getUser()->getFirstName() . ' ' . $log->getUser()->getLastName() : 'System',
            $log->getAction(),
            $log->getEntity(),
            $log->getEntityId(),
            $log->getDescription(),
            $log->getIpAddress(),
            $log->getUserAgent(),
            $log->getCreatedAt() ? $log->getCreatedAt()->format('Y-m-d H:i:s') : '',
        ]);
    }
    
    fclose($handle);
    
    return $response;
}

private function exportAsExcel(array $logs): Response
{
    $filename = sprintf('activity_logs_%s.xlsx', (new \DateTimeImmutable())->format('Y-m-d_His'));
    
    $response = new Response();
    $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $filename));
    
    // Simple HTML table export for Excel
    $html = '<html><head><meta charset="UTF-8"><title>Activity Logs</title></head><body>';
    $html .= '<table border="1">';
    $html .= '<thead><tr>';
    $html .= '<th>ID</th><th>User</th><th>Action</th><th>Entity</th><th>Entity ID</th><th>Description</th><th>IP Address</th><th>User Agent</th><th>Timestamp</th>';
    $html .= '</tr></thead><tbody>';
    
    foreach ($logs as $log) {
        $html .= '<tr>';
        $html .= sprintf('<td>%d</td>', $log->getId());
        $html .= sprintf('<td>%s</td>', $log->getUser() ? $log->getUser()->getFirstName() . ' ' . $log->getUser()->getLastName() : 'System');
        $html .= sprintf('<td>%s</td>', $log->getAction());
        $html .= sprintf('<td>%s</td>', $log->getEntity());
        $html .= sprintf('<td>%s</td>', $log->getEntityId());
        $html .= sprintf('<td>%s</td>', $log->getDescription());
        $html .= sprintf('<td>%s</td>', $log->getIpAddress());
        $html .= sprintf('<td>%s</td>', $log->getUserAgent());
        $html .= sprintf('<td>%s</td>', $log->getCreatedAt() ? $log->getCreatedAt()->format('Y-m-d H:i:s') : '');
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table></body></html>';
    
    $response->setContent($html);
    
    return $response;
}

#[Route('/admin/activity-logs/clear-old', name: 'admin_activity_logs_clear_old', methods: ['POST'])]
public function clearOldLogs(Request $request, EntityManagerInterface $entityManager): JsonResponse
{

    $csrfToken = $request->request->get('_csrf_token') ?? $request->headers->get('X-CSRF-Token');

    // Check CSRF token
    if (!$this->isCsrfTokenValid('clear_logs', $csrfToken)) {
        return $this->json(['success' => false, 'message' => 'Invalid CSRF token'], 400);
    }
    
    // Get logs older than 30 days
    $thirtyDaysAgo = new \DateTimeImmutable('-30 days');
    
    $logs = $entityManager
        ->getRepository(ActivityLog::class)
        ->createQueryBuilder('a')
        ->where('a.createdAt < :date')
        ->setParameter('date', $thirtyDaysAgo)
        ->getQuery()
        ->getResult();
    
    $count = count($logs);
    
    // Delete old logs
    foreach ($logs as $log) {
        $entityManager->remove($log);
    }
    $entityManager->flush();
    
    return $this->json([
        'success' => true,
        'count' => $count,
        'message' => sprintf('Successfully cleared %d old logs', $count)
    ]);
}

}