<?php

namespace App\Security;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    private EntityManagerInterface $entityManager;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator)
    {
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $csrfToken = $request->request->get('_csrf_token');

        $user = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);

        if ($user && $user->isBanned()) {
            throw new CustomUserMessageAuthenticationException(
                'Account is banned',
                ['banMessage' => $user->getBanMessage()]
            );
        }

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [new CsrfTokenBadge('authenticate', $csrfToken)]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($request->isXmlHttpRequest()) {
            /** @var Utilisateur $user */
            $user = $token->getUser();
            
            if ($user->isBanned()) {
                return new JsonResponse([
                    'banned' => true,
                    'banMessage' => $user->getBanMessage()
                ]);
            }

            return new JsonResponse([
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'role' => $user->getRole()->value
            ]);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($request->isXmlHttpRequest()) {
            $data = [
                'error' => $exception->getMessageKey(),
                'banned' => false
            ];

            if ($exception instanceof CustomUserMessageAuthenticationException && $exception->getMessageKey() === 'Account is banned') {
                $data['banned'] = true;
                $data['banMessage'] = $exception->getMessageData()['banMessage'];
            }

            return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
        }

        $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
        return new RedirectResponse($this->urlGenerator->generate('app_login_page'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate('app_login_page');
    }
} 