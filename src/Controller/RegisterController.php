<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\Role;
use App\Form\RegisterFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegisterController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new Utilisateur();
        $form = $this->createForm(RegisterFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password using Symfony's password hasher
            $hashedPassword = $userPasswordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );

            if (str_starts_with($hashedPassword, '$2y$')) {
                $hashedPassword = substr_replace($hashedPassword, '$2a$', 0, 4);
            }

            $user->setMotPasse($hashedPassword);
            // Set default role as CLIENT
            $user->setRole(Role::CLIENT);

            try {
                // Save the user
                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Votre compte a été créé avec succès!');
                return $this->redirectToRoute('app_login_page');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la création du compte.');
                return $this->redirectToRoute('app_register');
            }
        }

        return $this->render('register/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
