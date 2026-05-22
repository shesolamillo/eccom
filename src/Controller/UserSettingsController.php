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

#[IsGranted('ROLE_USER')]
class UserSettingsController extends AbstractController
{
    #[Route('/account/settings', name: 'user_settings', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('settings/user/index.html.twig');
    }

    #[Route('/account/settings/profile', name: 'user_settings_profile', methods: ['POST'])]
    public function profile(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        if (!$this->isCsrfTokenValid('user_profile', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('user_settings');
        }

        /** @var User $user */
        $user = $this->getUser();
        $user->setFirstName($request->request->get('firstName'));
        $user->setLastName($request->request->get('lastName'));
        $user->setEmail($request->request->get('email'));
        $user->setPhone($request->request->get('phone'));
        $user->setBio($request->request->get('bio'));

        $birthday = $request->request->get('birthday');
        if ($birthday) $user->setBirthday(new \DateTimeImmutable($birthday));

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
        $this->addFlash('success', 'Profile updated.');
        return $this->redirectToRoute('user_settings');
    }

    #[Route('/account/settings/address', name: 'user_settings_address', methods: ['POST'])]
    public function address(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('user_address', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('user_settings');
        }

        /** @var User $user */
        $user    = $this->getUser();
        $address = $user->getAddress() ?? new \App\Entity\Address();

        $address->setStreet($request->request->get('street'));
        $address->setBarangay($request->request->get('barangay'));
        $address->setCity($request->request->get('city'));
        $address->setProvince($request->request->get('province'));
        $address->setZip($request->request->get('zip'));
        $address->setCountry($request->request->get('country', 'PH'));

        if (!$user->getAddress()) {
            $em->persist($address);
            $user->setAddress($address);
        }

        $em->flush();
        $this->addFlash('success', 'Address saved.');
        return $this->redirectToRoute('user_settings');
    }

    #[Route('/account/settings/password', name: 'user_settings_password', methods: ['POST'])]
    public function password(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        if (!$this->isCsrfTokenValid('user_password', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('user_settings');
        }

        /** @var User $user */
        $user            = $this->getUser();
        $currentPassword = $request->request->get('current_password');
        $newPassword     = $request->request->get('new_password');
        $confirmPassword = $request->request->get('confirm_password');

        if (!$hasher->isPasswordValid($user, $currentPassword)) {
            $this->addFlash('error', 'Current password is incorrect.');
            return $this->redirectToRoute('user_settings');
        }

        if ($newPassword !== $confirmPassword) {
            $this->addFlash('error', 'Passwords do not match.');
            return $this->redirectToRoute('user_settings');
        }

        if (strlen($newPassword) < 8) {
            $this->addFlash('error', 'Password must be at least 8 characters.');
            return $this->redirectToRoute('user_settings');
        }

        $user->setPassword($hasher->hashPassword($user, $newPassword));
        $em->flush();
        $this->addFlash('success', 'Password updated.');
        return $this->redirectToRoute('user_settings');
    }

    #[Route('/account/settings/notifications', name: 'user_settings_notifications', methods: ['POST'])]
    public function notifications(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('user_notifications', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('user_settings');
        }

        /** @var User $user */
        $user = $this->getUser();
        $user->setNotifOrderConfirm((bool) $request->request->get('notif_order_confirm'));
        $user->setNotifOrderShipped((bool) $request->request->get('notif_order_shipped'));
        $user->setNotifOrderDelivered((bool) $request->request->get('notif_order_delivered'));
        $user->setNotifPromos((bool) $request->request->get('notif_promos'));
        $user->setNotifNewsletter((bool) $request->request->get('notif_newsletter'));

        $em->flush();
        $this->addFlash('success', 'Notification preferences saved.');
        return $this->redirectToRoute('user_settings');
    }

    #[Route('/account/delete', name: 'user_account_delete', methods: ['POST'])]
    public function deleteAccount(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('user_delete', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('user_settings');
        }

        /** @var User $user */
        $user = $this->getUser();
        $this->container->get('security.token_storage')->setToken(null);
        $em->remove($user);
        $em->flush();

        return $this->redirectToRoute('app_home');
    }
}
