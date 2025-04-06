<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\StockRepository;
use App\Service\SystemSpecsService;

class ShopController extends AbstractController
{
    private $systemSpecsService;

    public function __construct(SystemSpecsService $systemSpecsService)
    {
        $this->systemSpecsService = $systemSpecsService;
    }

    #[Route('/store', name: 'app_store')]
    public function store(StockRepository $stockRepository): Response
    {
        $products = $stockRepository->findAllProductsWithDetails();
        $popularProducts = $stockRepository->findMostPopularProducts();
        
        return $this->render('shop/store.html.twig', [
            'products' => $products,
            'popular_products' => $popularProducts,
            'image_base_url' => $this->getParameter('image_base_url'),
        ]);
    }

    #[Route('/store/product/{id}', name: 'app_store_product')]
    public function storeProduct(int $id, StockRepository $stockRepository): Response
    {
        // Get the product data using the repository
        $product = $stockRepository->findOneProductWithDetails($id);
        
        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }
        
        // Get system specifications
        $systemSpecs = $this->systemSpecsService->getSystemSpecs();
        
        return $this->render('shop/store-product.html.twig', [
            'produit' => $product,
            'systemSpecs' => $systemSpecs,
            'image_base_url' => $this->getParameter('image_base_url')
        ]);
    }
}