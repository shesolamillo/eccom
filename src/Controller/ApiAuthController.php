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
use App\Entity\UserProfile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class ApiAuthController extends AbstractController
{
    private $jwtManager;
    public function __construct(JWTTokenManagerInterface $jwtManger)
    {
        $this->jwtManager = $jwtManger;
    }
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

        return $this->json([
            'message' => 'Registration successful',
            'token'   => base64_encode($user->getId() . ':' . $user->getEmail()),
            'user'    => $this->buildUserResponse($user),
        ], 201);

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


    #[Route('/api/profile/upload', methods: ['POST'])]
    public function upload(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        $file = $request->files->get('profilePicture');

        if (!$file) {
            return $this->json(['message' => 'No file uploaded'], 400);
        }

        $newFilename = uniqid().'.'.$file->guessExtension();

        $file->move(
            $this->getParameter('profiles_directory'),
            $newFilename
        );

        $profile = $user->getUserProfile();
        $profile->setProfilePicture($newFilename);

        $em->flush();

        return $this->json([
            'message' => 'Profile updated',
            'profilePicture' => $newFilename
        ]);
    }

    #[Route('/api/profile/update', name: 'api_update_user', methods: ['POST'])]
    public function updateUser(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        // ✅ CHANGED — decode base64 token manually instead of $this->getUser()
        $authHeader = $request->headers->get('Authorization', '');

        if (!str_starts_with($authHeader, 'Bearer ')) {
            return $this->json(['message' => 'Unauthorized'], 403);
        }

        $token   = substr($authHeader, 7);
        $decoded = base64_decode($token, true);

        if (!$decoded || !str_contains($decoded, ':')) {
            return $this->json(['message' => 'Invalid token'], 403);
        }

        // Token format: base64(id:email) → decode → split by : → get ID
        $userId = (int) explode(':', $decoded, 2)[0];

        if ($userId <= 0) {
            return $this->json(['message' => 'Invalid user ID'], 403);
        }

        $user = $this->findUserById($em, $userId);
        if (!$user) {
            return $this->json(['message' => 'User not found'], 404);
        }

        $firstName   = $request->request->get('firstName');
        $lastName    = $request->request->get('lastName');
        $phoneNumber = $request->request->get('phoneNumber');

        if ($firstName !== null)   $user->setFirstName($firstName);
        if ($lastName !== null)    $user->setLastName($lastName);
        if ($phoneNumber !== null) $user->setPhoneNumber($phoneNumber);

        // Handle profile picture upload
        $file = $request->files->get('profilePicture');
        if ($file) {
            $newFilename = uniqid() . '.' . $file->guessExtension();
            $file->move($this->getParameter('profiles_directory'), $newFilename);

            $profile = $user->getUserProfile();
            if (!$profile) {
                // ✅ CHANGED — use fully qualified class or import at top
                $profile = new UserProfile();
                $profile->setUser($user);
                $em->persist($profile);
            }

            $profile->setProfilePicture($newFilename);
        }

        $em->flush();

        return $this->json([
            'message' => 'Profile updated successfully',
            'user'    => $this->buildUserResponse($user),
        ]);
    }
    private function findUserById(EntityManagerInterface $em, int $id): ?User
{
    return $em->getRepository(User::class)
        ->createQueryBuilder('u')
        ->leftJoin('u.userProfile', 'up')
        ->addSelect('up')
        ->where('u.id = :id')
        ->setParameter('id', $id)
        ->getQuery()
        ->getOneOrNullResult();
}



    

    
}

