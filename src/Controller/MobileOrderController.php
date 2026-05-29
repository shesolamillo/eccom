<?php
namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MobileOrderController extends AbstractController
{
    #[Route('/api/mobile/order', name: 'api_mobile_order_create', methods: ['POST'])]
    public function createOrder(
        Request $request,
        EntityManagerInterface $em,
        ProductRepository $productRepo
    ): JsonResponse {
        // Auth: expect Bearer token = base64(id:email)
        $authHeader = $request->headers->get('Authorization', '');
        $token = str_replace('Bearer ', '', $authHeader);
        $decoded = base64_decode($token);
        [$userId] = explode(':', $decoded, 2);

        $user = $em->getRepository(\App\Entity\User::class)->find((int)$userId);
        if (!$user) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $items        = $data['items']        ?? [];
        $deliveryType = $data['delivery_type'] ?? Order::DELIVERY_PICKUP;
        $deliveryAddr = $data['delivery_address'] ?? null;
        $paymentMethod = $data['payment_method'] ?? Order::PAYMENT_CASH;
        $notes        = $data['notes']         ?? null;

        if (empty($items)) {
            return $this->json(['message' => 'No items provided'], 400);
        }

        if ($deliveryType === Order::DELIVERY_DELIVERY && empty($deliveryAddr)) {
            return $this->json(['message' => 'Delivery address required'], 400);
        }

        $em->beginTransaction();
        try {
            $order = new Order();
            $order->setCustomer($user);
            $order->setStatus(Order::STATUS_PENDING);
            $order->setDeliveryType($deliveryType);
            $order->setDeliveryAddress($deliveryType === Order::DELIVERY_DELIVERY ? $deliveryAddr : null);
            $order->setPaymentMethod($paymentMethod);
            $order->setNotes($notes);
            $order->setIsPaid(false);

            $subtotal = 0.0;

            foreach ($items as $itemData) {
                $product = $productRepo->find((int)($itemData['product_id'] ?? 0));
                if (!$product) {
                    $em->rollback();
                    return $this->json(['message' => 'Product not found: ' . ($itemData['product_id'] ?? '?')], 404);
                }

                $qty = max(1, (int)($itemData['quantity'] ?? 1));

                // Stock check
                $stock = $product->getStock();
                if (!$stock || $stock->getQuantity() < $qty) {
                    $em->rollback();
                    return $this->json([
                        'message' => "Insufficient stock for: {$product->getName()}"
                    ], 422);
                }

                $orderItem = new OrderItem();
                $orderItem->setProduct($product);
                $orderItem->setQuantity($qty);
                $orderItem->setUnitPrice($product->getPrice());
                $orderItem->calculateTotal();
                $order->addOrderItem($orderItem);

                // Deduct stock
                $stock->subtractQuantity($qty);
                $em->persist($stock);

                $subtotal += $product->getPrice() * $qty;
            }

            // Shipping fee: free if pickup, flat 50 if delivery (mirror your CartService logic)
            $shippingFee = $deliveryType === Order::DELIVERY_DELIVERY ? 50.0 : 0.0;
            $order->setDeliveryFee($shippingFee);
            $order->setTotalAmount($subtotal + $shippingFee);

            $em->persist($order);
            $em->flush();
            $em->commit();

            return $this->json([
                'success'      => true,
                'message'      => 'Order placed successfully',
                'order_number' => $order->getOrderNumber(),
                'order_id'     => $order->getId(),
                'total'        => $order->getTotalAmount(),
                'status'       => $order->getStatus(),
            ], 201);

        } catch (\Exception $e) {
            $em->rollback();
            return $this->json(['message' => 'Failed to place order: ' . $e->getMessage()], 500);
        }



        
    }

    #[Route('/api/mobile/order/{id}/status', name: 'api_mobile_order_update_status', methods: ['PATCH'])]
    public function updateOrderStatus(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        // Auth: expect Bearer token = base64(id:email)
        $authHeader = $request->headers->get('Authorization', '');
        $token      = str_replace('Bearer ', '', $authHeader);
        $decoded    = base64_decode($token);
        [$userId]   = explode(':', $decoded, 2);

        $user = $em->getRepository(\App\Entity\User::class)->find((int)$userId);
        if (!$user) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        // Only admins can update status
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->json(['message' => 'Forbidden'], 403);
        }

        $order = $em->getRepository(Order::class)->find($id);
        if (!$order) {
            return $this->json(['message' => 'Order not found'], 404);
        }

        $data   = json_decode($request->getContent(), true);
        $status = $data['status'] ?? null;

        $allowed = [
            Order::STATUS_PENDING,
            Order::STATUS_PROCESSING,
            Order::STATUS_COMPLETED,
            Order::STATUS_CANCELLED,
        ];

        if (!$status || !in_array($status, $allowed)) {
            return $this->json(['message' => 'Invalid status value'], 400);
        }

        $order->setStatus($status);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Order status updated',
            'status'  => $order->getStatus(),
        ]);
    }

}
