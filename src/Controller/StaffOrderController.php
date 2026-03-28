<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use App\Entity\ClothesCategory;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
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
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        ProductRepository $productRepository
    ): Response {
        // Check if user has staff access
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        // Handle POST request (cart-based order creation)
        if ($request->isMethod('POST') && !str_starts_with($request->headers->get('content-type', ''), 'multipart/form-data')) {
            return $this->handleCartOrderCreation($request, $entityManager, $userRepository, $productRepository);
        }

        // GET request - display form
        $customers = $userRepository->findBy(['isActive' => true], ['firstName' => 'ASC']);
        $products = $productRepository->findBy(['isAvailable' => true], ['name' => 'ASC']);
        $categories = $entityManager->getRepository(ClothesCategory::class)->findAll();

        return $this->render('staff/order/new.html.twig', [
            'customers' => $customers,
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    /**
     * Handle cart-based order creation from AJAX
     */
    private function handleCartOrderCreation(
    Request $request,
    EntityManagerInterface $entityManager,
    UserRepository $userRepository,
    ProductRepository $productRepository
): Response {
    try {
        $data = $request->request->all();

        // --- CSRF validation ---
        $submittedToken = $data['_csrf_token'] ?? '';
        if (!$this->isCsrfTokenValid('order_create', $submittedToken)) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], 403);
        }

        // Validate required fields
        if (empty($data['customer_id'])) {
            return new JsonResponse(['error' => 'Customer required'], 400);
        }

        // Get customer
        $customer = $userRepository->find($data['customer_id']);
        if (!$customer) {
            return new JsonResponse(['error' => 'Customer not found'], 404);
        }

        // Validate items
        $items = $data['items'] ?? [];
        if (empty($items)) {
            return new JsonResponse(['error' => 'No items in order'], 400);
        }

        $entityManager->beginTransaction(); // Start transaction

        // Create order
        $order = new Order();
        $order->setOrderNumber('ORD-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT));
        $order->setCustomer($customer);
        $order->setCreatedAt(new \DateTimeImmutable());
        $order->setCreatedBy($this->getUser());
        $order->setStatus(Order::STATUS_PENDING);
        $order->setDeliveryType($data['delivery_type'] ?? Order::DELIVERY_PICKUP);
        $order->setPaymentMethod($data['payment_method'] ?? Order::PAYMENT_CASH);
        $order->setIsPaid(false);
        $order->setIsUrgent($data['is_urgent'] ? true : false);

        // --- Address combination ---
        if ($order->getDeliveryType() === Order::DELIVERY_DELIVERY) {
            $recipient = $data['recipient_name'] ?? '';
            $street = $data['street_address'] ?? '';
            $city = $data['city'] ?? '';
            $postal = $data['postal_code'] ?? '';

            // Basic validation
            if (empty($recipient) || empty($street) || empty($city) || empty($postal)) {
                return new JsonResponse(['error' => 'All delivery address fields are required'], 400);
            }

            $addressParts = array_filter([$recipient, $street, $city, $postal]);
            $deliveryAddress = implode(', ', $addressParts);
            $order->setDeliveryAddress($deliveryAddress);
            $order->setDeliveryFee((float)($data['delivery_fee'] ?? 100));
        } else {
            $order->setDeliveryFee(0);
        }

        $totalAmount = 0;

        // Add items to order and deduct stock
        foreach ($items as $item) {
            $product = $productRepository->find($item['product_id']);
            if (!$product) {
                continue;
            }

            $quantity = (int)$item['quantity'];

            // Validate stock
            if (!$product->getStock() || $product->getStock()->getQuantity() < $quantity) {
                return new JsonResponse([
                    'error' => $product->getName() . ' has insufficient stock'
                ], 400);
            }

            // Create order item
            $orderItem = new OrderItem();
            $orderItem->setProduct($product);
            $orderItem->setQuantity($quantity);
            $orderItem->setUnitPrice($product->getPrice());
            $orderItem->setTotal($product->getPrice() * $quantity);

            $order->addOrderItem($orderItem);
            $totalAmount += $orderItem->getTotal();

            // --- Deduct stock ---
            $stock = $product->getStock();
            $stock->subtractQuantity($quantity);
            $entityManager->persist($stock);
        }

        // Set total
        $order->setTotal($totalAmount);

        // Persist order
        $entityManager->persist($order);
        $entityManager->flush();  // flush order and stock updates
        $entityManager->commit(); // commit transaction

        return new JsonResponse([
            'success' => true,
            'message' => 'Order created successfully',
            'orderId' => $order->getId(),
            'redirectUrl' => $this->generateUrl('admin_order_manage', ['id' => $order->getId()])
        ]);

    } catch (\Exception $e) {
        if ($entityManager->getConnection()->isTransactionActive()) {
            $entityManager->rollback();
        }
        return new JsonResponse([
            'error' => 'Error creating order: ' . $e->getMessage()
        ], 500);
    }
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