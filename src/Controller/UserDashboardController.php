<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        OrderRepository $orderRepository,
        ProductRepository $productRepository
    ): Response {
        $user = $this->getUser();
        $recentOrders = $orderRepository->findByCustomer($user);
        $popularProducts = $productRepository->findTopSelling(6);

        return $this->render('dashboard/user/index.html.twig', [
            'user' => $user,
            'recentOrders' => $recentOrders,
            'popularProducts' => $popularProducts,
        ]);
    }

    #[Route('/profile', name: 'app_profile')]
    public function profile(): Response
    {
        return $this->redirectToRoute('app_security_profile');
    }
}