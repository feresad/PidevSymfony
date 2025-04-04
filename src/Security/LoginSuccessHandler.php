<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function onAuthenticationSuccess(Request $request, $token): Response
    {
        if ($request->isXmlHttpRequest()) {
            $user = $token->getUser();
            return new JsonResponse([
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'role' => $user->getRole()->value
            ]);
        }

        // Fallback for non-AJAX requests
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}