<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\StockRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StaffDashboardController extends AbstractController
{
    #[Route('/staff', name: 'staff_dashboard')]
    public function index(
        OrderRepository $orderRepository,
        ProductRepository $productRepository,
        StockRepository $stockRepository,
        UserRepository $userRepository
    ): Response {
        $todayOrders = $orderRepository->getTodaysOrders();
        $pendingOrders = $orderRepository->findByStatus('pending');
        $lowStockProducts = $stockRepository->findLowStock();
        $recentOrders = $orderRepository->findRecentOrders(10);

        return $this->render('dashboard/staff/index.html.twig', [
            'todayOrders' => $todayOrders,
            'pendingOrders' => $pendingOrders,
            'lowStockProducts' => $lowStockProducts,
            'recentOrders' => $recentOrders,
        ]);
    }
}