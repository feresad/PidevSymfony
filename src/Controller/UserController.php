<?php

namespace App\Controller;

use App\Entity\Role;
use App\Entity\Utilisateur;
use App\Form\LoginFormType;
use App\Repository\EvenementRepository;
use App\Repository\UtilisateurRepository;
use SebastianBergmann\Environment\Console;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class UserController extends AbstractController
{
    
    #[Route('/login', name: 'app_login_page', methods: ['GET'])]
    public function loginPage(AuthenticationUtils $authenticationUtils,UtilisateurRepository $userRepository): Response
    {
        
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(LoginFormType::class);
        $userId = $userRepository->getLoggedInUserId();
        return $this->render('login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'form' => $form->createView(),
            'userId' => $userId,
        ]);
    }

    #[Route('/login', name: 'app_login', methods: ['POST'])]
    public function login(Request $request, UtilisateurRepository $userRepository): Response
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');

        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            return $this->json(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        // Check if user is banned
        if ($user->isBan()) {
            $banEnd = $user->getBanTime();
            $banMessage = $banEnd
                ? "Votre compte est banni jusqu'au " . $banEnd->format('d/m/Y H:i') . "."
                : "Votre compte est banni de façon permanente.";
            return $this->json([
                'error' => 'Account is banned',
                'banMessage' => $banMessage
            ], Response::HTTP_FORBIDDEN);
        }

        // Get the stored hash
        $storedHash = $user->getPassword();
        $isValid = hash_equals($storedHash, crypt($password, $storedHash));

        if (!$isValid) {
            return $this->json(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        $roles = $user->getRoles();
        $isAdmin = in_array('ROLE_ADMIN', $roles);

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $roles,
            'isAdmin' => $isAdmin
        ], Response::HTTP_OK);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        // This method will be intercepted by the logout listener
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/', name: 'app_home')]
    public function home(EvenementRepository $repo): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login_page');
        }

        // Check if user has ROLE_ADMIN using the security interface
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->render('home/indexadmin.html.twig');
        }
        $today = new \DateTime();
        $recentEvenements = $repo->findRecentEvents($today, 4);


        return $this->render('home/index.html.twig', [
            'user' => $user,
            'recentEvenements' => $recentEvenements,
            'image_base_url' => $this->getParameter('image_base_url'),
        ]);
    }
}
