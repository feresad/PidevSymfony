<?php

namespace App\Controller;

use App\Entity\Games;
use App\Form\GameFormType;
use App\Repository\GamesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/admin/games')]
class AdminGamesController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_games_dashboard', methods: ['GET'])]
    public function index(Request $request, GamesRepository $gamesRepository): Response
    {
        $searchTerm = $request->query->get('search', '');

        if ($searchTerm) {
            $games = $gamesRepository->findByName($searchTerm);
        } else {
            $games = $gamesRepository->findAll();
        }

        return $this->render('admin_games/index.html.twig', [
            'games' => $games,
            'searchTerm' => $searchTerm,
            'image_base_url2' => $this->getParameter('image_base_url2'),
            'image_base_url' => $this->getParameter('image_base_url'),
        ]);
    }

    #[Route('/new', name: 'admin_games_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $game = new Games();
        $form = $this->createForm(GameFormType::class, $game);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $errors = [];

            $gameName = $form->get('game_name')->getData();
            if (empty(trim($gameName))) {
                $errors['game_name'] = 'Game name cannot be empty.';
            }

            $gameType = $form->get('game_type')->getData();
            if (!$gameType) {
                $errors['game_type'] = 'Game type must be selected.';
            }

            $imageFile = $form->get('image_path')->getData();
            if ($imageFile) {
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                $extension = $imageFile->guessExtension();
                if (!in_array(strtolower($extension), $allowedExtensions)) {
                    $errors['image_path'] = 'Image must be a JPG, PNG, or GIF file.';
                }
                if ($imageFile->getSize() > 5 * 1024 * 1024) { // 5MB limit
                    $errors['image_path'] = 'Image size must not exceed 5MB.';
                }
            }

            if (empty($errors)) {
                if ($imageFile) {
                    $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                    try {
                        $imageFile->move(
                            $this->getParameter('uploads_directory2'),
                            $newFilename
                        );
                        $game->setImagePath($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Failed to upload image: ' . $e->getMessage());
                        return $this->render('admin_games/manage.html.twig', [
                            'form' => $form->createView(),
                            'game' => null,
                            'image_base_url' => $this->getParameter('image_base_url'),
                        ]);
                    }
                }

                try {
                    $entityManager->persist($game);
                    $entityManager->flush();
                    $this->addFlash('success', 'Game added successfully!');
                    return $this->redirectToRoute('admin_games_dashboard');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Error adding game: ' . $e->getMessage());
                }
            } else {
                foreach ($errors as $field => $message) {
                    $this->addFlash('error', $message);
                }
            }
        }

        return $this->render('admin_games/manage.html.twig', [
            'form' => $form->createView(),
            'game' => null,
            'image_base_url' => $this->getParameter('image_base_url'),
        ]); 
    }

    #[Route('/edit/{id}', name: 'admin_games_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Games $game, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(GameFormType::class, $game);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $errors = [];

            $gameName = $form->get('game_name')->getData();
            if (empty(trim($gameName))) {
                $errors['game_name'] = 'Game name cannot be empty.';
            }

            $gameType = $form->get('game_type')->getData();
            if (!$gameType) {
                $errors['game_type'] = 'Game type must be selected.';
            }

            $imageFile = $form->get('image_path')->getData();
            if ($imageFile) {
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                $extension = $imageFile->guessExtension();
                if (!in_array(strtolower($extension), $allowedExtensions)) {
                    $errors['image_path'] = 'Image must be a JPG, PNG, or GIF file.';
                }
                if ($imageFile->getSize() > 5 * 1024 * 1024) { // 5MB limit
                    $errors['image_path'] = 'Image size must not exceed 5MB.';
                }
            }

            if (empty($errors)) {
                if ($imageFile) {
                    if ($game->getImagePath()) {
                        $oldImagePath = $this->getParameter('image_base_url') . $game->getImagePath();
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }

                    $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                    try {
                        $imageFile->move(
                            $this->getParameter('uploads_directory'),
                            $newFilename
                        );
                        $game->setImagePath($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Failed to upload image: ' . $e->getMessage());
                        return $this->render('admin_games/manage.html.twig', [
                            'form' => $form->createView(),
                            'game' => $game,
                        ]);
                    }
                }

                try {
                    $entityManager->flush();
                    $this->addFlash('success', 'Game updated successfully!');
                    return $this->redirectToRoute('admin_games_dashboard');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Error updating game: ' . $e->getMessage());
                }
            } else {
                foreach ($errors as $field => $message) {
                    $this->addFlash('error', $message);
                }
            }
        }

        return $this->render('admin_games/manage.html.twig', [
            'form' => $form->createView(),
            'game' => $game,
            'image_base_url' => $this->getParameter('image_base_url'),
            'image_base_url2' => $this->getParameter('image_base_url2'),
        ]);
    }

    #[Route('/delete/{id}', name: 'admin_games_delete', methods: ['POST'])]
    public function delete(Request $request, Games $game, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $game->getGameId(), $request->request->get('_token'))) {
            try {
                if ($game->getImagePath()) {
                    $imagePath = $this->getParameter('uploads_directory') . '/' . $game->getImagePath();
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }

                $entityManager->remove($game);
                $entityManager->flush();
                $this->addFlash('success', 'Game deleted successfully!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error deleting game: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_games_dashboard',[
            'image_base_url' => $this->getParameter('image_base_url'),
        ]);
    }
}