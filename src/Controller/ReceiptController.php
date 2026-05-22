<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Receipt;
use App\Repository\ReceiptRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ReceiptController extends AbstractController
{
    #[Route('/receipt/{id}/view', name: 'app_receipt_view')]
    public function view(
        Order $order,
        ReceiptRepository $receiptRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Check access based on user role
        if ($this->isGranted('ROLE_ADMIN')) {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        } else {
            $this->denyAccessUnlessGranted('ROLE_STAFF');
        }

        $receipt = $receiptRepository->findOneBy(['orderRef' => $order]);

        if (!$receipt) {
            $this->addFlash('warning', 'No receipt found for this order. Please generate one first.');
            
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('admin_order_manage', ['id' => $order->getId()]);
            } else {
                return $this->redirectToRoute('staff_order_manage', ['id' => $order->getId()]);
            }
        }

        return $this->render('receipt/show.html.twig', [
            'receipt' => $receipt,
            'order' => $order,
            'isAdmin' => $this->isGranted('ROLE_ADMIN'),
        ]);
    }

    #[Route('/receipt/{id}/generate', name: 'app_receipt_generate')]
    public function generate(
        Order $order,
        EntityManagerInterface $entityManager,
        ReceiptRepository $receiptRepository
    ): Response {
        // Check access based on user role
        if ($this->isGranted('ROLE_ADMIN')) {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        } else {
            $this->denyAccessUnlessGranted('ROLE_STAFF');
        }

        // Check if order is completed
        if ($order->getStatus() !== Order::STATUS_COMPLETED) {
            $this->addFlash('error', 'Can only generate receipt for completed orders.');
            
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('admin_order_manage', ['id' => $order->getId()]);
            } else {
                return $this->redirectToRoute('staff_order_manage', ['id' => $order->getId()]);
            }
        }

        // CHECK IF RECEIPT ALREADY EXISTS - IMPORTANTE NI!
        $existingReceipt = $receiptRepository->findOneBy(['orderRef' => $order]);
        
        if ($existingReceipt) {
            $this->addFlash('info', 'Receipt already exists for this order.');
            return $this->redirectToRoute('app_receipt_view', ['id' => $order->getId()]);
        }

        // Create new receipt
        $receipt = new Receipt();
        $receipt->setOrderRef($order);
        $receipt->setReceiptNumber($this->generateReceiptNumber());
        $receipt->setIssuedDate(new \DateTimeImmutable());
        $receipt->setPaymentMethod($order->getPaymentMethod());
        $receipt->setSubtotal($order->getTotalAmount() - ($order->getDeliveryFee() ?? 0));
        $receipt->setTotalAmount($order->getTotalAmount());
        $receipt->setPrintedAt(new \DateTimeImmutable());
        $receipt->setPrintedBy($this->getUser());
        $receipt->setCreatedAt(new \DateTimeImmutable());

        try {
            $entityManager->persist($receipt);
            $entityManager->flush();

            $this->addFlash('success', 'Receipt generated successfully!');
            return $this->redirectToRoute('app_receipt_view', ['id' => $order->getId()]);
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error generating receipt: ' . $e->getMessage());
            
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('admin_order_manage', ['id' => $order->getId()]);
            } else {
                return $this->redirectToRoute('staff_order_manage', ['id' => $order->getId()]);
            }
        }
    }

    #[Route('/receipt/{id}/preview', name: 'app_receipt_preview')]
    public function preview(Order $order): Response
    {
        // Check access based on user role
        if ($this->isGranted('ROLE_ADMIN')) {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        } else {
            $this->denyAccessUnlessGranted('ROLE_STAFF');
        }

        return $this->render('receipt/preview.html.twig', [
            'order' => $order,
            'isAdmin' => $this->isGranted('ROLE_ADMIN'),
        ]);
    }

    private function generateReceiptNumber(): string
    {
        return 'RCP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
}