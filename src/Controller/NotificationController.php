<?php

namespace App\Controller;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Provides data endpoints so the frontend JS can get
 * the info it needs to emit Socket.io events.
 */
#[Route('/api/notify')]
class NotificationController extends AbstractController
{
    /**
     * Returns order + customer info needed by the frontend
     * to emit 'order:new' or 'order:status_changed' via Socket.io.
     *
     * GET /api/notify/order/{id}
     */
    #[Route('/order/{id}', name: 'api_notify_order_info', methods: ['GET'])]
    public function orderInfo(Order $order): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $customer = $order->getCustomer();

        return $this->json([
            'orderId'      => $order->getId(),
            'orderNumber'  => $order->getOrderNumber(),
            'customerId'   => $customer->getId(),
            'customerName' => $customer->getFirstName() . ' ' . $customer->getLastName(),
            'totalAmount'  => $order->getTotalAmount(),
            'status'       => $order->getStatus(),
        ]);
    }
}
