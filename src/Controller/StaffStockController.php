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
use App\Entity\StockAdjustment;

class StaffStockController extends AbstractController
{
    #[Route('/staff/stocks', name: 'staff_stocks', methods: ['GET','POST'])]
    public function index(StockRepository $stockRepository,
        Request $request,
        EntityManagerInterface $em,
        ProductRepository $productRepository
    ):Response {
        if ($request->isMethod('POST')) {
            

            $productId = $request->request->get('product_id');
            $quantity = (int) $request->request->get('quantity');
            $minimumThreshold = (int) $request->request->get('minimumThreshold', 10);

        // Example: set product relation
        // $productId = $request->request->get('product_id');
        // if ($productId) {
        //     $product = $productRepository->find($productId);
        //     if ($product) {
        //         $stock->setProduct($product);
        //     }
        // }

         // Validate product ID
        if (!$productId) {
            $this->addFlash('error', 'Product is required');
            return $this->redirectToRoute('staff_stocks');
        }

         // Find the product
        $product = $productRepository->find($productId);

        if (!$product) {
            $this->addFlash('error', 'Product not found');
            return $this->redirectToRoute('staff_stocks');
        }
        
        $existingStock = $stockRepository->findOneBy(['product' => $product]);

         if ($existingStock) {
            // UPDATE existing stock
            $existingStock->addQuantity($quantity);
            //$existingStock->setMinimumThreshold($minimumThreshold);
           // $existingStock->checkLowStock(); // Update low stock status
            $existingStock->setUpdatedAt(new \DateTimeImmutable());
            
            $em->flush();
            $this->addFlash('success', 'Stock updated successfully for ' . $product->getName());

        } else {
         // CREATE new stock
        $stock = new Stock();
        $stock->setProduct($product);
        $stock->setQuantity($quantity);
        $stock->setMinimumThreshold($minimumThreshold);
        $stock->setLastRestockedAt(new \DateTimeImmutable());
       // $stock->checkLowStock(); // Check if it's low stock

        $em->persist($stock);
        $em->flush();

        $this->addFlash('success', 'Stock added successfully for ' . $product->getName());
        }



        return $this->redirectToRoute('staff_stocks');
    }
        $search = $request->query->get('search');
            $status = $request->query->get('status');
            $sort   = $request->query->get('sort', 'name_asc');

            $stocks = $stockRepository->findAll();

            if ($search) {
                $stocks = array_filter($stocks, fn($s) =>
                    stripos($s->getProduct()->getName(), $search) !== false ||
                    stripos($s->getProduct()->getSku() ?? '', $search) !== false
                );
            }

            if ($status === 'in_stock') {
                $stocks = array_filter($stocks, fn($s) => $s->getQuantity() > $s->getMinimumThreshold());
            } elseif ($status === 'low_stock') {
                $stocks = array_filter($stocks, fn($s) => $s->getQuantity() > 0 && $s->isIsLowStock());
            } elseif ($status === 'out_of_stock') {
                $stocks = array_filter($stocks, fn($s) => $s->getQuantity() <= 0);
            }

            usort($stocks, function($a, $b) use ($sort) {
                return match($sort) {
                    'quantity_asc'  => $a->getQuantity() <=> $b->getQuantity(),
                    'quantity_desc' => $b->getQuantity() <=> $a->getQuantity(),
                    'name_desc'     => strcmp($b->getProduct()->getName(), $a->getProduct()->getName()),
                    'updated_desc'  => ($b->getUpdatedAt() ?? $b->getCreatedAt()) <=> ($a->getUpdatedAt() ?? $a->getCreatedAt()),
                    default         => strcmp($a->getProduct()->getName(), $b->getProduct()->getName()),
                };
            });


        $lowStock = $stockRepository->findLowStock();
        $outOfStock = $stockRepository->findOutOfStock();

        $products = $productRepository->findBy(['isAvailable' => true]);

        $productsWithStock = array_map(fn($s) => $s->getProduct()->getId(), $stocks);
            $productsWithoutStock = array_filter(
                $products,
                fn($p) => !in_array($p->getId(), $productsWithStock)
            );
        
        return $this->render('stock/staff/index.html.twig', [
            'stocks' => $stocks,
            'lowStock' => $lowStock,
            'outOfStock' => $outOfStock,
            'products' => $products,
            'productsWithoutStock' => $productsWithoutStock,
            'totalItems'          => count($stocks),
            'lowStockItems'       => count($lowStock),
            'outOfStockItems'     => count($outOfStock),
            'totalValue'          => array_reduce($stocks, function($carry, $stock) {
                return $carry + ($stock->getQuantity() * $stock->getProduct()->getPrice());
            }, 0),

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
        
        return $this->redirectToRoute('staff_stocks');
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