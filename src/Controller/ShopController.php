<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\StockRepository;
use App\Repository\ReviewRepository;
use App\Repository\CommandeRepository;
use App\Entity\Review;
use App\Entity\Commande;
use App\Entity\Stock;
use App\Service\SystemSpecsService;
use App\Service\GeminiFpsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class ShopController extends AbstractController
{
    private $systemSpecsService;
    private $entityManager;
    private $geminiFpsService;
    private $mailer;

    public function __construct(
        SystemSpecsService $systemSpecsService, 
        EntityManagerInterface $entityManager,
        GeminiFpsService $geminiFpsService,
        MailerInterface $mailer
    ) {
        $this->systemSpecsService = $systemSpecsService;
        $this->entityManager = $entityManager;
        $this->geminiFpsService = $geminiFpsService;
        $this->mailer = $mailer;
    }

    #[Route('/store', name: 'app_store')]
    public function store(Request $request, StockRepository $stockRepository): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 4;
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', 'default');
        $isAjax = $request->query->getBoolean('ajax', false);
        
        $products = $stockRepository->findAllProductsWithDetailsPaginated($page, $limit, $search, $sort);
        $totalProducts = $stockRepository->getTotalProducts($search);
        $maxPages = ceil($totalProducts / $limit);
        
        $popularProducts = $stockRepository->findMostPopularProducts();
        
        if ($isAjax) {
            return $this->render('shop/store.html.twig', [
                'products' => $products,
                'popular_products' => $popularProducts,
                'image_base_url' => $this->getParameter('image_base_url'),
                'current_page' => $page,
                'max_pages' => $maxPages,
                'search' => $search,
                'sort' => $sort,
            ]);
        }
        
        return $this->render('shop/store.html.twig', [
            'products' => $products,
            'popular_products' => $popularProducts,
            'image_base_url' => $this->getParameter('image_base_url'),
            'current_page' => $page,
            'max_pages' => $maxPages,
            'search' => $search,
            'sort' => $sort,
        ]);
    }

    #[Route('/store/product/{id}', name: 'app_store_product', methods: ['GET', 'POST'])]
    public function storeProduct(
        int $id,
        StockRepository $stockRepository,
        ReviewRepository $reviewRepository,
        CommandeRepository $commandeRepository,
        Request $request
    ): Response {
        $stock = $stockRepository->findOneByProduitId($id);
        
        if (!$stock) {
            throw $this->createNotFoundException('Product not found');
        }

        $product = $stock->getProduit();
        $systemSpecs = $this->systemSpecsService->getSystemSpecs();
        $reviews = $reviewRepository->findByProduitId($product->getId());
        $reviewCount = $reviewRepository->countByProduitId($product->getId());
        $canReview = false;

        try {
            $recommendedSpecs = $this->geminiFpsService->getRecommendedSpecs($product->getNomProduit());
        } catch (\Exception $e) {
            $recommendedSpecs = null;
        }

        // FPS estimation for all GPUs
        $fpsEstimates = null;
        try {
            if (!empty($systemSpecs['cpu']['name']) && !empty($systemSpecs['ram']['total']) && !empty($systemSpecs['gpus'])) {
                $fpsEstimates = $this->geminiFpsService->estimateFps([
                    'cpu' => $systemSpecs['cpu']['name'],
                    'ram' => $systemSpecs['ram']['total'],
                    'gpu' => $systemSpecs['gpus'],
                    'game_name' => $product->getNomProduit(),
                ]);
            }
        } catch (\Exception $e) {
            $fpsEstimates = null;
        }

        $user = $this->getUser();
        if ($user) {
            $completedOrders = $commandeRepository->createQueryBuilder('c')
                ->where('c.utilisateur = :user')
                ->andWhere('c.produit = :produit')
                ->andWhere('c.status = :status')
                ->setParameter('user', $user)
                ->setParameter('produit', $product)
                ->setParameter('status', 'terminé')
                ->getQuery()
                ->getResult();
            $canReview = !empty($completedOrders);
        }
        if ($request->isMethod('POST') && $canReview) {
            $comment = $request->request->get('comment');
            if (!empty($comment)) {
                $review = new Review();
                $review->setUtilisateur($user)
                       ->setProduit($product)
                       ->setComment($comment);
                try {
                    $this->entityManager->persist($review);
                    $this->entityManager->flush();
                    $this->addFlash('success', 'Votre avis a été soumis avec succès !');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur s\'est produite lors de la soumission de votre avis.');
                }
                return $this->redirectToRoute('app_store_product', ['id' => $id]);
            } else {
                $this->addFlash('error', 'Le commentaire ne peut pas être vide.');
            }
        } elseif ($request->isMethod('POST') && !$canReview) {
            $this->addFlash('error', 'Vous devez acheter ce produit pour laisser un avis.');
        }

        return $this->render('shop/store-product.html.twig', [
            'stock' => $stock,
            'product' => $product,
            'systemSpecs' => $systemSpecs,
            'recommendedSpecs' => $recommendedSpecs,
            'image_base_url' => $this->getParameter('image_base_url'),
            'reviews' => $reviews,
            'reviewCount' => $reviewCount,
            'canReview' => $canReview,
            'fpsEstimates' => $fpsEstimates,
        ]);
    }

    #[Route('/store/payment/{id}', name: 'app_payment_page')]
    public function paymentPage(int $id, StockRepository $stockRepository): Response
    {
        $stock = $stockRepository->findOneByProduitId($id);
        
        if (!$stock) {
            throw $this->createNotFoundException('Product not found');
        }
        
        if ($stock->getQuantity() <= 0) {
            $this->addFlash('error', 'Ce produit est hors stock.');
            return $this->redirectToRoute('app_store_product', ['id' => $id]);
        }
        
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour commander.');
            return $this->redirectToRoute('app_login');
        }
        
        // Convert price from TND to EUR (assuming 1 TND = 0.29 EUR)
        $priceInEur = $stock->getPrixProduit() * 0.29;

        return $this->render('shop/payment.html.twig', [
            'stock' => $stock,
            'product' => $stock->getProduit(),
            'price_in_tnd' => $stock->getPrixProduit(), // Original price in TND for display
            'price_in_eur' => $priceInEur, // EUR price for Stripe
            'image_base_url' => $this->getParameter('image_base_url'),
            'stripe_public_key' => $this->getParameter('stripe_public_key'),
        ]);
    }

    #[Route('/store/process-payment/{id}', name: 'app_process_payment', methods: ['POST'])]
    public function processPayment(int $id, Request $request, EntityManagerInterface $entityManager, StockRepository $stockRepository): JsonResponse
    {
        try {
            // Initialize Stripe
            \Stripe\Stripe::setApiKey($this->getParameter('stripe_secret_key'));
            
            // Get the JSON data from the request
            $data = json_decode($request->getContent(), true);
            
            // Validate required data
            if (!isset($data['paymentMethodId'])) {
                throw new \Exception('Missing required payment information');
            }

            // Get the product details using the repository and route parameter
            $stock = $stockRepository->findOneByProduitId($id);
            if (!$stock) {
                throw new \Exception('Product not found');
            }

            $product = $stock->getProduit();

            // Convert price from TND to EUR for Stripe (assuming 1 TND = 0.29 EUR)
            $priceInEur = $stock->getPrixProduit() * 0.29;
            // Convert price to cents for Stripe (Stripe expects amounts in cents)
            $amountInCents = round($priceInEur * 100);

            try {
                // Create a PaymentIntent
                $paymentIntent = \Stripe\PaymentIntent::create([
                    'amount' => $amountInCents,
                    'currency' => 'eur', // Using EUR for payment processing
                    'payment_method' => $data['paymentMethodId'],
                    'confirmation_method' => 'manual',
                    'confirm' => true,
                    'return_url' => $this->generateUrl('app_payment_success', ['id' => 0], UrlGeneratorInterface::ABSOLUTE_URL),
                    'metadata' => [
                        'product_id' => $stock->getId(),
                        'product_name' => $product->getNomProduit(),
                        'price_tnd' => $stock->getPrixProduit()
                    ],
                    'description' => 'Purchase of ' . $product->getNomProduit() . ' (' . $stock->getPrixProduit() . ' TND)'
                ]);

                // If payment is successful
                if ($paymentIntent->status === 'succeeded') {
                    // Create new order
                    $commande = new Commande();
                    $commande->setUtilisateur($this->getUser());
                    $commande->setProduit($product);
                    $commande->setStatus('terminé');
                    // Note: createdAt is automatically set in the Commande constructor

                    // Update stock quantity
                    $newQuantity = $stock->getQuantity() - 1;
                    $stock->setQuantity($newQuantity);

                    // Save changes to database - we need to flush immediately
                    $entityManager->persist($commande);
                    $entityManager->persist($stock);
                    $entityManager->flush();
                    
                    // Store the order ID before any other operations
                    $orderId = $commande->getId();
                    
                    try {
                        // Send a confirmation email (if implemented)
                        // This will be processed asynchronously by the messenger system
                        $this->sendOrderConfirmationEmail($commande);
                    } catch (\Exception $emailException) {
                        // Log the email error but don't fail the payment
                        error_log('Error sending confirmation email: ' . $emailException->getMessage());
                    }

                    return new JsonResponse([
                        'success' => true,
                        'orderId' => $orderId
                    ]);
                } else {
                    return new JsonResponse([
                        'success' => false,
                        'error' => 'Le paiement a échoué'
                    ]);
                }

            } catch (\Stripe\Exception\CardException $e) {
                // Since it's a card error, we can show it to the customer
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Erreur de carte: ' . $e->getMessage()
                ]);
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Requête invalide: ' . $e->getMessage()
                ]);
            }

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    #[Route('/store/payment-success/{id}', name: 'app_payment_success')]
    public function paymentSuccess(int $id, CommandeRepository $commandeRepository): Response
    {
        $commande = $commandeRepository->find($id);
        
        if (!$commande) {
            throw $this->createNotFoundException('Commande not found');
        }
        
        if ($commande->getUtilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You do not have access to this order');
        }
        
        // Only update if not already marked as completed
        if ($commande->getStatus() !== 'terminé') {
            $commande->setStatus('terminé');
            $this->entityManager->persist($commande);
            $this->entityManager->flush();
        }
        
        return $this->render('shop/payment-success.html.twig', [
            'commande' => $commande,
            'image_base_url' => $this->getParameter('image_base_url')
        ]);
    }

    private function sendOrderConfirmationEmail(Commande $commande): void
    {
        // Only if there's email implementation
        if ($this->mailer) {
            // Get the product
            $produit = $commande->getProduit();
            
            // Render the email template
            $emailHtml = $this->renderView('shop/email_confirmation.html.twig', [
                'commande' => $commande,
                'produit' => $produit
            ]);
            
            $email = (new Email())
                ->from('levelopcorporation@gmail.com')
                ->to($commande->getUtilisateur()->getEmail())
                ->subject('Confirmation de votre commande #' . $commande->getId())
                ->html($emailHtml);

            $this->mailer->send($email);
        }
    }
    #[Route('/generate-invoice/{id}', name: 'app_generate_invoice')]
    public function generateInvoicePdf(Commande $commande): Response
    {
        // Check if the user has access to this order
        if ($commande->getUtilisateur() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('You do not have access to this order');
        }
        
        // Get the stock associated with the product
        $stock = null;
        if ($commande->getProduit() && $commande->getProduit()->getStocks() && !$commande->getProduit()->getStocks()->isEmpty()) {
            $stock = $commande->getProduit()->getStocks()->first();
        }
        
        // Create HTML content for the invoice
        $html = $this->renderView('shop/invoice_pdf.html.twig', [
            'commande' => $commande,
            'stock' => $stock,
            'date' => new \DateTime(),
            'user' => $this->getUser(),
            'image_base_url' => $this->getParameter('image_base_url')
        ]);
        
        // Create a new Dompdf instance
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        
        // (Optional) Set paper size and orientation
        $dompdf->setPaper('A4', 'portrait');
        
        // Render the HTML as PDF
        $dompdf->render();
        
        // Output the generated PDF to browser
        $response = new Response($dompdf->output());
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="facture-' . $commande->getId() . '.pdf"');
        
        return $response;
    }
}