<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\Utilisateur;
use App\Entity\Role;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;

class SecurityController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google')]
    public function connectGoogle(ClientRegistry $clientRegistry, Request $request): Response
    {
        $client = $clientRegistry->getClient('google');
        
        // Get the current URL being used
        $currentUrl = $request->getSchemeAndHttpHost();
        dump([
            'Current URL' => $currentUrl,
            'Redirect URI' => $currentUrl . '/connect/google/check'
        ]);
        
        return $client->redirect([
            'email',
            'profile'
        ], []);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectGoogleCheck(): Response
    {
        // This method will not be executed, as the authenticator handles this
        return new Response('', Response::HTTP_OK);
    }

    #[Route('/access-denied', name: 'app_access_denied')]
    public function accessDenied(): Response
    {
        return $this->render('security/access_denied.html.twig');
    }

    #[Route('/login/google', name: 'app_google_login', methods: ['POST'])]
    public function googleLogin(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['email'])) {
            return new JsonResponse(['error' => 'Invalid request'], 400);
        }

        $user = $entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $data['email']]);

        if (!$user) {
            // Create new user
            $user = new Utilisateur();
            $user->setEmail($data['email']);
            $user->setNom(explode(' ', $data['name'])[0] ?? '');
            $user->setPrenom(explode(' ', $data['name'])[1] ?? '');
            $user->setNickname($data['email']);
            $user->setNumero(0); // You might want to handle this differently
            
            // Hash the password using the same method as RegisterController
            $hashedPassword = $passwordHasher->hashPassword($user, bin2hex(random_bytes(16)));
            if (str_starts_with($hashedPassword, '$2y$')) {
                $hashedPassword = substr_replace($hashedPassword, '$2a$', 0, 4);
            }
            $user->setMotPasse($hashedPassword);
            
            $user->setRole(Role::CLIENT);
            $user->setGoogleId($data['googleId']);

            $entityManager->persist($user);
            $entityManager->flush();
        } else if (!$user->getGoogleId()) {
            // Update existing user with Google info
            $user->setGoogleId($data['googleId']);
            $entityManager->flush();
        }

        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'role' => $user->getRole()->value
        ]);
    }
}
