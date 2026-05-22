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

    #[Route('/order/create', name: 'app_user_order_create', methods: ['GET', 'POST'])]
public function new(
    Request $request,
    EntityManagerInterface $entityManager,
    ProductRepository $productRepository
): Response {
    $user = $this->getUser();

    if ($request->isMethod('POST') && $request->isXmlHttpRequest() === false && $request->headers->get('Content-Type') !== 'application/json') {
        // Handle fetch/FormData POST
        $deliveryType = $request->request->get('delivery_type');
        $paymentMethod = $request->request->get('payment_method');
        $isUrgent = (bool) $request->request->get('is_urgent', false);
        $deliveryFee = (float) $request->request->get('delivery_fee', 0);
        $items = $request->request->all('items');

        if (empty($items)) {
            return $this->json(['error' => 'No items in order'], 400);
        }

        $order = new Order();
        $order->setCustomer($user);
        $order->setDeliveryType($deliveryType);
        $order->setPaymentMethod($paymentMethod);
        $order->setIsUrgent($isUrgent);
        $order->setDeliveryFee($deliveryFee);

        if ($deliveryType === 'delivery') {
            $recipient = $request->request->get('recipient_name');
            $phone = $request->request->get('phone_number');
            $street = $request->request->get('street_address');
            $city = $request->request->get('city');
            $postal = $request->request->get('postal_code');
            $order->setDeliveryAddress(implode(', ', array_filter([$recipient, $phone, $street, $city, $postal])));
        }

        foreach ($items as $itemData) {
            $product = $productRepository->find((int) $itemData['product_id']);
            if (!$product) continue;
            $orderItem = new OrderItem();
            $orderItem->setProduct($product);
            $orderItem->setQuantity((int) $itemData['quantity']);
            $orderItem->setUnitPrice($product->getPrice());
            $orderItem->calculateTotal();
            $order->addOrderItem($orderItem);
        }

        $order->calculateTotal();
        $entityManager->persist($order);
        $entityManager->flush();

        return $this->json(['success' => true, 'orderId' => $order->getId()]);
    }

    // GET - render the form
    $products = $productRepository->findAllAvailable();

    return $this->render('order/user/new.html.twig', [
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