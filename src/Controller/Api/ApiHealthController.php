<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'api_')]
class ApiHealthController extends AbstractController
{
    #[Route('/health', name: 'health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'message' => 'API is healthy',
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            'version' => '1.0.0',
            'endpoints' => [
                'products' => '/api/products',
                'orders' => '/api/orders',
                'stock' => '/api/stock',
                'users' => '/api/users',
            ]
        ]);
    }
}
