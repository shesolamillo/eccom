<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Repository\ProductTypeRepository;
use App\Repository\ClothesCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/products')]
#[IsGranted('ROLE_ADMIN')]
class AdminProductController extends AbstractController
{
    #[Route('/', name: 'admin_products')]
    public function index(
        ProductRepository $productRepository,
        ClothesCategoryRepository $categoryRepository,
        Request $request
    ): Response {
        // Get filter parameters
        $categoryId = $request->query->get('category');
        $status = $request->query->get('status');
        $search = $request->query->get('search');
        
        // Apply filters
        $products = $productRepository->findWithFilters($categoryId, $status, $search);
        
        // Get statistics
        $totalProducts = $productRepository->count([]);
        $availableProducts = count($productRepository->findBy(['isAvailable' => true]));
        $lowStockCount = $productRepository->countLowStock();
        $outOfStockCount = $productRepository->countOutOfStock();
        
        $categories = $categoryRepository->findAllActive();

        return $this->render('admin/product/index.html.twig', [
            'products' => $products,
            'categories' => $categories,
            'totalProducts' => $totalProducts,
            'availableProducts' => $availableProducts,
            'lowStockCount' => $lowStockCount,
            'outOfStockCount' => $outOfStockCount,
            'selectedCategory' => $categoryId,
            'selectedStatus' => $status,
            'searchTerm' => $search,
        ]);
    }

