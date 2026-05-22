<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('ROLE_STAFF')]
class StaffSettingsController extends AbstractController
{
    #[Route('/staff/settings', name: 'staff_settings', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('settings/staff/index.html.twig');
    }

    #[Route('/staff/settings/profile', name: 'staff_settings_profile', methods: ['POST'])]
    public function profile(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        if (!$this->isCsrfTokenValid('staff_profile', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('staff_settings');
        }

        /** @var User $user */
        $user = $this->getUser();
        $user->setFirstName($request->request->get('firstName'));
        $user->setLastName($request->request->get('lastName'));
        $user->setEmail($request->request->get('email'));
        $user->setPhone($request->request->get('phone'));

        $photo = $request->files->get('photo');
        if ($photo) {
            $safeFilename = $slugger->slug(pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME));
            $newFilename  = $safeFilename . '-' . uniqid() . '.' . $photo->guessExtension();
            $photo->move($this->getParameter('avatars_directory'), $newFilename);

            if ($user->getPhoto()) {
                $old = $this->getParameter('avatars_directory') . '/' . $user->getPhoto();
                if (file_exists($old)) unlink($old);
            }
            $user->setPhoto($newFilename);
        }

        $em->flush();
        $this->addFlash('success', 'Profile updated successfully.');
        return $this->redirectToRoute('staff_settings');
    }

    #[Route('/staff/settings/password', name: 'staff_settings_password', methods: ['POST'])]
    public function password(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        if (!$this->isCsrfTokenValid('staff_password', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('staff_settings');
        }

        /** @var User $user */
        $user            = $this->getUser();
        $currentPassword = $request->request->get('current_password');
        $newPassword     = $request->request->get('new_password');
        $confirmPassword = $request->request->get('confirm_password');

        if (!$hasher->isPasswordValid($user, $currentPassword)) {
            $this->addFlash('error', 'Current password is incorrect.');
            return $this->redirectToRoute('staff_settings');
        }

        if ($newPassword !== $confirmPassword) {
            $this->addFlash('error', 'New passwords do not match.');
            return $this->redirectToRoute('staff_settings');
        }

        if (strlen($newPassword) < 8) {
            $this->addFlash('error', 'Password must be at least 8 characters.');
            return $this->redirectToRoute('staff_settings');
        }

        $user->setPassword($hasher->hashPassword($user, $newPassword));
        $em->flush();
        $this->addFlash('success', 'Password updated successfully.');
        return $this->redirectToRoute('staff_settings');
    }

    #[Route('/staff/settings/notifications', name: 'staff_settings_notifications', methods: ['POST'])]
    public function notifications(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('staff_notifications', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('staff_settings');
        }

        /** @var User $user */
        $user = $this->getUser();
        $user->setNotifLowStock((bool) $request->request->get('notif_low_stock'));
        $user->setNotifNewOrder((bool) $request->request->get('notif_new_order'));
        $user->setNotifOrderStatus((bool) $request->request->get('notif_order_status'));
        $user->setNotifDailySummary((bool) $request->request->get('notif_daily_summary'));
        $user->setInappStockAlert((bool) $request->request->get('inapp_stock_alert'));
        $user->setInappOrderNotif((bool) $request->request->get('inapp_order_notif'));

        $em->flush();
        $this->addFlash('success', 'Notification preferences saved.');
        return $this->redirectToRoute('staff_settings');
    }

    #[Route('/staff/settings/preferences', name: 'staff_settings_preferences', methods: ['POST'])]
    public function preferences(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('staff_preferences', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('staff_settings');
        }

        /** @var User $user */
        $user = $this->getUser();
        $user->setItemsPerPage((int) $request->request->get('items_per_page', 25));
        $user->setDefaultDateRange($request->request->get('default_date_range', 'week'));
        $user->setLanguage($request->request->get('language', 'en'));
        $user->setCompactMode((bool) $request->request->get('compact_mode'));

        $em->flush();
        $this->addFlash('success', 'Preferences saved.');
        return $this->redirectToRoute('staff_settings');
    }
}
