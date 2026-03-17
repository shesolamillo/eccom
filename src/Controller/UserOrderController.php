<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Form\OrderType;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserOrderController extends AbstractController
{
    #[Route('/my-orders', name: 'app_user_orders')]
    public function index(OrderRepository $orderRepository): Response
    {
        $user = $this->getUser();
        $orders = $orderRepository->findByCustomer($user);

        return $this->render('order/user/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/order/create', name: 'app_user_order_create')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ProductRepository $productRepository
    ): Response {
        $order = new Order();
        $order->setCustomer($this->getUser());

        // Add example item for form
        $orderItem = new OrderItem();
        $order->addOrderItem($orderItem);

        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $order->calculateTotal();
            $entityManager->persist($order);
            $entityManager->flush();

            $this->addFlash('success', 'Order created successfully!');

            return $this->redirectToRoute('app_user_order_show', ['id' => $order->getId()]);
        }

        $products = $productRepository->findAllAvailable();

        return $this->render('order/user/new.html.twig', [
            'order' => $order,
            'form' => $form->createView(),
            'products' => $products,
        ]);
    }

    #[Route('/order/{id}', name: 'app_user_order_show')]
    public function show(Order $order): Response
    {
        $this->denyAccessUnlessGranted('VIEW', $order);

        return $this->render('order/user/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/order/{id}/cancel', name: 'app_user_order_cancel')]
    public function cancel(Order $order, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('EDIT', $order);

        if ($order->getStatus() === Order::STATUS_PENDING) {
            $order->setStatus(Order::STATUS_CANCELLED);
            $entityManager->flush();

            $this->addFlash('success', 'Order cancelled successfully.');
        } else {
            $this->addFlash('error', 'Cannot cancel order in current status.');
        }

        return $this->redirectToRoute('app_user_order_show', ['id' => $order->getId()]);
    }
}