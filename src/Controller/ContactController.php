<?php
// src/Controller/ContactController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('landing/contact.html.twig');
    }

    #[Route('/contact/submit', name: 'app_contact_submit', methods: ['POST'])]
    public function submit(Request $request, MailerInterface $mailer): Response
    {
        $firstName = trim($request->request->get('firstName', ''));
        $lastName  = trim($request->request->get('lastName', ''));
        $email     = trim($request->request->get('email', ''));
        $phone     = trim($request->request->get('phone', ''));
        $subject   = trim($request->request->get('subject', ''));
        $message   = trim($request->request->get('message', ''));

        if (!$firstName || !$lastName || !$email || !$subject || !$message) {
            $this->addFlash('contact_error', 'Please fill in all required fields.');
            return $this->redirectToRoute('app_contact');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('contact_error', 'Please enter a valid email address.');
            return $this->redirectToRoute('app_contact');
        }

        $fromAddress = new Address('sheilamaesolamillo@gmail.com', 'SMSol Studio');

        try {
            // Email to admin
            $adminEmail = (new Email())
                ->from($fromAddress)
                ->to('sheilamaesolamillo@gmail.com')
                ->replyTo(new Address($email, $firstName . ' ' . $lastName))
                ->subject('[Contact Form] ' . ucfirst($subject) . ' — ' . $firstName . ' ' . $lastName)
                ->html("
                    <div style='font-family:sans-serif;max-width:600px;margin:0 auto'>
                        <div style='background:#e91e8c;padding:20px 30px;border-radius:12px 12px 0 0'>
                            <h2 style='color:#fff;margin:0;font-size:1.1rem'>New Contact Message — SMSol Studio</h2>
                        </div>
                        <div style='background:#fff;padding:30px;border:1px solid #f0f0f0;border-radius:0 0 12px 12px'>
                            <table style='width:100%;font-size:0.9rem;border-collapse:collapse'>
                                <tr><td style='padding:8px 0;color:#9ca3af;width:110px'>Name</td><td style='padding:8px 0;font-weight:600'>{$firstName} {$lastName}</td></tr>
                                <tr><td style='padding:8px 0;color:#9ca3af'>Email</td><td style='padding:8px 0'><a href='mailto:{$email}' style='color:#e91e8c'>{$email}</a></td></tr>
                                <tr><td style='padding:8px 0;color:#9ca3af'>Phone</td><td style='padding:8px 0'>" . ($phone ?: 'Not provided') . "</td></tr>
                                <tr><td style='padding:8px 0;color:#9ca3af'>Subject</td><td style='padding:8px 0'>" . ucfirst($subject) . "</td></tr>
                            </table>
                            <hr style='border:none;border-top:1px solid #f0f0f0;margin:20px 0'>
                            <p style='color:#9ca3af;font-size:0.78rem;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.05em'>Message</p>
                            <p style='color:#111;line-height:1.7;margin:0'>" . nl2br(htmlspecialchars($message)) . "</p>
                        </div>
                    </div>
                ");

            $mailer->send($adminEmail);

            // Auto-reply to sender
            $replyEmail = (new Email())
                ->from($fromAddress)
                ->to(new Address($email, $firstName . ' ' . $lastName))
                ->subject('We received your message — SMSol Studio')
                ->html("
                    <div style='font-family:sans-serif;max-width:600px;margin:0 auto'>
                        <div style='background:#e91e8c;padding:20px 30px;border-radius:12px 12px 0 0'>
                            <h2 style='color:#fff;margin:0;font-size:1.1rem'>Thanks for reaching out!</h2>
                        </div>
                        <div style='background:#fff;padding:30px;border:1px solid #f0f0f0;border-radius:0 0 12px 12px'>
                            <p style='color:#111'>Hi <strong>{$firstName}</strong>,</p>
                            <p style='color:#4b5563;line-height:1.7'>
                                We've received your message and will get back to you within 1-2 business days.
                            </p>
                            <div style='background:#fdf5fb;border:1px solid #fce4f3;border-left:3px solid #e91e8c;border-radius:10px;padding:16px 20px;margin:20px 0'>
                                <p style='color:#9ca3af;font-size:0.75rem;margin:0 0 6px;text-transform:uppercase;letter-spacing:0.05em'>Your message</p>
                                <p style='color:#111;margin:0;line-height:1.7'>" . nl2br(htmlspecialchars($message)) . "</p>
                            </div>
                            <p style='color:#4b5563;margin-top:24px'>Warm regards,<br><strong style='color:#e91e8c'>SMSol Studio Team</strong></p>
                        </div>
                    </div>
                ");

            $mailer->send($replyEmail);

            $this->addFlash('contact_success', 'Thank you, ' . $firstName . '! Your message has been sent. We\'ll get back to you soon.');

        } catch (\Exception $e) {
            $this->addFlash('contact_error', 'Failed to send message. Please try again. Error: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_contact');
    }
}
