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

class ReceiptController extends AbstractController
{
    #[Route('/receipt/{id}', name: 'app_receipt_download')]
    public function download(
        Order $order,
        ReceiptRepository $receiptRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('VIEW', $order);

        $receipt = $receiptRepository->findByOrder($order->getId());

        if (!$receipt) {
            $receipt = new Receipt();
            $receipt->setOrderRef($order);
            $receipt->generateReceiptNumber();
            
            $entityManager->persist($receipt);
            $entityManager->flush();
        }

        // For now, return HTML receipt. Later implement PDF generation
        return $this->render('receipt/show.html.twig', [
            'receipt' => $receipt,
            'order' => $order,
        ]);
    }

    #[Route('/receipt/{id}/generate', name: 'app_receipt_generate')]
    public function generate(
        Order $order,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        if ($order->getStatus() !== Order::STATUS_COMPLETED) {
            $this->addFlash('error', 'Can only generate receipt for completed orders.');
            return $this->redirectToRoute('staff_order_manage', ['id' => $order->getId()]);
        }

        $receipt = new Receipt();
        $receipt->setOrderRef($order);
        $receipt->markAsPrinted($this->getUser());

        $entityManager->persist($receipt);
        $entityManager->flush();

        $this->addFlash('success', 'Receipt generated successfully.');

        return $this->redirectToRoute('app_receipt_download', ['id' => $order->getId()]);
    }

    #[Route('/receipt/{id}/preview', name: 'app_receipt_preview')]
    public function preview(Order $order): Response
    {
        $this->denyAccessUnlessGranted('VIEW', $order);

        return $this->render('receipt/preview.html.twig', [
            'order' => $order,
        ]);
    }
}