<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/cart')]
class CartController extends AbstractController
{
    public function __construct(
        private CartService $cartService,
        private EntityManagerInterface $em
    ) {}

    /**
     * API endpoint to validate product for add-to-cart
     */
    #[Route('/api/validate/{id}', name: 'cart_api_validate', methods: ['GET'])]
    public function validateProduct(Product $product): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'product' => [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'image' => $product->getPhotoUrl(),
                'stock' => $product->getStock()?->getQuantity() ?? 0,
                'available' => $product->isAvailable() && ($product->getStock()?->getQuantity() ?? 0) > 0,
            ]
        ]);
    }

    /**
     * Add product to cart (supports AJAX)
     */
    #[Route('/add/{id}', name: 'cart_add', methods: ['POST'])]
    public function add(Product $product, Request $request): Response
    {
        // Validate stock
        $result = $this->cartService->addItem($product, (int)$request->request->get('quantity', 1));

        // If AJAX request, return JSON
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => $result['success'],
                'message' => $result['message'],
                'count' => $this->cartService->getCartCount(),
            ]);
        }

        // Redirect for regular form submission
        if ($result['success']) {
            $this->addFlash('success', $result['message']);
        } else {
            $this->addFlash('error', $result['message']);
        }

        return $this->redirectToRoute('cart_view');
    }

    /**
     * View cart (User)
     */
    #[Route('', name: 'cart_view', methods: ['GET'])]
    public function view(Request $request): Response
    {
        $items = $this->cartService->getCartItems();
        $validation = $this->cartService->validateCart();

        $subtotal = $this->cartService->getCartTotal();
        $shipping = $this->cartService->calculateShippingFee($subtotal);
        $total = $subtotal + $shipping;

        return $this->render('cart/view.html.twig', [
            'items' => $items,
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total' => $total,
            'cart_issues' => $validation['issues'],
        ]);
    }

    /**
     * Remove item from cart
     */
    #[Route('/remove/{id}', name: 'cart_remove', methods: ['POST', 'DELETE'])]
    public function remove(Product $product, Request $request): Response
    {
        $this->cartService->removeItem($product->getId());

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true,
                'message' => 'Item removed',
                'count' => $this->cartService->getCartCount(),
            ]);
        }

        $this->addFlash('success', 'Item removed from cart');
        return $this->redirectToRoute('cart_view');
    }

    /**
     * Update cart item quantity
     */
    #[Route('/update/{id}', name: 'cart_update_quantity', methods: ['POST', 'PUT'])]
    public function updateQuantity(Product $product, Request $request): Response
    {
        $quantity = (int)$request->request->get('quantity', 1);
        $result = $this->cartService->updateQuantity($product, $quantity);

        if ($request->isXmlHttpRequest()) {
            $items = $this->cartService->getCartItems();
            $subtotal = $this->cartService->getCartTotal();
            $shipping = $this->cartService->calculateShippingFee($subtotal);

            return new JsonResponse([
                'success' => $result['success'],
                'message' => $result['message'],
                'count' => $this->cartService->getCartCount(),
                'subtotal' => $subtotal,
                'shipping' => $shipping,
                'total' => $subtotal + $shipping,
                'item_subtotal' => $quantity * $product->getPrice(),
            ]);
        }

        if ($result['success']) {
            $this->addFlash('success', 'Quantity updated');
        } else {
            $this->addFlash('error', $result['message']);
        }

        return $this->redirectToRoute('cart_view');
    }

    /**
     * Clear entire cart
     */
    #[Route('/clear', name: 'cart_clear', methods: ['POST', 'DELETE'])]
    public function clear(Request $request): Response
    {
        $this->cartService->clearCart();

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => true, 'message' => 'Cart cleared']);
        }

        $this->addFlash('success', 'Cart cleared');
        return $this->redirectToRoute('app_products');
    }

    /**
     * Display multi-step checkout form
     */
    #[Route('/checkout', name: 'cart_checkout_page', methods: ['GET'])]
    public function checkoutPage(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $items = $this->cartService->getCartItems();
        
        if (empty($items)) {
            $this->addFlash('error', 'Your cart is empty');
            return $this->redirectToRoute('app_products');
        }

        $subtotal = $this->cartService->getCartTotal();
        $shipping = $this->cartService->calculateShippingFee($subtotal);
        $total = $subtotal + $shipping;

        return $this->render('cart/checkout.html.twig', [
            'items' => $items,
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total' => $total,
        ]);
    }

    /**
     * Checkout process for regular users (form submission)
     */
   #[Route('/process', name: 'cart_checkout', methods: ['POST'])]
