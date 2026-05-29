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
            Order::STATUS_ACCEPTED,   // ← added
            Order::STATUS_DECLINED,   // ← added
        ];

        if (!$status || !in_array($status, $allowed)) {
            return $this->json(['message' => 'Invalid status value'], 400);
        }

        $order->setStatus($status);
        $em->flush();

        $customer     = $order->getCustomer();
        $deviceTokens = $em->getRepository(\App\Entity\DeviceToken::class)
            ->findBy(['user' => $customer]);

        foreach ($deviceTokens as $dt) {
            $this->sendFcmPushV1(
                $dt->getToken(),
                '📦 Order Status Updated',
                "Your order #{$order->getOrderNumber()} is now " . strtoupper($status),
                (string) $order->getId()
            );
        }

        return $this->json([
            'success' => true,
            'message' => 'Order status updated',
            'status'  => $order->getStatus(),
        ]);
    }

    #[Route('/api/mobile/save-device-token', name: 'api_mobile_save_device_token', methods: ['POST'])]
public function saveDeviceToken(
    Request $request,
    EntityManagerInterface $em
): JsonResponse {
    $authHeader = $request->headers->get('Authorization', '');
    $token      = str_replace('Bearer ', '', $authHeader);
    $decoded    = base64_decode($token);
    [$userId]   = explode(':', $decoded, 2);

    $user = $em->getRepository(\App\Entity\User::class)->find((int)$userId);
    if (!$user) {
        return $this->json(['message' => 'Unauthorized'], 401);
    }

    $data     = json_decode($request->getContent(), true);
    $fcmToken = $data['deviceToken'] ?? null;

    if (!$fcmToken) {
        return $this->json(['message' => 'deviceToken is required'], 400);
    }

    // Check if token already exists for this user — avoid duplicates
    $existing = $em->getRepository(\App\Entity\DeviceToken::class)
        ->findOneBy(['user' => $user, 'token' => $fcmToken]);

    if (!$existing) {
        $deviceToken = new \App\Entity\DeviceToken();
        $deviceToken->setUser($user);
        $deviceToken->setToken($fcmToken);
        $em->persist($deviceToken);
        $em->flush();
    }

    return $this->json(['success' => true]);
}



private function loadServiceAccount(): array
{
    $json = $_ENV['GOOGLE_SERVICE_ACCOUNT_JSON'] ?? '';
    if (!$json) {
        throw new \RuntimeException('Missing GOOGLE_SERVICE_ACCOUNT_JSON');
    }
    return json_decode($json, true);
}

private function getFcmAccessToken(array $serviceAccount): string
{
    $now = time();
    $header = ['alg' => 'RS256', 'typ' => 'JWT'];
    $claims = [
        'iss' => $serviceAccount['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $now + 3600,
    ];

    $base64Url = function (string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    };

    $jwtHeader = $base64Url(json_encode($header));
    $jwtClaims = $base64Url(json_encode($claims));
    $unsignedJwt = "{$jwtHeader}.{$jwtClaims}";

    openssl_sign($unsignedJwt, $signature, $serviceAccount['private_key'], OPENSSL_ALGO_SHA256);
    $signedJwt = $unsignedJwt . '.' . $base64Url($signature);

    $response = json_decode(file_get_contents('https://oauth2.googleapis.com/token', false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $signedJwt,
            ]),
        ],
    ])), true);

    if (!isset($response['access_token'])) {
        throw new \RuntimeException('Unable to obtain FCM access token: ' . json_encode($response));
    }

    return $response['access_token'];
}

private function sendFcmPushV1(
    string $deviceToken,
    string $title,
    string $body,
    string $orderId
): void {
    $serviceAccount = $this->loadServiceAccount();
    $accessToken = $this->getFcmAccessToken($serviceAccount);
    $projectId = $serviceAccount['project_id'];

    $payload = [
        'message' => [
            'token' => $deviceToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => [
                'orderId' => $orderId,
            ],
            'android' => [
                'priority' => 'HIGH',
            ],
        ],
    ];

    $ch = curl_init("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json; charset=UTF-8',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
    ]);
    $result = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);

    if (($info['http_code'] ?? 0) !== 200) {
        error_log('FCM send failed: ' . $result);
    }
}
  
    

}
