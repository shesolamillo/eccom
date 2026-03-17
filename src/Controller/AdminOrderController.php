<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use App\Form\OrderFormType;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/admin/orders')]
class AdminOrderController extends AbstractController
{
    #[Route('/new', name: 'admin_order_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): Response {
        // Check if user has admin access
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $order = new Order();
        $order->setCreatedAt(new \DateTimeImmutable());
        $order->setStatus(Order::STATUS_PENDING);
        
        // Set default values
        $order->setDeliveryType(Order::DELIVERY_PICKUP);
        $order->setPaymentMethod(Order::PAYMENT_CASH);
        $order->setIsPaid(false);
        
        // Generate order number
        $order->setOrderNumber('ORD-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT));
        
        // Create form
        $form = $this->createForm(OrderFormType::class, $order, [
            'method' => 'POST',
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Set created by
                $order->setCreatedBy($this->getUser());
                
                // Calculate initial total
                $order->setTotalAmount(0);
                
                $entityManager->persist($order);
                $entityManager->flush();

                $this->addFlash('success', 'Order created successfully! You can now add products.');
                
                return $this->redirectToRoute('admin_order_manage', ['id' => $order->getId()]);
                
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error creating order: ' . $e->getMessage());
            }
        }

        // Get all active users for customer selection
        $customers = $userRepository->findBy(['isActive' => true], ['firstName' => 'ASC']);

        return $this->render('admin/order/new.html.twig', [
            'form' => $form->createView(),
            'customers' => $customers,
        ]);
    }

    #[Route('/{id}/manage', name: 'admin_order_manage', methods: ['GET', 'POST'])]
    public function manage(
        Order $order, 
        Request $request, 
        EntityManagerInterface $entityManager,
        ProductRepository $productRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Handle status updates via POST
        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');
            $notes = $request->request->get('notes', '');

            switch ($action) {
                case 'accept':
                    $order->setStatus(Order::STATUS_ACCEPTED);
                    $order->setProcessedBy($this->getUser());
                    $order->setNotes($notes);
                    $this->addFlash('success', 'Order accepted successfully.');
                    break;
                
                case 'decline':
                    $order->setStatus(Order::STATUS_DECLINED);
                    $order->setProcessedBy($this->getUser());
                    $order->setNotes($notes);
                    $this->addFlash('success', 'Order declined successfully.');
                    break;
                
                case 'process':
                    $order->setStatus(Order::STATUS_PROCESSING);
                    $order->setProcessedBy($this->getUser());
                    $order->setNotes($notes);
                    $this->addFlash('success', 'Order marked as processing.');
                    break;
                
                case 'complete':
                    $order->setStatus(Order::STATUS_COMPLETED);
                    $order->setProcessedBy($this->getUser());
                    $order->setNotes($notes);
                    $order->setCompletedAt(new \DateTimeImmutable());
                    $this->addFlash('success', 'Order marked as completed.');
                    break;
                
                case 'cancel':
                    $order->setStatus(Order::STATUS_CANCELLED);
                    $order->setProcessedBy($this->getUser());
                    $order->setNotes($notes);
                    $this->addFlash('success', 'Order cancelled.');
                    break;
            }

            $entityManager->flush();
            
            return $this->redirectToRoute('admin_order_manage', ['id' => $order->getId()]);
        }

        // Get available products (only active ones)
        $availableProducts = $productRepository->findBy(['isAvailable' => true], ['name' => 'ASC']);

