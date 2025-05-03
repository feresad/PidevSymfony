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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mime\Email;

class RegisterController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $userPasswordHasher, 
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        SessionInterface $session
    ): Response
    {
        $user = new Utilisateur();
        $form = $this->createForm(RegisterFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Store user data in session
            $session->set('registration_data', [
                'email' => $form->get('email')->getData(),
                'nom' => $form->get('nom')->getData(),
                'prenom' => $form->get('prenom')->getData(),
                'nickname' => $form->get('nickname')->getData(),
                'numero' => $form->get('numero')->getData(),
                'plainPassword' => $form->get('plainPassword')->getData(),
            ]);

            // Generate OTP
            $otp = random_int(100000, 999999);
            
            // Store OTP in session
            $session->set('registration_otp', $otp);
            $session->set('registration_otp_expiration', (new \DateTime('+10 minutes'))->getTimestamp());

            // Send verification email
            $logoUrl = "https://i.postimg.cc/wxcZgCYH/level.png";
            $emailMessage = (new Email())
                ->from('levelopcorporation@gmail.com')
                ->to($form->get('email')->getData())
                ->subject('Code de vérification (OTP)')
                ->html(
                    "<html>" .
                        "<body style='background-color: #1B1B1B; color: #ffffff; font-family: Arial, sans-serif; text-align: center; padding: 20px;'>" .
                        "<div style='max-width: 500px; margin: auto; background-color: #2A2A2A; padding: 20px; border-radius: 10px;'>" .
                        "<img src='" . $logoUrl . "' alt='Logo LevelOP' style='max-width: 150px; margin-bottom: 10px;'>" .
                        "<h2 style='color: #ffffff;'>Bienvenue sur LevelOP!</h2>" .
                        "<p style='font-size: 16px;'>Utilisez le code ci-dessous pour vérifier votre compte :</p>" .
                        "<div style='background-color: #000000; padding: 10px; border-radius: 5px;'>" .
                        "<h1 style='color: #4CAF50; font-size: 32px;'>" . $otp . "</h1>" .
                        "</div>" .
                        "<p style='font-size: 14px; color: #aaaaaa;'>Si vous n'avez pas créé ce compte, veuillez ignorer cet e-mail.</p>" .
                        "<hr style='border: 1px solid #444;'>" .
                        "<p style='font-size: 12px; color: #777;'>Cordialement, <br> L'équipe LevelOP</p>" .
                        "</div>" .
                        "</body>" .
                        "</html>"
                );

            $mailer->send($emailMessage);

            $this->addFlash('success', 'Un code de vérification a été envoyé à votre email.');
            return $this->redirectToRoute('app_verify_registration');
        }

        return $this->render('register/register.html.twig', [
            'registrationForm' => $form->createView(),
            'image_base_url' => $this->getParameter('image_base_url'),
        ]);
    }

    #[Route('/verify-registration', name: 'app_verify_registration')]
    public function verifyRegistration(Request $request, SessionInterface $session): Response
    {
        // Check if registration data exists in session
        if (!$session->has('registration_data')) {
            $this->addFlash('error', 'Session expirée. Veuillez recommencer l\'inscription.');
            return $this->redirectToRoute('app_register');
        }

        return $this->render('register/verify_registration.html.twig',[
            'image_base_url' => $this->getParameter('image_base_url'),
        ]);
    }

    #[Route('/check-registration-otp', name: 'app_check_registration_otp', methods: ['POST'])]
    public function checkRegistrationOtp(
        Request $request, 
        SessionInterface $session,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager
    ): Response
    {
        $submittedOtp = $request->request->get('otp');
        $registrationData = $session->get('registration_data');
        
        if (!$registrationData) {
            $this->addFlash('error', 'Session expirée. Veuillez recommencer l\'inscription.');
            return $this->redirectToRoute('app_register');
        }

        $storedOtp = $session->get('registration_otp');
        $expiration = $session->get('registration_otp_expiration');

        if (!$storedOtp || !$expiration || time() > $expiration) {
            $this->addFlash('error', 'Code expiré ou invalide.');
            return $this->redirectToRoute('app_verify_registration');
        }

        if ($submittedOtp != $storedOtp) {
            $this->addFlash('error', 'Code incorrect.');
            return $this->redirectToRoute('app_verify_registration');
        }

        // Create and save the user
        $user = new Utilisateur();
        $user->setEmail($registrationData['email']);
        $user->setNom($registrationData['nom']);
        $user->setPrenom($registrationData['prenom']);
        $user->setNickname($registrationData['nickname']);
        $user->setNumero($registrationData['numero']);
        
        // Hash the password
        $hashedPassword = $userPasswordHasher->hashPassword(
            $user,
            $registrationData['plainPassword']
        );

        if (str_starts_with($hashedPassword, '$2y$')) {
            $hashedPassword = substr_replace($hashedPassword, '$2a$', 0, 4);
        }

        $user->setMotPasse($hashedPassword);
        $user->setRole(Role::CLIENT);

        try {
            $entityManager->persist($user);
            $entityManager->flush();

            // Clear session data
            $session->remove('registration_data');
            $session->remove('registration_otp');
            $session->remove('registration_otp_expiration');

            $this->addFlash('success', 'Votre compte a été créé avec succès!');
            return $this->redirectToRoute('app_login_page');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la création du compte.');
            return $this->redirectToRoute('app_register');
        }
    }
}
