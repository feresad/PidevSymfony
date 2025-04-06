<?php

namespace App\Controller;

use App\Entity\CategorieEvent;
use App\Form\CategorieEventType;
use App\Repository\Categorie_eventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Id;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
#[Route("/categorie")]
final class CategorieEventController extends AbstractController
{
    #[Route('/all',name: 'categorie_list')]
    public function gettAll(Categorie_eventRepository $repo):Response{
        $CategorieEvent = $repo->findAll();
        return $this->render('categorie_event/listeCategorieEvent.html.twig', [
            "CategorieEvent"=>$CategorieEvent,
        ]);
    }
    #[Route('/add', name: 'categorie_ajouter')]
    public function ajouter(Request $request, EntityManagerInterface $entityManager): Response
    {
        $categorie = new CategorieEvent();
        $form = $this->createForm(CategorieEventType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($categorie);
            $entityManager->flush();

            $this->addFlash('success', 'Catégorie ajoutée avec succès !');
            return $this->redirectToRoute('categorie_list_admin');
        }

        return $this->render('categorie_event/ajouterCategorieEvent.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit/{id}', name: 'categorie_modifier')]
    public function modifier(int $id, Request $request, Categorie_eventRepository $repo, EntityManagerInterface $entityManager): Response
    {
        $categorie = $repo->find($id);

        if (!$categorie) {
            $this->addFlash('error', 'Catégorie non trouvée.');
            return $this->redirectToRoute('categorie_list');
        }

        $form = $this->createForm(CategorieEventType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Catégorie modifiée avec succès !');
            return $this->redirectToRoute('categorie_list');
        }

        return $this->render('categorie_event/modifierCategorieEvent.html.twig', [
            'form' => $form->createView(),
            'categorie' => $categorie,
        ]);
    }

    #[Route('/show/{id}', name: 'categorie_voir')]
    public function voir(int $id, Categorie_eventRepository $repo): Response
    {
        $categorie = $repo->find($id);

        if (!$categorie) {
            $this->addFlash('error', 'Catégorie non trouvée.');
            return $this->redirectToRoute('categorie_list');
        }

        return $this->render('categorie_event/voirCategorieEvent.html.twig', [
            'categorie' => $categorie,
        ]);
    }

    #[Route('/delete/{id}', name: 'delete_categorie')]
    public function deleteCategorie(int $id, Categorie_eventRepository $repo, EntityManagerInterface $entityManager): Response
    {
        $categorie = $repo->find($id);

        if (!$categorie) {
            $this->addFlash('error', 'Catégorie non trouvée.');
            return $this->redirectToRoute('categorie_list');
        }

        $entityManager->remove($categorie);
        $entityManager->flush();

        $this->addFlash('success', 'Catégorie supprimée avec succès !');
        return $this->redirectToRoute('categorie_list');
    }
    #[Route('/admin/all',name: 'categorie_list_admin')]
    public function getAll(Categorie_eventRepository $repo):Response{
        $CategorieEvent = $repo->findAll();
        return $this->render('categorie_event/listeCategorieEventadmin.html.twig', [
            "CategorieEvent"=>$CategorieEvent,
        ]);
    }
    #[Route('/admin/show/{id}', name: 'categorie_voir_admin')]
    public function DetailsAdmin(int $id, Categorie_eventRepository $repo): Response
    {
        $categorie = $repo->find($id);

        if (!$categorie) {
            $this->addFlash('error', 'Catégorie non trouvée.');
            return $this->redirectToRoute('categorie_list');
        }

        return $this->render('categorie_event/voirCategorieEventadmin.html.twig', [
            'categorie' => $categorie,
        ]);
    }
}

