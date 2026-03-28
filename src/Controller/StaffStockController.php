<?php

namespace App\Controller;

use App\Entity\Stock;
use App\Form\StockType;
use App\Repository\StockRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StaffStockController extends AbstractController
{
    #[Route('/staff/stocks', name: 'staff_stocks', methods: ['GET','POST'])]
    public function index(StockRepository $stockRepository,
        Request $request,
        EntityManagerInterface $em,
        ProductRepository $productRepository
    ):Response {
        if ($request->isMethod('POST')) {
        $stock = new Stock();
        $stock->setQuantity((int) $request->request->get('quantity'));
        $stock->setMinimumThreshold((int) $request->request->get('minimumThreshold'));

        // Example: set product relation
        $productId = $request->request->get('product_id');
        if ($productId) {
            $product = $productRepository->find($productId);
            if ($product) {
                $stock->setProduct($product);
            }
        }

        $em->persist($stock);
        $em->flush();

        return $this->redirectToRoute('staff_stocks');
    }
        $stocks = $stockRepository->findAll();

        $lowStock = $stockRepository->findLowStock();
        $outOfStock = $stockRepository->findOutOfStock();
        
        return $this->render('stock/staff/index.html.twig', [
            'stocks' => $stocks,
            'lowStock' => $lowStock,
            'outOfStock' => $outOfStock,
        ]);
    }
    // If you have a StockController, add this method:
    #[Route('/staff/stock/adjust', name: 'staff_stock_adjust', methods: ['POST'])]
    public function adjustStock(Request $request, EntityManagerInterface $entityManager, StockRepository $stockRepository): Response
    {
        $stockId = $request->request->get('stock_id');
        $newQuantity = $request->request->get('new_quantity');
        $reason = $request->request->get('reason');
        $notes = $request->request->get('notes');
        
        $stock = $stockRepository->find($stockId);
        
        if (!$stock) {
            $this->addFlash('error', 'Stock record not found.');
            return $this->redirectToRoute('staff_stock_index');
        }
        
        // Create stock adjustment history
        $adjustment = new StockAdjustment();
        $adjustment->setStock($stock);
        $adjustment->setPreviousQuantity($stock->getQuantity());
        $adjustment->setNewQuantity($newQuantity);
        $adjustment->setAdjustmentType('manual');
        $adjustment->setReason($reason);
        $adjustment->setNotes($notes);
        $adjustment->setAdjustedBy($this->getUser());
        $adjustment->setAdjustedAt(new \DateTimeImmutable());
        
        // Update stock quantity
        $stock->setQuantity($newQuantity);
        
        $entityManager->persist($adjustment);
        $entityManager->flush();
        
        $this->addFlash('success', 'Stock quantity updated successfully.');
        
        return $this->redirectToRoute('staff_stock_index');
    }                   

    #[Route('/staff/stock/{id}', name: 'staff_stock_update')]
    public function update(
        Request $request,
        Stock $stock,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(StockType::class, $stock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Stock updated successfully.');

            return $this->redirectToRoute('staff_stocks');
        }

        return $this->render('stock/staff/update.html.twig', [
            'stock' => $stock,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/staff/stock/add', name: 'staff_stock_add')]
    public function add(
        Request $request,
        EntityManagerInterface $entityManager,
        ProductRepository $productRepository
    ): Response {
        $productId = $request->query->get('product');
        $product = null;

        if ($productId) {
            $product = $productRepository->find($productId);
        }

        $stock = new Stock();
        
        if ($product) {
            $stock->setProduct($product);
        }

        $form = $this->createForm(StockType::class, $stock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($stock);
            $entityManager->flush();

            $this->addFlash('success', 'Stock added successfully.');

            return $this->redirectToRoute('staff_stocks');
        }

        $products = $productRepository->findAllAvailable();

        return $this->render('stock/staff/add.html.twig', [
            'stock' => $stock,
            'form' => $form->createView(),
            'products' => $products,
        ]);
    }

    #[Route('/staff/stock/{id}/history', name: 'staff_stock_history')]
    public function history(int $id, StockRepository $stockRepository): Response
    {
        $stock = $stockRepository->find($id);

        if (!$stock) {
            throw $this->createNotFoundException('Stock not found');
        }

        // Assuming you have a StockHistory entity or relation
        $history = $stock->getHistory(); 

        return $this->render('stock/history.html.twig', [
            'stock' => $stock,
            'history' => $history,
        ]);
    }

}