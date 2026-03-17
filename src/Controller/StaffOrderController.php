<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/staff/orders')]
class StaffOrderController extends AbstractController
{
    #[Route('', name: 'staff_orders')]
    public function index(OrderRepository $orderRepository, Request $request): Response
    {
        $status = $request->query->get('status');
        $dateRange = $request->query->get('date_range');
        $search = $request->query->get('search');
        
        // Build query based on filters
        $queryBuilder = $orderRepository->createQueryBuilder('o')
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
            $queryBuilder->leftJoin('o.customer', 'c')
                ->andWhere('o.orderNumber LIKE :search OR c.firstName LIKE :search OR c.lastName LIKE :search OR c.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        
        $orders = $queryBuilder->getQuery()->getResult();
        
        // Get order counts for stats
        $totalOrders = $orderRepository->count([]);
        $pendingOrders = $orderRepository->count(['status' => Order::STATUS_PENDING]);
        $processingOrders = $orderRepository->count(['status' => Order::STATUS_PROCESSING]);
        $completedOrders = $orderRepository->count(['status' => Order::STATUS_COMPLETED]);
        
        return $this->render('order/staff/index.html.twig', [
            'orders' => $orders,
            'totalOrders' => $totalOrders,
            'pendingOrders' => $pendingOrders,
            'processingOrders' => $processingOrders,
            'completedOrders' => $completedOrders,
        ]);
    }

    #[Route('/new', name: 'staff_order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $order = new Order();
        
        // Create form inline
        $form = $this->createFormBuilder($order)
            ->add('customer', EntityType::class, [
                'label' => 'Customer *',
                'class' => User::class,
                'choice_label' => function(User $user) {
                    return $user->getFullName() . ' (' . $user->getEmail() . ')';
                },
                'placeholder' => 'Select a customer',
                'attr' => ['class' => 'form-select'],
                'required' => true,
            ])
            ->add('deliveryType', ChoiceType::class, [
                'label' => 'Delivery Type *',
                'choices' => [
                    'Pickup' => Order::DELIVERY_PICKUP,
                    'Delivery' => Order::DELIVERY_DELIVERY,
                ],
                'attr' => ['class' => 'form-select'],
                'required' => true,
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'label' => 'Payment Method *',
                'choices' => [
                    'Cash' => Order::PAYMENT_CASH,
                    'Online' => Order::PAYMENT_ONLINE,
                ],
                'attr' => ['class' => 'form-select'],
                'required' => true,
            ])
            ->add('isUrgent', CheckboxType::class, [
                'label' => 'Urgent Order',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
            ])
            ->add('deliveryAddress', TextareaType::class, [
                'label' => 'Delivery Address',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3],
            ])
            ->add('deliveryFee', MoneyType::class, [
                'label' => 'Delivery Fee (₱)',
                'required' => false,
                'currency' => 'PHP',
                'attr' => ['class' => 'form-control'],
                'html5' => true,
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Order Notes',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Special instructions or notes...'],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Continue to Add Products',
                'attr' => ['class' => 'btn btn-primary'],
            ])
            ->getForm();
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Generate order number
            if (!$order->getOrderNumber()) {
                $order->generateOrderNumber();
            }
            
            $entityManager->persist($order);
            $entityManager->flush();
            
            $this->addFlash('success', 'Order created successfully! Now add products.');
            
            return $this->redirectToRoute('staff_order_manage', ['id' => $order->getId()]);
        }
        
        return $this->render('order/staff/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'staff_order_manage', methods: ['GET', 'POST'])]
    public function manage(Order $order, Request $request, EntityManagerInterface $entityManager): Response
    {
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
                    $this->addFlash('success', 'Order marked as completed.');
                    break;
            }

            $entityManager->flush();
        }

        // Get available products for adding items
        $productRepository = $entityManager->getRepository(Product::class);
        $availableProducts = $productRepository->findBy(['isActive' => true], ['name' => 'ASC']);

        return $this->render('order/staff/manage.html.twig', [
            'order' => $order,
            'availableProducts' => $availableProducts,
        ]);
    }

    #[Route('/{id}/status', name: 'staff_order_status', methods: ['POST'])]
    public function updateStatus(Order $order, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $status = $data['status'] ?? null;
        
        if (!in_array($status, [Order::STATUS_PENDING, Order::STATUS_PROCESSING, Order::STATUS_COMPLETED, Order::STATUS_CANCELLED, Order::STATUS_DECLINED])) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid status'], 400);
        }
        
        $order->setStatus($status);
        $order->setProcessedBy($this->getUser());
        
        if ($status === Order::STATUS_COMPLETED) {
            $order->setCompletedAt(new \DateTimeImmutable());
        }
        
        $entityManager->flush();
        
        return new JsonResponse(['success' => true]);
    }

    #[Route('/{id}/payment', name: 'staff_order_payment', methods: ['POST'])]
    public function updatePayment(Order $order, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $isPaid = $data['isPaid'] ?? false;
        
        $order->setIsPaid($isPaid);
        
        if ($isPaid) {
            $order->setPaidAt(new \DateTimeImmutable());
        } else {
            $order->setPaidAt(null);
        }
        
        $entityManager->flush();
        
        return new JsonResponse(['success' => true]);
    }

    #[Route('/{id}/schedule', name: 'staff_order_schedule', methods: ['POST'])]
    public function updateSchedule(Order $order, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $date = new \DateTimeImmutable($data['date'] ?? 'now');
        $type = $data['type'] ?? 'pickup';
        
        if ($type === 'delivery') {
            $order->setDeliveryDate($date);
        } else {
            $order->setPickupDate($date);
        }
        
        $entityManager->flush();
        
        return new JsonResponse(['success' => true]);
    }

    #[Route('/{id}/add-item', name: 'staff_order_add_item', methods: ['POST'])]
    public function addItem(Order $order, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $productId = $data['productId'] ?? null;
        $quantity = $data['quantity'] ?? 1;
        $price = $data['price'] ?? 0;
        
        if (!$productId) {
            return new JsonResponse(['success' => false, 'message' => 'Product ID is required'], 400);
        }
        
        $product = $entityManager->getRepository(Product::class)->find($productId);
        if (!$product) {
            return new JsonResponse(['success' => false, 'message' => 'Product not found'], 404);
        }
        
        // Check stock availability
        if ($product->getStock() && $product->getStock()->getQuantity() < $quantity) {
            return new JsonResponse([
                'success' => false, 
                'message' => 'Insufficient stock. Available: ' . $product->getStock()->getQuantity()
            ], 400);
        }
        
        $orderItem = new OrderItem();
        $orderItem->setProduct($product);
        $orderItem->setQuantity($quantity);
        $orderItem->setPrice($price);
        $orderItem->setOrderRef($order);
        
        $entityManager->persist($orderItem);
        
        // Update order total
        $order->calculateTotal();
        
        $entityManager->flush();
        
        return new JsonResponse(['success' => true]);
    }

    #[Route('/item/{id}/quantity', name: 'staff_order_item_quantity', methods: ['POST'])]
    public function updateItemQuantity(OrderItem $orderItem, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $change = $data['change'] ?? 0;
        
        $newQuantity = $orderItem->getQuantity() + $change;
        
        if ($newQuantity < 1) {
            return new JsonResponse(['success' => false, 'message' => 'Quantity must be at least 1'], 400);
        }
        
        // Check stock availability
        $product = $orderItem->getProduct();
        if ($product->getStock() && $product->getStock()->getQuantity() < $newQuantity) {
            return new JsonResponse([
                'success' => false, 
                'message' => 'Insufficient stock. Available: ' . $product->getStock()->getQuantity()
            ], 400);
        }
        
        $orderItem->setQuantity($newQuantity);
        
        // Update order total
        $order = $orderItem->getOrderRef();
        $order->calculateTotal();
        
        $entityManager->flush();
        
        return new JsonResponse(['success' => true]);
    }

    #[Route('/item/{id}/remove', name: 'staff_order_item_remove', methods: ['DELETE'])]
    public function removeItem(OrderItem $orderItem, EntityManagerInterface $entityManager): JsonResponse
    {
        $order = $orderItem->getOrderRef();
        $entityManager->remove($orderItem);
        
        // Update order total
        $order->calculateTotal();
        
        $entityManager->flush();
        
        return new JsonResponse(['success' => true]);
    }

    #[Route('/{id}/notify', name: 'staff_order_notify', methods: ['POST'])]
    public function sendNotification(Order $order): JsonResponse
    {
        // This is a placeholder - implement actual notification logic
        // You could send email, SMS, or push notification here
        
        return new JsonResponse([
            'success' => true,
            'message' => 'Notification sent to customer'
        ]);
    }

    #[Route('/{id}/delete', name: 'staff_order_delete', methods: ['DELETE'])]
    public function delete(Order $order, EntityManagerInterface $entityManager): JsonResponse
    {
        // Only allow deletion of pending orders or by admin
        if ($order->getStatus() !== Order::STATUS_PENDING && !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Only pending orders can be deleted'
            ], 403);
        }
        
        $entityManager->remove($order);
        $entityManager->flush();
        
        return new JsonResponse(['success' => true]);
    }

    #[Route('/{id}/details', name: 'staff_order_details', methods: ['GET'])]
    public function getOrderDetails(Order $order): JsonResponse
    {
        $items = [];
        foreach ($order->getOrderItems() as $item) {
            $items[] = [
                'productName' => $item->getProduct()->getName(),
                'quantity' => $item->getQuantity(),
                'price' => $item->getPrice(),
                'total' => $item->getTotalPrice()
            ];
        }
        
        $data = [
            'orderNumber' => $order->getOrderNumber(),
            'createdAt' => $order->getCreatedAt()->format('c'),
            'status' => $order->getStatus(),
            'paymentMethod' => $order->getPaymentMethod(),
            'customerName' => $order->getCustomer() ? $order->getCustomer()->getFullName() : 'N/A',
            'customerEmail' => $order->getCustomer() ? $order->getCustomer()->getEmail() : 'N/A',
            'customerPhone' => $order->getCustomer() ? $order->getCustomer()->getPhoneNumber() : 'N/A',
            'customerAddress' => $order->getDeliveryAddress(),
            'items' => $items,
            'subtotal' => $order->getTotalAmount() - $order->getDeliveryFee(),
            'tax' => 0, // Add tax calculation if you have tax
            'shipping' => $order->getDeliveryFee(),
            'total' => $order->getTotalAmount(),
            'notes' => $order->getNotes()
        ];
        
        return new JsonResponse($data);
    }

    #[Route('/{id}/notes', name: 'staff_order_notes', methods: ['POST'])]
    public function updateNotes(Order $order, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $notes = $data['notes'] ?? '';
        
        $order->setNotes($notes);
        $entityManager->flush();
        
        return new JsonResponse(['success' => true]);
    }
    #[Route('/{id}/show', name: 'staff_order_show', methods: ['GET'])]
public function show(Order $order): Response
{
    return $this->render('order/staff/show.html.twig', [
        'order' => $order,
    ]);
}

}