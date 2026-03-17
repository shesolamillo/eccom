<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use App\Repository\StockRepository;
use App\Repository\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminDashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(
        OrderRepository $orderRepository,
        UserRepository $userRepository,
        ProductRepository $productRepository,
        StockRepository $stockRepository
    ): Response {
        $orderStats = $orderRepository->getOrderStatistics();
        $userStats = [
            'total' => $userRepository->count([]),
            'admins' => $userRepository->countByRole('ROLE_ADMIN'),
            'staff' => $userRepository->countByRole('ROLE_STAFF'),
            'users' => $userRepository->countByRole('ROLE_USER'),
        ];
        $productStats = [
            'total' => $productRepository->count([]),
            'available' => count($productRepository->findAllAvailable()),
        ];                                                                                  
        $stockStats = $stockRepository->getStockSummary();

        return $this->render('dashboard/admin/index.html.twig', [
            'orderStats' => $orderStats,
            'userStats' => $userStats,
            'productStats' => $productStats,
            'stockStats' => $stockStats,
        ]);
    }

    #[Route('/admin/analytics', name: 'admin_analytics')]
    public function analytics(
        OrderRepository $orderRepository,
        ActivityLogRepository $activityLogRepository
    ): Response {
        $dailyRevenue = $orderRepository->getDailyRevenue(30);
        $monthlyRevenue = $orderRepository->getMonthlyRevenue(12);
        $activitySummary = $activityLogRepository->getActivitySummary(30);

        return $this->render('dashboard/admin/analytics.html.twig', [
            'dailyRevenue' => $dailyRevenue,
            'monthlyRevenue' => $monthlyRevenue,
            'activitySummary' => $activitySummary,
        ]);
    }
}