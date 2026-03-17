<?php

namespace App\Controller;

use App\Repository\StockRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StockController extends AbstractController
{
    #[Route('/stocks', name: 'stock_index')]
    public function index(StockRepository $stockRepository): Response
    {
        $stocks = $stockRepository->findAll();

        return $this->render('stock/index.html.twig', [
            'stocks' => $stocks,
        ]);
    }

    #[Route('/stock/{id}', name: 'stock_show')]
    public function show(int $id, StockRepository $stockRepository): Response
    {
        $stock = $stockRepository->find($id);

        if (!$stock) {
            throw $this->createNotFoundException('Stock not found');
        }

        return $this->render('stock/show.html.twig', [
            'stock' => $stock,
        ]);
    }
}