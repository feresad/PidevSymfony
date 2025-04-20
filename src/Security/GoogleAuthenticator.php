<?php

namespace App\Security;

use App\Entity\Utilisateur;
use App\Entity\Role;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use League\OAuth2\Client\Provider\GoogleUser;

class GoogleAuthenticator extends OAuth2Authenticator
{
    private $clientRegistry;
    private $entityManager;
    private $router;

    public function __construct(ClientRegistry $clientRegistry, EntityManagerInterface $entityManager, RouterInterface $router)
    {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->router = $router;
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($accessToken, $client) {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);

                $email = $googleUser->getEmail();

                // 1) have they logged in with Google before? Easy!
                $existingUser = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);

                if ($existingUser) {
                    return $existingUser;
                }

                // 2) do we have a matching user by email?
                $user = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);

                if (!$user) {
                    $user = new Utilisateur();
                    $user->setEmail($email);
                    $names = explode(' ', $googleUser->getName());
                    $user->setNom($names[0] ?? '');
                    $user->setPrenom($names[1] ?? '');
                    $user->setNickname($email);
                    $user->setNumero(0);
                    $user->setMotPasse(bin2hex(random_bytes(16)));
                    $user->setRole(Role::CLIENT);
                    $user->setGoogleId($googleUser->getId());
                    $this->entityManager->persist($user);
                }

                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // change "app_homepage" to any route you want
        return new RedirectResponse(
            $this->router->generate('app_home')
        );
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($request->hasSession()) {
            $request->getSession()->set('_security.last_error', $exception);
        }

        return new RedirectResponse(
            $this->router->generate('app_login_page')
        );
    }
} 