    #[Route('/new', name: 'admin_product_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ProductTypeRepository $typeRepository,
        ClothesCategoryRepository $categoryRepository,
        SluggerInterface $slugger,
        ProductRepository $productRepository
    ): Response {
        $product = new Product();
        $product->setCreatedBy($this->getUser());
        $product->setIsAvailable(true);

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle image upload
            $imageFile = $form->get('image')->getData();
            if ($imageFile instanceof UploadedFile) {
                $newFilename = $this->uploadImage($imageFile, $slugger);
                if ($newFilename) {
                    $product->setPhoto($newFilename);
                }
            }
            
            // Generate SKU if not provided
            if (!$product->getSku()) {
                $sku = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $product->getName()), 0, 6));
                $sku .= '-' . str_pad($productRepository->getNextId(), 4, '0', STR_PAD_LEFT);
                $product->setSku($sku);
            }
            
            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', 'Product created successfully!');
            
            return $this->redirectToRoute('admin_product_show', ['id' => $product->getId()]);
        }

        $types = $typeRepository->findAllActive();
        $categories = $categoryRepository->findAllActive();

        return $this->render('admin/product/new.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
            'types' => $types,
            'categories' => $categories,
        ]);
    }

    #[Route('/{id}', name: 'admin_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('admin/product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_product_edit')]
    public function edit(
        Request $request,
        Product $product,
        EntityManagerInterface $entityManager,
        ProductTypeRepository $typeRepository,
        ClothesCategoryRepository $categoryRepository,
        SluggerInterface $slugger
    ): Response {
        $originalImage = $product->getPhoto();
        
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle image upload
            $imageFile = $form->get('image')->getData();
            if ($imageFile instanceof UploadedFile) {
                $newFilename = $this->uploadImage($imageFile, $slugger);
                if ($newFilename) {
                    // Delete old image if exists
                    if ($originalImage) {
                        $oldImagePath = $this->getParameter('products_directory') . '/' . $originalImage;
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    $product->setPhoto($newFilename);
                }
            } else {
                // Keep the original image
                $product->setPhoto($originalImage);
            }
            
            $entityManager->flush();

            $this->addFlash('success', 'Product updated successfully.');
            return $this->redirectToRoute('admin_product_show', ['id' => $product->getId()]);
        }

        $types = $typeRepository->findAllActive();
        $categories = $categoryRepository->findAllActive();

        return $this->render('admin/product/edit.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
            'types' => $types,
            'categories' => $categories,
            'originalImage' => $originalImage,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_product_delete', methods: ['DELETE', 'POST'])]
    public function delete(
        Product $product,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        // Check CSRF token
        $submittedToken = $request->request->get('_token');
        
        if (!$this->isCsrfTokenValid('delete' . $product->getId(), $submittedToken)) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => false, 'message' => 'Invalid CSRF token'], 403);
            }
            $this->addFlash('error', 'Invalid security token.');
            return $this->redirectToRoute('admin_products');
        }
        
        try {
            // Check if product has orders
            if (count($product->getOrderItems()) > 0) {
                $message = 'Cannot delete product with existing orders. Consider deactivating it instead.';
                if ($request->isXmlHttpRequest()) {
                    return $this->json(['success' => false, 'message' => $message], 400);
                }
                $this->addFlash('error', $message);
                return $this->redirectToRoute('admin_products');
            }
            
            // Delete associated stock if exists
            if ($product->getStock()) {
                $entityManager->remove($product->getStock());
            }
            
            // Delete image file if exists
            if ($product->getPhoto()) {
                $imagePath = $this->getParameter('products_directory') . '/' . $product->getPhoto();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            $entityManager->remove($product);
            $entityManager->flush();
            
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true, 
                    'message' => 'Product deleted successfully.',
                    'redirect' => $this->generateUrl('admin_products')
                ]);
            }
            
            $this->addFlash('success', 'Product deleted successfully.');
        } catch (\Exception $e) {
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => false, 
                    'message' => 'Error deleting product: ' . $e->getMessage()
                ], 500);
            }
            $this->addFlash('error', 'Error deleting product: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('admin_products');
    }

    #[Route('/{id}/toggle-status', name: 'admin_product_toggle_status', methods: ['POST'])]
    public function toggleStatus(
        Product $product,
        EntityManagerInterface $entityManager,
        Request $request
    ): JsonResponse {
        // Check CSRF token
        $submittedToken = $request->request->get('_token');
        
        if (!$this->isCsrfTokenValid('toggle-status' . $product->getId(), $submittedToken)) {
            return $this->json(['success' => false, 'message' => 'Invalid CSRF token'], 403);
        }
        
        $product->setIsAvailable(!$product->isIsAvailable());
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Product status updated successfully.',
            'isAvailable' => $product->isIsAvailable(),
            'statusText' => $product->isIsAvailable() ? 'Available' : 'Unavailable',
            'statusClass' => $product->isIsAvailable() ? 'success' : 'danger'
        ]);
    }

    #[Route('/bulk/update-status', name: 'admin_product_bulk_status', methods: ['POST'])]
    public function bulkUpdateStatus(
        Request $request,
        EntityManagerInterface $entityManager,
        ProductRepository $productRepository
    ): JsonResponse {
        $productIds = json_decode($request->request->get('productIds', '[]'), true);
        $isActive = filter_var($request->request->get('isActive'), FILTER_VALIDATE_BOOLEAN);
        $token = $request->request->get('_token');
        
        if (!$this->isCsrfTokenValid('bulk-actions', $token)) {
            return $this->json(['success' => false, 'message' => 'Invalid CSRF token'], 403);
        }
        
        if (empty($productIds)) {
            return $this->json(['success' => false, 'message' => 'No products selected'], 400);
        }
        
        try {
            $products = $productRepository->findBy(['id' => $productIds]);
            $updatedCount = 0;
            
            foreach ($products as $product) {
                $product->setIsAvailable($isActive);
                $updatedCount++;
            }
            
            $entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => "Updated {$updatedCount} product(s) successfully.",
                'updatedCount' => $updatedCount
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error updating products: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/bulk/delete', name: 'admin_product_bulk_delete', methods: ['POST'])]
    public function bulkDelete(
        Request $request,
        EntityManagerInterface $entityManager,
        ProductRepository $productRepository
    ): JsonResponse {
        $productIds = json_decode($request->request->get('productIds', '[]'), true);
        $token = $request->request->get('_token');
        
        if (!$this->isCsrfTokenValid('bulk-actions', $token)) {
            return $this->json(['success' => false, 'message' => 'Invalid CSRF token'], 403);
        }
        
        if (empty($productIds)) {
            return $this->json(['success' => false, 'message' => 'No products selected'], 400);
        }
        
        try {
            $products = $productRepository->findBy(['id' => $productIds]);
            $deletedCount = 0;
            $failedCount = 0;
            $failedProducts = [];
            
            foreach ($products as $product) {
                // Check if product has orders
                if (count($product->getOrderItems()) > 0) {
                    $failedCount++;
                    $failedProducts[] = $product->getName();
                    continue;
                }
                
                // Delete associated stock if exists
                if ($product->getStock()) {
                    $entityManager->remove($product->getStock());
                }
                
                // Delete image file if exists
                if ($product->getPhoto()) {
                    $imagePath = $this->getParameter('products_directory') . '/' . $product->getPhoto();
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }
                
                $entityManager->remove($product);
                $deletedCount++;
            }
            
            $entityManager->flush();
            
            $message = "Deleted {$deletedCount} product(s) successfully.";
            if ($failedCount > 0) {
                $message .= " Failed to delete {$failedCount} product(s) with existing orders.";
            }
            
            return $this->json([
                'success' => true,
                'message' => $message,
                'deletedCount' => $deletedCount,
                'failedCount' => $failedCount,
                'failedProducts' => $failedProducts
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error deleting products: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to upload product image
     */
    private function uploadImage(UploadedFile $imageFile, SluggerInterface $slugger): ?string
    {
        try {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
            
            $imageFile->move(
                $this->getParameter('products_directory'),
                $newFilename
            );
            
            return $newFilename;
        } catch (FileException $e) {
            $this->addFlash('warning', 'Could not upload image: ' . $e->getMessage());
            return null;
        }
    }
}