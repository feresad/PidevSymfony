<?php

namespace App\Controller;

use App\Entity\Stock;
use App\Form\StockType;
use App\Repository\StockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/stock')]
class StockController extends AbstractController
{
    private string $dossierUpload;

    public function __construct(string $dossierUpload)
    {
        $this->dossierUpload = $dossierUpload;
    }

    #[Route('/', name: 'app_stock_index', methods: ['GET'])]
    public function index(StockRepository $stockRepository): Response
    {
        return $this->render('stock/index.html.twig', [
            'stocks' => $stockRepository->findAll(),
            'image_base_url' => $this->getParameter('image_base_url'),
        ]);
    }

    #[Route('/new', name: 'app_stock_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $stock = new Stock();
        $form = $this->createForm(StockType::class, $stock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file upload if there's an image
            $imageFile = $form->get('fichierImage')->getData();
            if ($imageFile) {
                // Get original filename
                $filename = $imageFile->getClientOriginalName();
                
                try {
                    $imageFile->move(
                        $this->dossierUpload,
                        $filename
                    );
                    $stock->setImage($filename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image : ' . $e->getMessage());
                    return $this->redirectToRoute('app_stock_new');
                }
            }

            $entityManager->persist($stock);
            $entityManager->flush();

            $this->addFlash('success', 'Stock ajouté avec succès');
            return $this->redirectToRoute('app_stock_index',['image_base_url' => $this->getParameter('image_base_url')]);
        }
    }

    #[Route('/{id}/modifier', name: 'app_stock_edit', methods: ['GET', 'POST'])]
    public function modifier(Request $request, Stock $stock, EntityManagerInterface $entityManager): Response
    {
        $ancienneImage = $stock->getImage();
        $form = $this->createForm(StockType::class, $stock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $fichierImage */
            $fichierImage = $form->get('fichierImage')->getData();

            if ($fichierImage) {
                // Suppression de l'ancienne image
                if ($ancienneImage && file_exists($this->dossierUpload.$ancienneImage)) {
                    unlink($this->dossierUpload.$ancienneImage);
                }

                $nomOriginal = pathinfo($fichierImage->getClientOriginalName(), PATHINFO_FILENAME);
                $nouveauNom = $nomOriginal.'-'.uniqid().'.'.$fichierImage->guessExtension();

                try {
                    $fichierImage->move(
                        $this->dossierUpload,
                        $nouveauNom
                    );
                    $stock->setImage($nouveauNom);
                } catch (FileException $e) {
                    $this->addFlash('erreur', 'Erreur lors du téléchargement de l\'image : '.$e->getMessage());
                    return $this->redirectToRoute('app_stock_edit', ['id' => $stock->getId()]);
                }
            }

            $entityManager->flush();
            $this->addFlash('succes', 'Stock mis à jour avec succès !');
            return $this->redirectToRoute('app_stock_index');
        }

        return $this->render('stock/edit.html.twig', [
            'stock' => $stock,
            'form' => $form->createView(),
            'image_base_url' => $this->getParameter('image_base_url'),
        ]);
    }

    #[Route('/{id}', name: 'app_stock_delete', methods: ['POST'])]
    public function delete(Request $request, Stock $stock, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$stock->getId(), $request->request->get('_token'))) {
            $entityManager->remove($stock);
            $entityManager->flush();
            $this->addFlash('success', 'Stock supprimé avec succès');
        }

        return $this->redirectToRoute('app_stock_index');
    }
}