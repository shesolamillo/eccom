<?php

namespace App\Controller\Api;

use App\Repository\StockRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/api/stock', name: 'api_stock_')]
class ApiStockController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        StockRepository $stockRepository,
        Request $request
    ): JsonResponse {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);
        $productId = $request->query->get('productId');

        $criteria = [];
        if ($productId) {
            $criteria['product'] = $productId;
        }

        $stocks = $stockRepository->findBy($criteria);
        $total = count($stocks);
        $stocks = array_slice($stocks, ($page - 1) * $limit, $limit);

        $data = array_map(function($stock) {
            return $this->stockToArray($stock);
        }, $stocks);

        return $this->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit),
            ]
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(
        $id,
        StockRepository $stockRepository
    ): JsonResponse {
        $stock = $stockRepository->find($id);

        if (!$stock) {
            return $this->json([
                'success' => false,
                'error' => 'Stock not found'
            ], 404);
        }

        return $this->json([
            'success' => true,
            'data' => $this->stockToArray($stock)
        ]);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(
        $id,
        Request $request,
        StockRepository $stockRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $stock = $stockRepository->find($id);

        if (!$stock) {
            return $this->json([
                'success' => false,
                'error' => 'Stock not found'
            ], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['quantity'])) {
            $stock->setQuantity($data['quantity']);
        }

        if (isset($data['reorderLevel'])) {
            $stock->setReorderLevel($data['reorderLevel']);
        }

        $stock->setUpdatedAt(new \DateTime());
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Stock updated successfully',
            'data' => $this->stockToArray($stock)
        ]);
    }

    #[Route('/low-stock', name: 'low_stock', methods: ['GET'])]
    public function getLowStock(
        StockRepository $stockRepository,
        Request $request
    ): JsonResponse {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);

        // Get all stocks where quantity <= reorderLevel
        $allStocks = $stockRepository->findAll();
        $lowStock = array_filter($allStocks, function($stock) {
            return $stock->getQuantity() <= $stock->getReorderLevel();
        });

        $total = count($lowStock);
        $lowStock = array_slice($lowStock, ($page - 1) * $limit, $limit);

        $data = array_map(function($stock) {
            return $this->stockToArray($stock);
        }, $lowStock);

        return $this->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit),
            ]
        ]);
    }

    private function stockToArray($stock): array
    {
        return [
            'id' => $stock->getId(),
            'productId' => $stock->getProduct()->getId(),
            'productName' => $stock->getProduct()->getName(),
            'size' => $stock->getSize(),
            'quantity' => $stock->getQuantity(),
            'reorderLevel' => $stock->getReorderLevel(),
            'isLowStock' => $stock->getQuantity() <= $stock->getReorderLevel(),
            'updatedAt' => $stock->getUpdatedAt() ? $stock->getUpdatedAt()->format('Y-m-d H:i:s') : null,
        ];
    }
}
