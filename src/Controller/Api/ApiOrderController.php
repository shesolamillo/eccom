<?php

namespace App\Controller\Api;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/api/orders', name: 'api_orders_')]
class ApiOrderController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        OrderRepository $orderRepository,
        Request $request
    ): JsonResponse {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);
        $status = $request->query->get('status');
        $userId = $request->query->get('userId');

        $criteria = [];
        if ($status) {
            $criteria['status'] = $status;
        }
        if ($userId) {
            $criteria['user'] = $userId;
        }

        $orders = $orderRepository->findBy($criteria, ['createdAt' => 'DESC']);
        $total = count($orders);
        $orders = array_slice($orders, ($page - 1) * $limit, $limit);

        $data = array_map(function($order) {
            return $this->orderToArray($order);
        }, $orders);

        return $this->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit),
            ]
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(
        $id,
        OrderRepository $orderRepository
    ): JsonResponse {
        $order = $orderRepository->find($id);

        if (!$order) {
            return $this->json([
                'success' => false,
                'error' => 'Order not found'
            ], 404);
        }

        return $this->json([
            'success' => true,
            'data' => $this->orderToArray($order)
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        OrderRepository $orderRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Validate required fields
        if (!isset($data['userId']) || !isset($data['items'])) {
            return $this->json([
                'success' => false,
                'error' => 'Missing required fields: userId, items'
            ], 400);
        }

        $user = $userRepository->find($data['userId']);
        if (!$user) {
            return $this->json([
                'success' => false,
                'error' => 'User not found'
            ], 404);
        }

        $order = new Order();
        $order->setUser($user);
        $order->setStatus('pending');
        $order->setTotalAmount(0);
        $order->setCreatedAt(new \DateTime());

        // Process items (you'll need to implement OrderItem creation)
        // This is a simplified version
        $totalAmount = 0;
        foreach ($data['items'] as $item) {
            // Add items to order
            $totalAmount += (isset($item['price']) ? $item['price'] : 0) * (isset($item['quantity']) ? $item['quantity'] : 0);
        }

        $order->setTotalAmount($totalAmount);
        $entityManager->persist($order);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => $this->orderToArray($order)
        ], 201);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(
        $id,
        Request $request,
        OrderRepository $orderRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $order = $orderRepository->find($id);

        if (!$order) {
            return $this->json([
                'success' => false,
                'error' => 'Order not found'
            ], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['status'])) {
            $order->setStatus($data['status']);
        }

        $order->setUpdatedAt(new \DateTime());
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Order updated successfully',
            'data' => $this->orderToArray($order)
        ]);
    }

    private function orderToArray($order): array
    {
        $items = [];
        foreach ($order->getOrderItems() as $item) {
            $items[] = [
                'id' => $item->getId(),
                'productId' => $item->getProduct()->getId(),
                'productName' => $item->getProduct()->getName(),
                'quantity' => $item->getQuantity(),
                'price' => (float) $item->getPrice(),
                'subtotal' => (float) ($item->getQuantity() * $item->getPrice()),
            ];
        }

        return [
            'id' => $order->getId(),
            'userId' => $order->getUser()->getId(),
            'userName' => $order->getUser()->getName(),
            'status' => $order->getStatus(),
            'totalAmount' => (float) $order->getTotalAmount(),
            'items' => $items,
            'itemCount' => count($items),
            'createdAt' => $order->getCreatedAt() ? $order->getCreatedAt()->format('Y-m-d H:i:s') : null,
            'updatedAt' => $order->getUpdatedAt() ? $order->getUpdatedAt()->format('Y-m-d H:i:s') : null,
        ];
    }
}
