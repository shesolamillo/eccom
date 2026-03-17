<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Repository\ProductTypeRepository;
use App\Repository\ClothesCategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    #[Route('/products', name: 'app_products')]
    public function index(
        ProductRepository $productRepository,
        ProductTypeRepository $productTypeRepository,
        ClothesCategoryRepository $categoryRepository,
        Request $request
    ): Response {
        $categoryId = $request->query->get('category');
        $typeId = $request->query->get('type');

        $products = $productRepository->findByCategoryAndType($categoryId, $typeId);
        $categories = $categoryRepository->findAllActive();
        $types = $productTypeRepository->findAllActive();

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'categories' => $categories,
            'types' => $types,
            'selectedCategory' => $categoryId,
            'selectedType' => $typeId,
        ]);
    }

    #[Route('/product/{id}', name: 'app_product_show')]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }
}