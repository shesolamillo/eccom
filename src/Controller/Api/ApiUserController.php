<?php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/users', name: 'api_users_')]
class ApiUserController extends AbstractController
{
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        UserRepository $userRepository,
        Request $request
    ): JsonResponse {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);
        $role = $request->query->get('role');

        $criteria = [];
        if ($role) {
            // You may need to adjust based on how roles are stored
            $criteria['role'] = $role;
        }

        $users = $userRepository->findBy($criteria);
        $total = count($users);
        $users = array_slice($users, ($page - 1) * $limit, $limit);

        $data = array_map(function($user) {
            return $this->userToArray($user);
        }, $users);

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
        UserRepository $userRepository
    ): JsonResponse {
        $user = $userRepository->find($id);

        if (!$user) {
            return $this->json([
                'success' => false,
                'error' => 'User not found'
            ], 404);
        }

        return $this->json([
            'success' => true,
            'data' => $this->userToArray($user)
        ]);
    }

    private function userToArray($user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'username' => $user->getUsername(),
            'roles' => $user->getRoles(),
            'isActive' => $user->isActive(),
            'createdAt' => $user->getCreatedAt() ? $user->getCreatedAt()->format('Y-m-d H:i:s') : null,
        ];
    }
}
