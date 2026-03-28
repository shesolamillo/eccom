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

    
}