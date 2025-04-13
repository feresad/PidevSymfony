<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

class AdminController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function dashboard(
        Request $request,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator,
        UtilisateurRepository $utilisateurRepository
    ): Response {
        // Check if user is admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $searchQuery = $request->query->get('q');
        
        // Get query for users based on search
        $query = $utilisateurRepository->searchByNickname($searchQuery);

        // Paginate the results
        $users = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/dashboard.html.twig', [
            'users' => $users,
            'searchQuery' => $searchQuery
        ]);
    }
}