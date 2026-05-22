<?php
// namespace App\Controller;

// use App\Entity\User;
// use Doctrine\ORM\EntityManagerInterface;
// use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// use Symfony\Component\HttpFoundation\JsonResponse;
// use Symfony\Component\HttpFoundation\Request;
// use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
// use Symfony\Component\Routing\Annotation\Route;

// class ApiAuthController extends AbstractController
// {
//     #[Route('/api/login', name: 'api_login', methods: ['POST'])]
//     public function login(
//         Request $request,
//         EntityManagerInterface $em,
//         UserPasswordHasherInterface $passwordHasher
//     ): JsonResponse {
//         $data = json_decode($request->getContent(), true);
//         // $username = $data['username'] ?? null;
//         $username = $data['email'] ?? $data['username'] ?? null; // accept both
//         $password = $data['password'] ?? null;

//         if (!$username || !$password) {
//             return $this->json(['message' => 'Username and password required'], 400);
//         }

//         // your User entity uses email as identifier — adjust if different
//         $user = $em->getRepository(User::class)->findOneBy(['email' => $username]);

//         if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
//             return $this->json(['message' => 'Invalid credentials'], 401);
//         }

//         return $this->json([
//             'message' => 'Login successful',
//             'user' => [
//                 'id'    => $user->getId(),
//                 'email' => $user->getEmail(),
//                 'roles' => $user->getRoles(),
//             ],
//         ]);
//     }

//     #[Route('/api/register', name: 'api_register', methods: ['POST'])]
//     public function register(
//         Request $request,
//         EntityManagerInterface $em,
//         UserPasswordHasherInterface $passwordHasher
//     ): JsonResponse {
//         $data     = json_decode($request->getContent(), true);
//         $username = $data['username'] ?? null;
//         $password = $data['password'] ?? null;

//         if (!$username || !$password) {
//             return $this->json(['message' => 'Username and password required'], 400);
//         }

//         $existing = $em->getRepository(User::class)->findOneBy(['email' => $username]);
//         if ($existing) {
//             return $this->json(['message' => 'Email already registered'], 409);
//         }

//         $user = new User();
//         $user->setEmail($username);
//         $user->setPassword($passwordHasher->hashPassword($user, $password));
//         $user->setIsVerified(true); // skip email verification for API users

//         $em->persist($user);
//         $em->flush();

//         return $this->json(['message' => 'Registration successful'], 201);
//     }
//         #[Route('/api/google-login', name: 'api_google_login', methods: ['POST'])]
// public function googleLogin(
//     Request $request,
//     EntityManagerInterface $em
// ): JsonResponse {
//     $data     = json_decode($request->getContent(), true);
//     $email    = $data['email'] ?? null;
//     $googleId = $data['googleId'] ?? null;
//     $name     = $data['name'] ?? null;

//     if (!$email) {
//         return $this->json(['message' => 'Email is required'], 400);
//     }

//     $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

//     if (!$user) {
//         // Auto-register on first Google login
//         $user = new User();
//         $user->setEmail($email);
//         $user->setPassword(''); // no password for Google users
//         $user->setIsVerified(true);
//         $em->persist($user);
//         $em->flush();
//     }

//     return $this->json([
//         'message' => 'Google login successful',
//         'token'   => base64_encode($user->getId() . ':' . $email), // replace with real JWT
//         'user'    => [
//             'id'    => $user->getId(),
//             'email' => $user->getEmail(),
//             'roles' => $user->getRoles(),
//         ],
//     ]);
// }


//     #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
//     public function logout(): JsonResponse
//     {
//         return $this->json(['message' => 'Logged out successfully']);
//     }
// }

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ApiAuthController extends AbstractController
{
    private function findUserWithProfile(EntityManagerInterface $em, string $email): ?User
    {
        return $em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->leftJoin('u.userProfile', 'up')
            ->addSelect('up')
            ->where('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }





    // helper to build consistent user response
    private function buildUserResponse(User $user): array
    {


        $profile    = $user->getUserProfile();
        $profilePic = null;

        if ($profile && $profile->getProfilePicture()) {
              $profilePic = '/uploads/profiles/' . $profile->getProfilePicture();
        }


        return [
            'id'        => $user->getId(),
            'email'     => $user->getEmail(),
            'roles'     => $user->getRoles(),
            'firstName' => $user->getFirstName(),
            'lastName'  => $user->getLastName(),
            'phoneNumber'    => $user->getPhoneNumber(), 
            'token'     => base64_encode($user->getId() . ':' . $user->getEmail()),
            //'profilePicture' => $profile?->getProfilePicture() ?? null,
            'profilePicture' => $profilePic,
            '_debug_pic_raw' => $profile?->getProfilePicture(),
            '_debug_pic_url' => $profilePic, 
        ];
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request,
                          EntityManagerInterface $em,
                          UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $data     = json_decode($request->getContent(), true);
        $username = $data['email'] ?? $data['username'] ?? null;
        $password = $data['password'] ?? null;

        if (!$username || !$password) {
            return $this->json(['message' => 'Username and password required'], 400);
        }

        //$user = $em->getRepository(User::class)->findOneBy(['email' => $username]);

        $user = $this->findUserWithProfile($em, $username);


        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return $this->json(['message' => 'Invalid credentials'], 401);
        }

        return $this->json([
            'message' => 'Login successful',
            'token'   => base64_encode($user->getId() . ':' . $user->getEmail()),
            'user'    => $this->buildUserResponse($user),
        ]);
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request,
                             EntityManagerInterface $em,
                             UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $data      = json_decode($request->getContent(), true);
        $email     = $data['email'] ?? $data['username'] ?? null;
        $password  = $data['password'] ?? null;
        $firstName = $data['firstName'] ?? null;
        $lastName  = $data['lastName'] ?? null;

        if (!$email || !$password) {
            return $this->json(['message' => 'Email and password required'], 400);
        }

        $existing = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing) {
            return $this->json(['message' => 'Email already registered'], 409);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $user->setIsVerified(true);
        if ($firstName) $user->setFirstName($firstName);
        if ($lastName)  $user->setLastName($lastName);

        $em->persist($user);
        $em->flush();

        return $this->json(['message' => 'Registration successful'], 201);
    }

    #[Route('/api/google-login', name: 'api_google_login', methods: ['POST'])]
    public function googleLogin(Request $request,
                                EntityManagerInterface $em): JsonResponse
    {
        $data     = json_decode($request->getContent(), true);
        $email    = $data['email']    ?? null;
        $googleId = $data['googleId'] ?? null;
        $name     = $data['name']     ?? null;

        if (!$email) {
            return $this->json(['message' => 'Email is required'], 400);
        }

        //$user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        $user = $this->findUserWithProfile($em, $email);


        if (!$user) {
            $user = new User();
            $user->setEmail($email);
            $user->setPassword('');
            $user->setIsVerified(true);

            // parse name into firstName/lastName
            if ($name) {
                $parts = explode(' ', $name, 2);
                $user->setFirstName($parts[0] ?? '');
                $user->setLastName($parts[1] ?? '');
            }

            $em->persist($user);
            $em->flush();
        }

        return $this->json([
            'message' => 'Google login successful',
            'token'   => base64_encode($user->getId() . ':' . $email),
            'user'    => $this->buildUserResponse($user),
        ]);
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        return $this->json(['message' => 'Logged out successfully']);
    }
}

