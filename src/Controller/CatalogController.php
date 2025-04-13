<?php

namespace App\Controller;

use App\Entity\Stock;
use App\Entity\Review;
use App\Form\ProduitType;
use App\Form\StockType;
use App\Repository\StockRepository;
use App\Repository\ReviewRepository;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/admin/catalog')]
class CatalogController extends AbstractController
{
    #[Route('/', name: 'app_catalog_index', methods: ['GET'])]
    public function index(Request $request, StockRepository $stockRepository): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 8; // Number of products per page
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', 'nom_asc');

        // Get products with search and sorting
        $products = $stockRepository->findAllProductsWithDetailsPaginated($page, $limit, $search, $sort);
        $totalProducts = $stockRepository->getTotalProducts($search);
        $maxPages = ceil($totalProducts / $limit);
        
        return $this->render('catalog/index.html.twig', [
            'products' => $products,
            'current_page' => $page,
            'max_pages' => $maxPages,
            'search' => $search,
            'sort' => $sort,
            'image_base_url' => $this->getParameter('image_base_url')
        ]);
    }

    #[Route('/product/{id}/edit-stock', name: 'app_catalog_edit_stock', methods: ['GET', 'POST'])]
    public function editStock(
        int $id,
        Request $request,
        StockRepository $stockRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $stock = $stockRepository->findOneByProduitId($id);
        
        if (!$stock) {
            throw $this->createNotFoundException('Product not found');
        }
    
        // Create the stock form
        $stockForm = $this->createForm(StockType::class, $stock);
    
        // Handle form submission
        $stockForm->handleRequest($request);
        if ($stockForm->isSubmitted() && $stockForm->isValid()) {
            try {
                /** @var UploadedFile $imageFile */
                $imageFile = $stockForm->get('fichierImage')->getData();
    
                if ($imageFile) {
                    $newFilename = uniqid().'.'.$imageFile->guessExtension();
                    $uploadDir = $this->getParameter('dossier_upload');
    
                    // Remove old image if it exists
                    if ($stock->getImage()) {
                        $oldFile = $uploadDir . '/' . $stock->getImage();
                        if (file_exists($oldFile)) {
                            unlink($oldFile);
                        }
                    }
    
                    // Move the new image
                    $imageFile->move($uploadDir, $newFilename);
                    $stock->setImage($newFilename);
                }
    
                $entityManager->flush();
                $this->addFlash('success', 'Stock mis à jour avec succès.');
                return $this->redirectToRoute('app_catalog_edit_stock', ['id' => $id]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la mise à jour du stock.');
            }
        }
    
        return $this->render('catalog/editstock.html.twig', [
            'stock_form' => $stockForm->createView(),
            'product' => $stock,
            'image_base_url' => $this->getParameter('image_base_url')
        ]);
    }

    #[Route('/product/{id}/edit-produit', name: 'app_catalog_edit_produit', methods: ['GET', 'POST'])]
    public function editProduit(
        int $id,
        Request $request,
        StockRepository $stockRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $stock = $stockRepository->findOneByProduitId($id);
        
        if (!$stock) {
            throw $this->createNotFoundException('Product not found');
        }
        
        // Access the Produit entity
        $produit = $stock->getProduit();
    
        // Create the produit form
        $produitForm = $this->createForm(ProduitType::class, $produit);
    
        // Handle form submission
        $produitForm->handleRequest($request);
        if ($produitForm->isSubmitted() && $produitForm->isValid()) {
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Produit mis à jour avec succès.');
                return $this->redirectToRoute('app_catalog_edit_produit', ['id' => $id]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la mise à jour du produit.');
            }
        }
    
        // Render the form with validation errors if any
        return $this->render('catalog/editproduit.html.twig', [
            'produit_form' => $produitForm->createView(),
            'product' => $stock,
        ]);
    }
    
    #[Route('/product/{id}', name: 'app_catalog_product_details', methods: ['GET', 'POST'])]
    public function productDetails(
        int $id,
        Request $request,
        StockRepository $stockRepository,
        ReviewRepository $reviewRepository,
        CommandeRepository $commandeRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Get product details
        $product = $stockRepository->findOneByProduitId($id);
        
        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        // Get reviews and customers who ordered
        $reviews = $reviewRepository->findByProduitId($id);
        $customers = $commandeRepository->findCustomersByProductId($id);
        
        // Handle review submission if user is logged in and has purchased the product
        $canReview = false;
        $user = $this->getUser();
        
        if ($user) {
            $hasPurchased = $commandeRepository->createQueryBuilder('c')
                ->where('c.utilisateur = :user')
                ->andWhere('c.produit = :produit')
                ->andWhere('c.status = :status')
                ->setParameter('user', $user)
                ->setParameter('produit', $product->getProduit())
                ->setParameter('status', 'terminé')
                ->getQuery()
                ->getResult();
            
            $canReview = !empty($hasPurchased);

            if ($request->isMethod('POST') && $canReview) {
                $comment = $request->request->get('comment');
                if (!empty($comment)) {
                    $review = new Review();
                    $review->setUtilisateur($user)
                           ->setProduit($product->getProduit())
                           ->setComment($comment);

                    $entityManager->persist($review);
                    $entityManager->flush();

                    $this->addFlash('success', 'Votre avis a été ajouté avec succès!');
                    return $this->redirectToRoute('app_catalog_product_details', ['id' => $id]);
                }
            }
        }

        return $this->render('catalog/details.html.twig', [
            'product' => $product,
            'reviews' => $reviews,
            'customers' => $customers,
            'canReview' => $canReview,
            'image_base_url' => $this->getParameter('image_base_url')
        ]);
    }
}