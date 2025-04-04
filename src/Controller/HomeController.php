<?php

namespace App\Controller;

use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            

        ]);
    }
    #[Route('/admin', name: 'app_home_admin')]
    public function indexAdmin(): Response
    {
        return $this->render('home/indexadmin.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }
    
}
