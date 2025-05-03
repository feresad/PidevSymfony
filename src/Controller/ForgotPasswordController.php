<?php

namespace App\Controller;

use App\Form\ForgotPasswordType;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mime\Email;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Form\ResetPasswordFormType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\Utilisateur;


class ForgotPasswordController extends AbstractController
{


    #[Route('/mot-passe-oublie', name: 'app_forgot_password')]
    public function index(
        Request $request,
        UtilisateurRepository $userRepository,
        MailerInterface $mailer
    ): Response {
        $form = $this->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user = $userRepository->findOneBy(['email' => $email]);

            if ($user) {
                // Generate 6-digit OTP
                $otp = random_int(100000, 999999);

                // Store email + OTP + expiration (optional) in session
                $session = $request->getSession();
                $session->set('reset_password_email', $email);
                $session->set('reset_password_otp', $otp);
                $session->set('reset_password_otp_expiration', (new \DateTime('+10 minutes'))->getTimestamp());
                $logoUrl = "https://i.postimg.cc/wxcZgCYH/level.png";
                // Send email
                $emailMessage = (new Email())
                    ->from('levelopcorporation@gmail.com')
                    ->to($email)
                    ->subject('Code de vérification (OTP)')
                    ->html(
                        "<html>" .
                            "<body style='background-color: #1B1B1B; color: #ffffff; font-family: Arial, sans-serif; text-align: center; padding: 20px;'>" .
                            "<div style='max-width: 500px; margin: auto; background-color: #2A2A2A; padding: 20px; border-radius: 10px;'>" .
                            "<img src='" . $logoUrl . "' alt='Logo LevelOP' style='max-width: 150px; margin-bottom: 10px;'>" .
                            "<h2 style='color: #ffffff;'>Bonjour,</h2>" .
                            "<p style='font-size: 16px;'>Utilisez le code ci-dessous pour vérifier votre identité :</p>" .
                            "<div style='background-color: #000000; padding: 10px; border-radius: 5px;'>" .
                            "<h1 style='color: #4CAF50; font-size: 32px;'>" . $otp . "</h1>" .
                            "</div>" .
                            "<p style='font-size: 14px; color: #aaaaaa;'>Si vous n'avez pas demandé ce code, veuillez ignorer cet e-mail.</p>" .
                            "<hr style='border: 1px solid #444;'>" .
                            "<p style='font-size: 12px; color: #777;'>Cordialement, <br> L'équipe LevelOP</p>" .
                            "</div>" .
                            "</body>" .
                            "</html>"
                    );


                $mailer->send($emailMessage);

                // Redirect to OTP verification page
                return $this->redirectToRoute('app_verify_otp');
            }

            $this->addFlash('error', 'Email introuvable. Veuillez vérifier votre adresse email.');
        }

