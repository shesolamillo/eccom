<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class GoogleController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google_start')]
    public function connect(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect(['email', 'profile']);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function check(): Response
    {
        $this->addFlash('error', 'Google authentication failed!');
        
        return $this->redirectToRoute('app_login');
    }

    #[Route('/connect/google/error', name: 'connect_google_error')]
    public function error(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        
        if ($error) {
            $this->addFlash('error', $error->getMessage());
        } else {
            $this->addFlash('error', 'An unknown error occurred during Google login');
        }
        
        return $this->redirectToRoute('app_login');
    }
}