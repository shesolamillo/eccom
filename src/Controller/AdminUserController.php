<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class AdminUserController extends AbstractController
{
    #[Route('/admin/users', name: 'admin_users')]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/user/{id}', name: 'admin_user_edit')]
    public function edit(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'User updated successfully.');

            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/user/{id}/promote', name: 'admin_user_promote')]
    public function promote(User $user, EntityManagerInterface $entityManager): Response
    {
        $roles = $user->getRoles();
        
        if (in_array('ROLE_STAFF', $roles)) {
            $user->setRoles(['ROLE_ADMIN']);
            $this->addFlash('success', 'User promoted to Admin.');
        } elseif (in_array('ROLE_USER', $roles)) {
            $user->setRoles(['ROLE_STAFF']);
            $this->addFlash('success', 'User promoted to Staff.');
        }

        $entityManager->flush();

        return $this->redirectToRoute('admin_user_edit', ['id' => $user->getId()]);
    }

    #[Route('/admin/user/{id}/demote', name: 'admin_user_demote')]
    public function demote(User $user, EntityManagerInterface $entityManager): Response
    {
        $roles = $user->getRoles();
        
        if (in_array('ROLE_ADMIN', $roles)) {
            $user->setRoles(['ROLE_STAFF']);
            $this->addFlash('success', 'User demoted to Staff.');
        } elseif (in_array('ROLE_STAFF', $roles)) {
            $user->setRoles(['ROLE_USER']);
            $this->addFlash('success', 'User demoted to regular User.');
        }

        $entityManager->flush();

        return $this->redirectToRoute('admin_user_edit', ['id' => $user->getId()]);
    }

    #[Route('/user/{id}/show', name: 'admin_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        return $this->render('admin/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/admin/users/{id}/delete', name: 'admin_user_delete', methods: ['DELETE', 'POST'])]
    public function deleteUser(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->find($id);
        
        if (!$user) {
            return $this->json(['success' => false, 'error' => 'User not found'], 404);
        }
        
        // Prevent deleting yourself
        if ($user->getId() === $this->getUser()->getId()) {
            return $this->json(['success' => false, 'error' => 'You cannot delete your own account'], 400);
        }
        
        // Prevent deleting the last admin
        $adminCount = $entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->getQuery()
            ->getSingleScalarResult();
        
        if (in_array('ROLE_ADMIN', $user->getRoles()) && $adminCount <= 1) {
            return $this->json(['success' => false, 'error' => 'Cannot delete the only admin user'], 400);
        }
        
        try {
            $userName = $user->getFirstName() . ' ' . $user->getLastName();
            $entityManager->remove($user);
            $entityManager->flush();
            
            return $this->json([
                'success' => true, 
                'message' => sprintf('User "%s" deleted successfully', $userName)
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false, 
                'error' => 'Failed to delete user: ' . $e->getMessage()
            ], 500);
        }


    }

    #[Route('/admin/users/add', name: 'admin_user_add', methods: ['POST'])]
public function addUser(Request $request, EntityManagerInterface $entityManager): JsonResponse
{
    $data = $request->request;
    
    $user = new User();
    $user->setFirstName($data->get('firstName'));
    $user->setLastName($data->get('lastName'));
    $user->setEmail($data->get('email'));
    $user->setPhoneNumber($data->get('phoneNumber'));
    $user->setRoles([$data->get('role')]);
    $user->setPassword(password_hash($data->get('password'), PASSWORD_BCRYPT));
    $user->setIsActive(true);
    $user->setCreatedAt(new \DateTimeImmutable());
    
    $entityManager->persist($user);
    $entityManager->flush();
    
    return $this->json(['success' => true, 'message' => 'User added successfully']);
}




    
}