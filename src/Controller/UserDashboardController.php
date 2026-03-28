<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\ProfileFormType;




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
    public function profile(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $profile = $user->getUserProfile();

        $form = $this->createForm(ProfileFormType::class, $profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($profile);
            $em->flush();

            $this->addFlash('success', 'Profile updated successfully!');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('user/profile.html.twig', [
            'profileForm' => $form->createView(),
        ]);
    }
}