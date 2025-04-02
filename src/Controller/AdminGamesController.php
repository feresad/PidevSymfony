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
    public function index(GamesRepository $gamesRepository): Response
    {
        $games = $gamesRepository->findAll();

        return $this->render('admin_games/index.html.twig', [
            'games' => $games,
        ]);
    }

    #[Route('/new', name: 'admin_games_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $game = new Games();
        $form = $this->createForm(GameFormType::class, $game);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image_path')->getData();
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                try {
                    $imageFile->move(
                        $this->getParameter('uploads_directory'),
                        $newFilename
                    );
                    $game->setImagePath($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload image: ' . $e->getMessage());
                    return $this->redirectToRoute('admin_games_new');
                }
            }
    
            $entityManager->persist($game);
            $entityManager->flush();
    
            $this->addFlash('success', 'Game added successfully!');
            return $this->redirectToRoute('admin_games_dashboard');
        }
    
        return $this->render('admin_games/manage.html.twig', [
            'form' => $form->createView(),
            'game' => null,
        ]);
    }

    #[Route('/edit/{id}', name: 'admin_games_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Games $game, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(GameFormType::class, $game);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file upload
            $imageFile = $form->get('image_path')->getData();
            if ($imageFile) {
                // Delete old image if exists
                if ($game->getImagePath()) {
                    $oldImagePath = $this->getParameter('uploads_directory') . '/' . $game->getImagePath();
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
                    return $this->redirectToRoute('admin_games_edit', ['id' => $game->getGameId()]);
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Game updated successfully!');
            return $this->redirectToRoute('admin_games_dashboard');
        }

        return $this->render('admin_games/manage.html.twig', [
            'form' => $form->createView(),
            'game' => $game,
        ]);
    }

    #[Route('/delete/{id}', name: 'admin_games_delete', methods: ['POST'])]
    public function delete(Request $request, Games $game, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $game->getGameId(), $request->request->get('_token'))) {
            // Delete image if exists
            if ($game->getImagePath()) {
                $imagePath = $this->getParameter('uploads_directory') . '/' . $game->getImagePath();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $entityManager->remove($game);
            $entityManager->flush();

            $this->addFlash('success', 'Game deleted successfully!');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_games_dashboard');
    }
}