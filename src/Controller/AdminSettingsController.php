<?php

namespace App\Controller;

use App\Entity\Settings;
use App\Form\SettingsType;
use App\Repository\SettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminSettingsController extends AbstractController
{
    #[Route('/admin/settings', name: 'admin_settings')]
    public function index(
        Request $request,
        SettingsRepository $settingsRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $settings = $settingsRepository->findAll();
        
        $form = $this->createForm(SettingsType::class, null, [
            'settings' => $settings,
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            
            foreach ($formData as $key => $value) {
                $settingsRepository->setValue($key, $value);
            }

            $this->addFlash('success', 'Settings updated successfully.');

            return $this->redirectToRoute('admin_settings');
        }

        return $this->render('admin/settings/index.html.twig', [
            'form' => $form->createView(),
            'settings' => $settings,
        ]);
    }
}