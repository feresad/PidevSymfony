<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

class LoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'error' => $exception->getMessageKey()
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Fallback for non-AJAX requests
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}