        return $this->render('forgot_password/forgotPassword.html.twig', [
            'form' => $form->createView(),
            'image_base_url'=> $this->getParameter('image_base_url'),
        ]);
    }


    #[Route('/forgot-password', name: 'forgot_password', methods: ['POST'])]
    public function forgotPassword(
        Request $request,
        MailerInterface $mailer,
        SessionInterface $session
    ): Response {
        $email = $request->request->get('email');

        // Simulate user existence check (in real code, verify the user exists if needed)

        $otp = random_int(100000, 999999);

        // Store OTP & email in session
        $session->set('otp_email', $email);
        $session->set('otp_code', $otp);
        $session->set('otp_expires', (new \DateTime())->modify('+10 minutes')->getTimestamp());
        $logoUrl = "https://i.postimg.cc/nhVJRFZ5/logo.png";
        // Send email
        $emailMessage = (new Email())
            ->from('levelopcorporation@gmail.com')
            ->to($email)
            ->subject('Code de vérification (OTP)')
            ->html(
                "<html>" +
                    "<body style='background-color: #1B1B1B; color: #ffffff; font-family: Arial, sans-serif; text-align: center; padding: 20px;'>" +
                    "<div style='max-width: 500px; margin: auto; background-color: #2A2A2A; padding: 20px; border-radius: 10px;'>" +
                    "<img src='" + $logoUrl + "' alt='Logo LevelOP' style='max-width: 150px; margin-bottom: 10px;'>" +
                    "<h2 style='color: #ffffff;'>Bonjour,</h2>" +
                    "<p style='font-size: 16px;'>Utilisez le code ci-dessous pour vérifier votre identité :</p>" +
                    "<div style='background-color: #000000; padding: 10px; border-radius: 5px;'>" +
                    "<h1 style='color: #4CAF50; font-size: 32px;'>" + $otp + "</h1>" +
                    "</div>" +
                    "<p style='font-size: 14px; color: #aaaaaa;'>Si vous n'avez pas demandé ce code, veuillez ignorer cet e-mail.</p>" +
                    "<hr style='border: 1px solid #444;'>" +
                    "<p style='font-size: 12px; color: #777;'>Cordialement, <br> L'équipe LevelOP</p>" +
                    "</div>" +
                    "</body>" +
                    "</html>"
            );


        $mailer->send($emailMessage);

        return $this->json(['message' => 'OTP sent to your email']);
    }
    #[Route('/verify-otp', name: 'verify_otp', methods: ['POST'])]
    public function verifyOtps(Request $request, SessionInterface $session): Response
    {
        $inputEmail = $request->request->get('email');
        $inputOtp = $request->request->get('otp');

        $storedEmail = $session->get('otp_email');
        $storedOtp = $session->get('otp_code');
        $expiresAt = $session->get('otp_expires');

        if (!$storedEmail || !$storedOtp || !$expiresAt) {
            return $this->json(['error' => 'No OTP session found'], 400);
        }

        if ($inputEmail !== $storedEmail || $inputOtp != $storedOtp) {
            return $this->json(['error' => 'Invalid OTP or email'], 400);
        }

        if (time() > $expiresAt) {
            return $this->json(['error' => 'OTP expired'], 400);
        }

        // Optionally clear OTP from session after successful verification
        $session->remove('otp_email');
        $session->remove('otp_code');
        $session->remove('otp_expires');

        return $this->json(['message' => 'OTP verified successfully']);
    }
    #[Route('/verifier-code', name: 'app_verify_otp')]
    public function verifyOtp(Request $request): Response
    {
        return $this->render('verify_code/verifyCode.html.twig');
    }

    #[Route('/check-otp', name: 'app_check_otp', methods: ['POST'])]
    public function checkOtp(Request $request): Response
    {
        $submittedOtp = $request->request->get('otp');
        $session = $request->getSession();

        $storedOtp = $session->get('reset_password_otp');
        $expiration = $session->get('reset_password_otp_expiration');

        if (!$storedOtp || !$expiration || time() > $expiration) {
            $this->addFlash('error', 'Code expiré ou invalide.');
            return $this->redirectToRoute('app_verify_otp');
        }

        if ($submittedOtp != $storedOtp) {
            $this->addFlash('error', 'Code incorrect.');
            return $this->redirectToRoute('app_verify_otp');
        }

        // OTP is valid – proceed to password reset form (you can create this next)
        return $this->redirectToRoute('app_reset_password_form');
    }


    #[Route('/reset-password', name: 'app_reset_password_form')]
    public function resetPasswordForm(
        Request $request,
        SessionInterface $session,
        UtilisateurRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $email = $session->get('reset_password_email');
        if (!$email) {
            $this->addFlash('error', 'Session expirée. Veuillez recommencer.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $user = $userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);

            // Correction pour l’algorithme comme dans register
            if (str_starts_with($hashedPassword, '$2y$')) {
                $hashedPassword = substr_replace($hashedPassword, '$2a$', 0, 4);
            }

            $user->setMotPasse($hashedPassword);

            $entityManager->flush();

            // Clean session
            $session->remove('reset_password_email');
            $session->remove('reset_password_otp');
            $session->remove('reset_password_otp_expiration');

            $this->addFlash('success', 'Mot de passe réinitialisé avec succès.');
            return $this->redirectToRoute('app_login_page');
        }

        return $this->render('reset_password/reset_password.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }
}