public function checkout(Request $request, EntityManagerInterface $em): Response
{
    $this->denyAccessUnlessGranted('ROLE_USER');

    // --- CSRF validation ---
    $submittedToken = $request->request->get('_csrf_token');
    if (!$this->isCsrfTokenValid('cart_checkout', $submittedToken)) {
        $this->addFlash('error', 'Invalid security token.');
        return $this->redirectToRoute('cart_view');
    }

    $validation = $this->cartService->validateCart();
    if (!$validation['valid']) {
        $this->addFlash('error', 'Cart contains unavailable items. Please review your cart.');
        return $this->redirectToRoute('cart_view');
    }

    if ($this->cartService->isEmpty()) {
        $this->addFlash('error', 'Your cart is empty');
        return $this->redirectToRoute('app_products');
    }

    $items = $this->cartService->getCartItems();
    $user = $this->getUser();

    $deliveryType = $request->request->get('delivery_type', Order::DELIVERY_PICKUP);
    $deliveryAddress = trim($request->request->get('delivery_address', ''));
    $paymentMethod = $request->request->get('payment_method', Order::PAYMENT_CASH);

    if ($deliveryType === Order::DELIVERY_DELIVERY && empty($deliveryAddress)) {
        $this->addFlash('error', 'Delivery address is required for delivery orders.');
        return $this->redirectToRoute('cart_view');
    }

    $em->beginTransaction(); // start transaction

    try {
        // Create order from cart
        $order = new Order();
        $order->setCustomer($user);
        $order->setOrderNumber('ORD-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT));
        $order->setStatus(Order::STATUS_PENDING);
        $order->setCreatedAt(new \DateTimeImmutable());
        $order->setDeliveryType($deliveryType);
        $order->setDeliveryAddress($deliveryType === Order::DELIVERY_DELIVERY ? $deliveryAddress : null);
        $order->setPaymentMethod($paymentMethod);
        $order->setIsPaid($paymentMethod === Order::PAYMENT_ONLINE);

        // Add items to order and deduct stock
        foreach ($items as $item) {
            $product = $item['product'];

            $orderItem = new OrderItem();
            $orderItem->setProduct($product);
            $orderItem->setQuantity($item['quantity']);
            $orderItem->setUnitPrice($item['price']);
            $orderItem->setTotal($item['subtotal']);

            $order->addOrderItem($orderItem);

            // Deduct stock
            $stock = $product->getStock();
            if ($stock) {
                $stock->subtractQuantity($item['quantity']);
                $em->persist($stock);
            }
        }

        // Calculate totals
        $subtotal = $this->cartService->getCartTotal();
        $shipping = $this->cartService->calculateShippingFee($subtotal);
        $order->setDeliveryFee($deliveryType === Order::DELIVERY_DELIVERY ? $shipping : 0);
        $order->setTotal($subtotal + ($deliveryType === Order::DELIVERY_DELIVERY ? $shipping : 0));

        $em->persist($order);
        $em->flush();  // flush order and stock updates
        $em->commit(); // commit transaction

        // Clear cart
        $this->cartService->clearCart();

        $this->addFlash('success', 'Order placed successfully! Please wait for processing.');
        return $this->redirectToRoute('app_user_order_show', ['id' => $order->getId()]);

    } catch (\Exception $e) {
        $em->rollback();
        $this->addFlash('error', 'An error occurred while placing your order. Please try again.');
        return $this->redirectToRoute('cart_view');
    }
}
    /**
     * Get mini cart data (for header badge)
     */
    #[Route('/mini', name: 'cart_mini', methods: ['GET'])]
    public function getMiniCart(): JsonResponse
    {
        return new JsonResponse([
            'count' => $this->cartService->getCartCount(),
            'total' => $this->cartService->getCartTotal(),
            'items_count' => count($this->cartService->getCartItems()),
        ]);
    }

    /**
     * API: Get full cart details (JSON)
     */
    #[Route('/api/details', name: 'api_cart_details', methods: ['GET'])]
    public function getCartDetails(): JsonResponse
    {
        $items = $this->cartService->getCartItems();
        $subtotal = $this->cartService->getCartTotal();
        $shipping = $this->cartService->calculateShippingFee($subtotal);

        return new JsonResponse([
            'items' => array_map(function($item) {
                return [
                    'id' => $item['id'],
                    'name' => $item['product']->getName(),
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['subtotal'],
                    'stock' => $item['stock_available'],
                ];
            }, $items),
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total' => $subtotal + $shipping,
            'count' => $this->cartService->getCartCount(),
        ]);
    }

    /**
     * API: Validate cart stock availability
     */
    #[Route('/api/validate', name: 'api_cart_validate', methods: ['POST'])]
    public function validateCartAPI(): JsonResponse
    {
        $validation = $this->cartService->validateCart();

        return new JsonResponse([
            'valid' => $validation['valid'],
            'issues' => $validation['issues'],
            'cart' => $this->cartService->getCartItems(),
        ]);
    }

    /**
     * API: Apply discount/coupon (for future implementation)
     */
    #[Route('/api/apply-coupon', name: 'api_apply_coupon', methods: ['POST'])]
    public function applyCoupon(Request $request): JsonResponse
    {
        $couponCode = $request->request->get('coupon');

        // Placeholder for coupon validation
        // This would connect to a Coupon entity in the future
        
        return new JsonResponse([
            'success' => false,
            'message' => 'Coupon validation not yet implemented',
        ]);
    }

    /**
     * API: Get cart summary for admin/staff
     */
    #[Route('/api/summary', name: 'api_cart_summary', methods: ['GET'])]
    public function getCartSummary(): JsonResponse
    {
        $items = $this->cartService->getCartItems();
        $subtotal = $this->cartService->getCartTotal();
        $shipping = $this->cartService->calculateShippingFee($subtotal);

        return new JsonResponse([
            'items_count' => count($items),
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total' => $subtotal + $shipping,
            'item_details' => array_map(function($item) {
                return [
                    'name' => $item['product']->getName(),
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'line_total' => $item['subtotal'],
                ];
            }, $items),
        ]);
    }
}
