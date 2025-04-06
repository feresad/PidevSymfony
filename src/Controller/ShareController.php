<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ShareController extends AbstractController
{
    #[Route('/share', name: 'app_share')]
    public function index(): Response
    {
        return $this->render('share/index.html.twig', [
            'controller_name' => 'ShareController',
        ]);
    }
}