        return $this->render('admin/order/manage.html.twig', [
            'order' => $order,
            'availableProducts' => $availableProducts,
        ]);
    }

    #[Route('/{id}/add-item', name: 'admin_order_add_item', methods: ['POST'])]
    public function addItem(
        Order $order, 
        Request $request, 
        EntityManagerInterface $entityManager,
        ProductRepository $productRepository
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Check CSRF token
        $submittedToken = $request->headers->get('X-CSRF-TOKEN');
        if (!$this->isCsrfTokenValid('admin-add-item', $submittedToken)) {
            return $this->json(['success' => false, 'message' => 'Invalid CSRF token'], 403);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return $this->json(['success' => false, 'message' => 'Invalid JSON data'], 400);
        }

        $productId = $data['productId'] ?? null;
        $quantity = (int) ($data['quantity'] ?? 1);
        $price = (float) ($data['price'] ?? 0);

        if (!$productId) {
            return $this->json(['success' => false, 'message' => 'Product ID is required'], 400);
        }

        if ($quantity <= 0) {
            return $this->json(['success' => false, 'message' => 'Quantity must be greater than 0'], 400);
        }

        $product = $productRepository->find($productId);
        if (!$product) {
            return $this->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        // Check if product already exists in order
        foreach ($order->getOrderItems() as $existingItem) {
            if ($existingItem->getProduct()->getId() === $product->getId()) {
                return $this->json([
                    'success' => false, 
                    'message' => 'Product already exists in order. Please adjust quantity instead.'
                ], 400);
            }
        }

        try {
            $orderItem = new OrderItem();
            $orderItem->setProduct($product);
            $orderItem->setQuantity($quantity);
            $orderItem->setUnitPrice($price > 0 ? $price : $product->getPrice());
            $orderItem->setOrderRef($order);
            $orderItem->calculateTotal();
            
            $entityManager->persist($orderItem);
            $order->addOrderItem($orderItem);
            
            // Update order total
            $order->calculateTotal();
            
            $entityManager->flush();

            // Prepare response data
            $itemData = [
                'id' => $orderItem->getId(),
                'productId' => $product->getId(),
                'productName' => $product->getName(),
                'quantity' => $orderItem->getQuantity(),
                'price' => $orderItem->getUnitPrice(),
                'totalPrice' => $orderItem->getTotalPrice(),
                'stockQuantity' => $product->getStock() ? $product->getStock()->getQuantity() : 0
            ];

            return $this->json([
                'success' => true, 
                'message' => 'Item added successfully',
                'item' => $itemData,
                'orderTotal' => $order->getTotalAmount()
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false, 
                'message' => 'Error adding item: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}/update-status', name: 'admin_order_update_status', methods: ['POST'])]
    public function updateStatus(
        Order $order, 
        Request $request, 
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $data = json_decode($request->getContent(), true);
        $status = $data['status'] ?? null;
        
        $validStatuses = [
            Order::STATUS_PENDING,
            Order::STATUS_ACCEPTED,
            Order::STATUS_PROCESSING,
            Order::STATUS_COMPLETED,
            Order::STATUS_CANCELLED,
            Order::STATUS_DECLINED
        ];
        
        if (!in_array($status, $validStatuses)) {
            return $this->json(['success' => false, 'message' => 'Invalid status'], 400);
        }
        
        $order->setStatus($status);
        $order->setProcessedBy($this->getUser());
        
        if ($status === Order::STATUS_COMPLETED) {
            $order->setCompletedAt(new \DateTimeImmutable());
        }
        
        $entityManager->flush();
        
        return $this->json(['success' => true, 'message' => 'Status updated successfully']);
    }

    #[Route('/{id}/update-payment', name: 'admin_order_update_payment', methods: ['POST'])]
    public function updatePayment(
        Order $order, 
        Request $request, 
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $data = json_decode($request->getContent(), true);
        $isPaid = $data['isPaid'] ?? false;
        
        $order->setIsPaid($isPaid);
        
        if ($isPaid) {
            $order->setPaidAt(new \DateTimeImmutable());
        } else {
            $order->setPaidAt(null);
        }
        
        $entityManager->flush();
        
        return $this->json(['success' => true, 'message' => 'Payment status updated']);
    }

    #[Route('/item/{id}/update-quantity', name: 'admin_order_item_update_quantity', methods: ['POST'])]
    public function updateItemQuantity(
        OrderItem $orderItem, 
        Request $request, 
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $data = json_decode($request->getContent(), true);
        $change = $data['change'] ?? 0;
        
        $newQuantity = $orderItem->getQuantity() + $change;
        
        if ($newQuantity < 1) {
            return $this->json(['success' => false, 'message' => 'Quantity must be at least 1'], 400);
        }
        
        // Check stock availability
        $product = $orderItem->getProduct();
        if ($product->getStock() && $product->getStock()->getQuantity() < $newQuantity) {
            return $this->json([
                'success' => false, 
                'message' => sprintf('Insufficient stock. Available: %d', $product->getStock()->getQuantity())
            ], 400);
        }
        
        $orderItem->setQuantity($newQuantity);
        $orderItem->calculateTotal();
        
        // Update order total
        $order = $orderItem->getOrderRef();
        $order->calculateTotal();
        
        $entityManager->flush();
        
        return $this->json([
            'success' => true, 
            'message' => 'Quantity updated',
            'newQuantity' => $newQuantity,
            'itemTotal' => $orderItem->getTotalPrice(),
            'orderTotal' => $order->getTotalAmount()
        ]);
    }

    #[Route('/item/{id}/remove', name: 'admin_order_item_remove', methods: ['DELETE'])]
    public function removeItem(
        OrderItem $orderItem, 
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $order = $orderItem->getOrderRef();
        $entityManager->remove($orderItem);
        
        // Update order total
        $order->calculateTotal();
        
        $entityManager->flush();
        
        return $this->json([
            'success' => true, 
            'message' => 'Item removed',
            'orderTotal' => $order->getTotalAmount()
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_order_delete', methods: ['DELETE'])]
    public function delete(
        Order $order, 
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Only allow deletion of pending orders
        if ($order->getStatus() !== Order::STATUS_PENDING) {
            return $this->json([
                'success' => false, 
                'message' => 'Only pending orders can be deleted'
            ], 403);
        }
        
        $entityManager->remove($order);
        $entityManager->flush();
        
        return $this->json(['success' => true, 'message' => 'Order deleted successfully']);
    }

    #[Route('', name: 'admin_orders')]
    public function index(OrderRepository $orderRepository, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $status = $request->query->get('status');
        $dateRange = $request->query->get('date_range');
        $search = $request->query->get('search');
        
        $queryBuilder = $orderRepository->createQueryBuilder('o')
            ->leftJoin('o.customer', 'c')
            ->addSelect('c')
            ->orderBy('o.createdAt', 'DESC');
        
        if ($status) {
            $queryBuilder->andWhere('o.status = :status')
                ->setParameter('status', $status);
        }
        
        if ($dateRange) {
            $now = new \DateTime();
            switch ($dateRange) {
                case 'today':
                    $startDate = new \DateTime('today');
                    $endDate = new \DateTime('tomorrow');
                    break;
                case 'week':
                    $startDate = new \DateTime('monday this week');
                    $endDate = new \DateTime('monday next week');
                    break;
                case 'month':
                    $startDate = new \DateTime('first day of this month');
                    $endDate = new \DateTime('first day of next month');
                    break;
                default:
                    $startDate = null;
                    $endDate = null;
            }
            
            if ($startDate && $endDate) {
                $queryBuilder->andWhere('o.createdAt >= :startDate AND o.createdAt < :endDate')
                    ->setParameter('startDate', $startDate)
                    ->setParameter('endDate', $endDate);
            }
        }
        
        if ($search) {
            $queryBuilder->andWhere('o.orderNumber LIKE :search OR c.firstName LIKE :search OR c.lastName LIKE :search OR c.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        
        $orders = $queryBuilder->getQuery()->getResult();
        
        // Get order counts
        $totalOrders = $orderRepository->count([]);
        $pendingOrders = $orderRepository->count(['status' => Order::STATUS_PENDING]);
        $processingOrders = $orderRepository->count(['status' => Order::STATUS_PROCESSING]);
        $completedOrders = $orderRepository->count(['status' => Order::STATUS_COMPLETED]);
        
        return $this->render('admin/order/index.html.twig', [
            'orders' => $orders,
            'totalOrders' => $totalOrders,
            'pendingOrders' => $pendingOrders,
            'processingOrders' => $processingOrders,
            'completedOrders' => $completedOrders,
        ]);
    }
}