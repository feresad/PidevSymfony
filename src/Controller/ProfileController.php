<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\ProfileType;
use App\Form\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class ProfileController extends AbstractController
{
    private $entityManager;
    private $passwordHasher;
    private $security;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        Security $security
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->security = $security;
    }

    #[Route('/profile', name: 'app_profile')]
    public function index(Request $request): Response
    {
        $user = $this->security->getUser();
        if (!$user instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        $profileForm = $this->createForm(ProfileType::class, $user);
        $passwordForm = $this->createForm(ChangePasswordType::class);

        $profileForm->handleRequest($request);
        if ($profileForm->isSubmitted() && $profileForm->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès!');
            return $this->redirectToRoute('app_profile');
        }

        $passwordForm->handleRequest($request);
        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $data = $passwordForm->getData();
            
            if (!$this->passwordHasher->isPasswordValid($user, $data['currentPassword'])) {
                $this->addFlash('error', 'Mot de passe actuel incorrect');
                return $this->redirectToRoute('app_profile');
            }

            $user->setMotPasse(
                $this->passwordHasher->hashPassword($user, $data['newPassword'])
            );

            $this->entityManager->flush();
            $this->addFlash('success', 'Mot de passe mis à jour avec succès!');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/profile.html.twig', [
            'profileForm' => $profileForm->createView(),
            'passwordForm' => $passwordForm->createView(),
            'user' => $user
        ]);
    }

    #[Route('/profile/update', name: 'app_profile_update', methods: ['POST'])]
    public function update(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user instanceof Utilisateur) {
            return $this->json(['success' => false, 'message' => 'User not found']);
        }

        $data = json_decode($request->getContent(), true);
        
        if (isset($data['nom'])) {
            $user->setNom($data['nom']);
        }
        if (isset($data['prenom'])) {
            $user->setPrenom($data['prenom']);
        }
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['nickname'])) {
            $user->setNickname($data['nickname']);
        }
        if (isset($data['numero'])) {
            $user->setNumero($data['numero']);
        }
        if (isset($data['mot_passe'])) {
            $user->setMotPasse($data['mot_passe']);
        }

        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/profile/upload-image', name: 'app_profile_upload_image', methods: ['POST'])]
    public function uploadImage(Request $request): JsonResponse
    {
        try {
            $user = $this->security->getUser();
            if (!$user instanceof Utilisateur) {
                return $this->json(['success' => false, 'message' => 'User not found']);
            }

            $data = json_decode($request->getContent(), true);
            $imageData = $data['image'] ?? null;

            if (!$imageData) {
                return $this->json(['success' => false, 'message' => 'No image data provided']);
            }

            // Set the upload directory
            $uploadDir = 'C:/xampp/htdocs/img/';
            if (!file_exists($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    return $this->json(['success' => false, 'message' => 'Failed to create upload directory']);
                }
            }

            // Generate unique filename
            $filename = time() . uniqid() . '.png';
            $filepath = $uploadDir . $filename;

            // Remove data URL prefix and decode base64
            $imageData = preg_replace('#^data:image/\w+;base64,#i', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $decodedImage = base64_decode($imageData);

            if (file_put_contents($filepath, $decodedImage)) {
                // Delete old photo if exists
                $oldPhoto = $user->getPhoto();
                if ($oldPhoto && $oldPhoto !== 'default-avatar.jpg') {
                    $oldPhotoPath = $uploadDir . $oldPhoto;
                    if (file_exists($oldPhotoPath)) {
                        unlink($oldPhotoPath);
                    }
                }

                // Store only the filename in the database
                $user->setPhoto($filename);
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $this->json([
                    'success' => true,
                    'photo' => $filename,
                    'message' => 'Image uploaded successfully'
                ]);
            }

            return $this->json(['success' => false, 'message' => 'Failed to save image']);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error uploading image: ' . $e->getMessage()
            ]);
        }
    }
} 