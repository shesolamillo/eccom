<?php
namespace App\Controller;

use App\Entity\ClothesCategory;
use App\Repository\ClothesCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\ProductType;
use App\Repository\ProductTypeRepository;

#[Route('/admin/categories')]
#[IsGranted('ROLE_ADMIN')]
class AdminClothesCategoryController extends AbstractController
{
    #[Route('', name: 'admin_category_manage', methods: ['GET'])]
    public function index(
        ClothesCategoryRepository $categoryRepository,
        ProductTypeRepository $productTypeRepository
    ): Response{
        return $this->render('admin/category/index.html.twig', [
            'categories' => $categoryRepository->findAllActive(),
            'productTypes' => $productTypeRepository->findAll(),
        ]);
    }



    #[Route('/add', name: 'admin_category_add', methods: ['POST'])]
    public function add(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $name = trim($request->request->get('name', ''));
        $type = $request->request->get('type', 'human');

        if (empty($name)) {
            $this->addFlash('error', 'Category name is required.');
            return $this->redirectToRoute('admin_category_manage');
        }

        $category = new ClothesCategory();
        $category->setName($name);
        $category->setIsActive(true);

        $em->persist($category);
        $em->flush();

        $this->addFlash('success', 'Category "' . $name . '" added successfully.');
        return $this->redirectToRoute('admin_category_manage');
    }

    #[Route('/{id}/delete', name: 'admin_category_delete', methods: ['POST'])]
    public function delete(
        ClothesCategory $category,
        EntityManagerInterface $em,
        Request $request
    ): Response {
         if (!$this->isCsrfTokenValid('delete' . $category->getId(), $request->request->get('_token'))) {
             $this->addFlash('error', 'Invalid security token.');
             return $this->redirectToRoute('admin_category_manage');
     }

        if ($category->getProducts()->count() > 0) {
            $this->addFlash('error', 'Cannot delete "' . $category->getName() . '" — it has existing products.');
            return $this->redirectToRoute('admin_category_manage');
        }

        $name = $category->getName();
        $em->remove($category);
        $em->flush();

        $this->addFlash('success', 'Category "' . $name . '" deleted.');
        return $this->redirectToRoute('admin_category_manage');
    }

    #[Route('/product-type/add', name: 'admin_product_type_add', methods: ['POST'])]
public function addProductType(
    Request $request, 
    EntityManagerInterface $em,
    ClothesCategoryRepository $categoryRepository
    ): Response
{
    $name = trim($request->request->get('name', ''));
    $categoryId = $request->request->get('category_id');
     $category   = $categoryRepository->find($categoryId);


    if (empty($name) || !$category) {
        $this->addFlash('error', 'Name and category are required');
        return $this->redirectToRoute('admin_category_manage');
    }

    $pt = new ProductType();
    $pt->setName($name);
    $pt->setCategory($category);
    $pt->setIsActive(true);
    $pt->setBasePrice(0);
    $pt->setEstimatedHours(24);

    $em->persist($pt);
    $em->flush();

    $this->addFlash('success', 'Product type "' . $name . '" added.');
    return $this->redirectToRoute('admin_category_manage');
}

#[Route('/product-type/{id}/delete', name: 'admin_product_type_delete', methods: ['POST'])]
public function deleteProductType(
    ProductType $productType, 
    EntityManagerInterface $em, 
    Request $request
    ): Response{
    if (!$this->isCsrfTokenValid('delete_pt' . $productType->getId(), $request->request->get('_token'))) {
        $this->addFlash('error', 'Invalid security token.');
        return $this->redirectToRoute('admin_category_manage');
    }

    if ($productType->getProducts()->count() > 0) {
        $this->addFlash('error', 'Cannot delete — it has existing products.');
        return $this->redirectToRoute('admin_category_manage');
    }

    $em->remove($productType);
    $em->flush();

    $this->addFlash('success', 'Product type deleted.');
    return $this->redirectToRoute('admin_category_manage');
}
}
