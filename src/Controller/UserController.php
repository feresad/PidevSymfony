<?php

namespace App\Controller;

use App\Entity\Role;
use App\Entity\Utilisateur;
use App\Form\LoginFormType;
use App\Repository\EvenementRepository;
use App\Repository\QuestionsRepository;
use App\Repository\Session_gameRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\StockRepository;
use SebastianBergmann\Environment\Console;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class UserController extends AbstractController
{
    #[Route('/login', name: 'app_login_page', methods: ['GET'])]
    public function loginPage(AuthenticationUtils $authenticationUtils, UtilisateurRepository $userRepository): Response
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
            'image_base_url' => $this->getParameter('image_base_url'),
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
    public function home(EvenementRepository $repo, QuestionsRepository $questionsRepository, Session_gameRepository $sessionRepository, StockRepository $stockRepo): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login_page');
        }

        // Check if user has ROLE_ADMIN using the security interface
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->render('home/indexadmin.html.twig', [
                'image_base_url' => $this->getParameter('image_base_url'),
            ]);
        }

        $today = new \DateTime();
        $recentEvenements = $repo->findRecentEvents($today, 3);
        
        // Get featured products sorted by price
        $featuredProducts = $stockRepo->findFeaturedProductsByPrice(6);

        // Fetch top 2 trending topics based on votes
        $trendingTopics = $questionsRepository->createQueryBuilder('q')
            ->innerJoin('q.utilisateur_id', 'u')
            ->where('q.votes >= :threshold')
            ->setParameter('threshold', -5)
            ->orderBy('q.votes', 'DESC')
            ->setMaxResults(2)
            ->getQuery()
            ->getResult();

        // Map topics to display data
        $topicsData = array_map(function ($question) {
            $user = $question->getUtilisateurId();
            return [
                'id' => $question->getQuestionId(),
                'title' => $question->getTitle(),
                'content' => $question->getContent(),
                'mediaPath' => $question->getMediaPath(), // Just the filename, e.g., 'example.mp4'
                'mediaType' => $question->getMediaType() ? $question->getMediaType()->value : null,
                'startedBy' => $user ? $user->getNickname() : 'Unknown User',
                'startedOn' => $question->getCreatedAt() ? $question->getCreatedAt()->format('M d, Y') : 'N/A',
                'postCount' => $question->getCommentaires()->count(),
            ];
        }, $trendingTopics);
        

        // Fetch promotional sessions
        $promoSessions = $sessionRepository->getSessionsInPromo();

        return $this->render('home/index.html.twig', [
            'user' => $user,
            'recentEvenements' => $recentEvenements,
            'featuredProducts' => $featuredProducts,
            'image_base_url' => $this->getParameter('image_base_url'),
            'image_base_url2' => $this->getParameter('image_base_url2'),
            'trendingTopics' => $topicsData,
            'promoSessions' => $promoSessions,
        ]);
    }
}