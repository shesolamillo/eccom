<?php

namespace App\Controller\Api;

use App\Repository\ProductRepository;
use App\Repository\ProductTypeRepository;
use App\Repository\ClothesCategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/products', name: 'api_products_')]
class ApiProductController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        ProductRepository $productRepository,
        Request $request
    ): JsonResponse {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);
        $categoryId = $request->query->get('category');
        $typeId = $request->query->get('type');
        $search = $request->query->get('search');

        // Get filtered products
        $products = $productRepository->findByCategoryAndType($categoryId, $typeId);
        
        if ($search) {
            $products = array_filter($products, function($product) use ($search) {
                return stripos($product->getName(), $search) !== false;
            });
        }

        // Paginate
        $total = count($products);
        $products = array_slice($products, ($page - 1) * $limit, $limit);

        $data = array_map(function($product) {
            return $this->productToArray($product);
        }, $products);

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
        ProductRepository $productRepository
    ): JsonResponse {
        $product = $productRepository->find($id);

        if (!$product) {
            return $this->json([
                'success' => false,
                'error' => 'Product not found'
            ], 404);
        }

        return $this->json([
            'success' => true,
            'data' => $this->productToArray($product)
        ]);
    }

    #[Route('/{id}/stock', name: 'stock', methods: ['GET'])]
    public function getStock(
        $id,
        ProductRepository $productRepository
    ): JsonResponse {
        $product = $productRepository->find($id);

        if (!$product) {
            return $this->json([
                'success' => false,
                'error' => 'Product not found'
            ], 404);
        }

        $stock = $product->getStock();
        $stockArray = [];
        
        foreach ($stock as $s) {
            $stockArray[] = [
                'id' => $s->getId(),
                'size' => $s->getSize(),
                'quantity' => $s->getQuantity(),
                'reorderLevel' => $s->getReorderLevel(),
            ];
        }

        return $this->json([
            'success' => true,
            'data' => [
                'productId' => $product->getId(),
                'productName' => $product->getName(),
                'stock' => $stockArray,
                'totalQuantity' => array_sum(array_column($stockArray, 'quantity')),
            ]
        ]);
    }

    private function productToArray($product): array
    {
        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => (float) $product->getPrice(),
            'image' => $product->getImage(),
            'category' => $product->getCategory() ? [
                'id' => $product->getCategory()->getId(),
                'name' => $product->getCategory()->getName(),
            ] : null,
            'type' => $product->getType() ? [
                'id' => $product->getType()->getId(),
                'name' => $product->getType()->getName(),
            ] : null,
            'sku' => $product->getSku(),
            'createdAt' => $product->getCreatedAt() ? $product->getCreatedAt()->format('Y-m-d H:i:s') : null,
            'updatedAt' => $product->getUpdatedAt() ? $product->getUpdatedAt()->format('Y-m-d H:i:s') : null,
        ];
    }
}
