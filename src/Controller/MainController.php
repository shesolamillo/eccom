<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;



class MainController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->render('landing/index.html.twig');
    }

    #[Route('/services', name: 'app_services')]
    public function services(): Response
    {
        return $this->render('landing/services.html.twig');
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('landing/contact.html.twig');
    }

    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('landing/about.html.twig');
    }

    #[Route('/health', name: 'app_health')]
    public function health(): Response
    {
        return new Response('OK', 200);
    }

    #[Route('/contact/submit', name: 'app_contact_submit', methods: ['POST'])]
    public function submitContact(Request $request): Response
    {
        // Handle form submission here
        $firstName = $request->request->get('firstName');
        $email = $request->request->get('email');
        $message = $request->request->get('message');

        // You can add validation, send email, or save to database here

        $this->addFlash('success', 'Thank you for contacting us!');
        return $this->redirectToRoute('app_contact');
    }


    
}