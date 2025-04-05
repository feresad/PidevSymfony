<?php

namespace App\Controller;

use App\Form\ForgotPasswordType;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ForgotPasswordController extends AbstractController
{
    #[Route('/mot-passe-oublie', name: 'app_forgot_password')]
    public function index(Request $request, UtilisateurRepository $userRepository): Response
    {
        $form = $this->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user = $userRepository->findOneBy(['email' => $email]);

            if ($user) {
                // Store email in session for reset password page
                $request->getSession()->set('reset_password_email', $email);
                return $this->redirectToRoute('app_reset_password');
            }

            $this->addFlash('error', 'Email introuvable. Veuillez vérifier votre adresse email.');
        }

        return $this->render('forgot_password/forgotPassword.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
} 