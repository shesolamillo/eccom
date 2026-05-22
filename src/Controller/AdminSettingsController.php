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
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminSettingsController extends AbstractController
{
    #[Route('/admin/settings', name: 'admin_settings', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        SettingsRepository $settingsRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $settings = $settingsRepository->findAll();
        $form     = $this->createForm(SettingsType::class, null, ['settings' => $settings]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($form->getData() as $key => $value) {
                $settingsRepository->setValue($key, $value);
            }
            $this->addFlash('success', 'Settings updated successfully.');
            return $this->redirectToRoute('admin_settings');
        }

        return $this->render('settings/admin/index.html.twig', [
            'form'     => $form->createView(),
            'settings' => $settings,
        ]);
    }

    #[Route('/admin/settings/add', name: 'admin_settings_add', methods: ['POST'])]
    public function addSetting(Request $request, EntityManagerInterface $entityManager): Response
    {
        $key         = $request->request->get('key');
        $type        = $request->request->get('type', 'string');
        $value       = $request->request->get('value');
        $description = $request->request->get('description');

        if (!$key) {
            $this->addFlash('error', 'Setting key is required.');
            return $this->redirectToRoute('admin_settings');
        }

        $existing = $entityManager->getRepository(Settings::class)->findOneBy(['settingKey' => $key]);

        if ($existing) {
            $existing->setDataType($type);
            if ($description) $existing->setDescription($description);
            if ($value !== null && $value !== '') $existing->setTypedValue($value);
            $entityManager->flush();
            $this->addFlash('success', "Setting '{$key}' updated successfully.");
        } else {
            $setting = new Settings();
            $setting->setSettingKey($key);
            $setting->setDataType($type);
            if ($description) $setting->setDescription($description);
            $setting->setTypedValue($value !== null && $value !== '' ? $value : match($type) {
                'boolean' => false,
                'integer' => 0,
                'float'   => 0.0,
                default   => '',
            });
            $entityManager->persist($setting);
            $entityManager->flush();
            $this->addFlash('success', "Setting '{$key}' added successfully.");
        }

        return $this->redirectToRoute('admin_settings');
    }
}
