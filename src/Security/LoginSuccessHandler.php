<?php
namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private RouterInterface $router
    ) {}

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token
    ): RedirectResponse {
        $user = $token->getUser();
        if (method_exists($user, 'isVerified') && !$user->isVerified()) {
            // Log them out by invalidating the session
            $request->getSession()->invalidate();
            $request->getSession()->getFlashBag()->add('error', 'Please verify your email before logging in. Check your inbox.');
            return new RedirectResponse($this->router->generate('app_login'));
        }

        $roles = $user->getRoles();

        if (in_array('ROLE_ADMIN', $roles, true)) {
            return new RedirectResponse($this->router->generate('admin_dashboard'));
        }

        if (in_array('ROLE_STAFF', $roles, true)) {
            return new RedirectResponse($this->router->generate('staff_dashboard'));
        }

        return new RedirectResponse($this->router->generate('app_dashboard'));
    }
}
