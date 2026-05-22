<?php
// src/Security/GoogleAuthenticator.php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class GoogleAuthenticator extends OAuth2Authenticator
{
    private ClientRegistry $clientRegistry;
    private EntityManagerInterface $entityManager;
    private RouterInterface $router;
    private MailerInterface $mailer;

    public function __construct(
        ClientRegistry $clientRegistry,
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        MailerInterface $mailer
    ) {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->mailer = $mailer;
        
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($accessToken) {
                $googleUser = $this->clientRegistry
                    ->getClient('google')
                    ->fetchUserFromToken($accessToken);

                $email = $googleUser->getEmail();
                
                // Check if user exists
                $user = $this->entityManager
                    ->getRepository(User::class)
                    ->findOneBy(['email' => $email]);

                // Create new user if not exists
                if (!$user) {
                    $user = new User();
                    $user->setEmail($email);
                    $user->setFullName($googleUser->getName());
                    $user->setGoogleId($googleUser->getId());
                    $user->setRoles(['ROLE_USER']);
                    
                    $this->entityManager->persist($user);
                    $this->entityManager->flush();
                }

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
{
    $user = $token->getUser();

    try {
        if (!$user || !$user->getEmail()) {
            throw new \Exception('User email not found');
        }

        // USER EMAIL
        $emailToUser = (new Email())
            ->from(new Address('sheilamaesolamillo@gmail.com', 'Eccom'))
            ->to($user->getEmail())
            ->subject('Login Confirmation')
            ->text('Hello ' . $user->getFullName() . ', you have successfully logged in using Google.');

        $this->mailer->send($emailToUser);

        // ADMIN EMAIL
        $emailToAdmin = (new Email())
            ->from('sheilamaesolamillo@gmail.com')
            ->to('sheilamaesolamillo@gmail.com')
            ->subject('User Login Alert')
            ->text($user->getEmail() . ' just logged in using Google.');

        $this->mailer->send($emailToAdmin);

    } catch (\Exception $e) {
        error_log('MAIL ERROR: ' . $e->getMessage());
    }

    return new RedirectResponse($this->router->generate('app_home'));
}
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Add flash message and redirect to login page
        $request->getSession()->getFlashBag()->add('error', 'Google authentication failed: ' . $exception->getMessage());
        
        return new RedirectResponse($this->router->generate('app_login'));
    }
}