<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Form\CommandeType;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/commande')]
class CommandeController extends AbstractController
{
    #[Route('/', name: 'app_commande_index', methods: ['GET'])]
    public function index(CommandeRepository $commandeRepository, Request $request): Response
    {
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', 'date_desc');
        $page = $request->query->getInt('page', 1);
        $limit = 10; // Number of items per page

        // Build sorting criteria
        $orderBy = [];
        switch ($sort) {
            case 'date_asc':
                $orderBy = ['c.createdAt' => 'ASC'];
                break;
            case 'date_desc':
                $orderBy = ['c.createdAt' => 'DESC'];
                break;
            case 'status_asc':
                $orderBy = ['c.status' => 'ASC'];
                break;
            default:
                $orderBy = ['c.createdAt' => 'DESC'];
        }

        // Fetch commandes, grouping by produit to avoid duplicates
        $queryBuilder = $commandeRepository->createQueryBuilder('c')
            ->select('c, p, s')
            ->leftJoin('c.produit', 'p')
            ->leftJoin('p.stocks', 's');

        if ($search) {
            $queryBuilder->andWhere('p.nom_produit LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $queryBuilder->orderBy(key($orderBy), current($orderBy));

        // Paginate the results
        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($queryBuilder);
        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);

        $totalItems = count($paginator);
        $maxPages = max(1, ceil($totalItems / $limit));

        return $this->render('commande/index.html.twig', [
            'commandes' => $paginator,
            'current_page' => $page,
            'image_base_url' => $this->getParameter('image_base_url'),
            'max_pages' => $maxPages,
            'search' => $search,
            'sort' => $sort,
        ]);
    }

    #[Route('/new', name: 'app_commande_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $commande = new Commande();
        $form = $this->createForm(CommandeType::class, $commande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($commande);
                $entityManager->flush();
                $this->addFlash('success', 'La commande a été créée avec succès.');
                return $this->redirectToRoute('app_commande_index');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la création de la commande: ' . $e->getMessage());
            }
        }

        return $this->render('commande/new.html.twig', [
            'commande' => $commande,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/stats', name: 'app_commande_stats', methods: ['GET'])]
    public function stats(Request $request, EntityManagerInterface $entityManager): Response
    {
        $year = $request->query->get('year', date('Y'));
        
        // Create date range for the selected year
        $startDate = new \DateTime($year . '-01-01 00:00:00');
        $endDate = new \DateTime($year . '-12-31 23:59:59');
        
        // Get all commandes for the selected year
        $commandesQuery = $entityManager->createQuery(
            'SELECT c, p, s
             FROM App\Entity\Commande c
             JOIN c.produit p
             JOIN p.stocks s
             WHERE c.createdAt >= :startDate AND c.createdAt <= :endDate
             ORDER BY c.createdAt ASC'
        )
        ->setParameter('startDate', $startDate)
        ->setParameter('endDate', $endDate);
        
        $commandes = $commandesQuery->getResult();
        
        // Process data manually in PHP
        $monthlyData = [];
        $productData = [];
        $totalRevenue = 0;
        $totalOrders = 0;
        
        $monthNames = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        
        // Initialize months
        for ($i = 1; $i <= 12; $i++) {
            $monthlyData[$i] = [
                'month_name' => $monthNames[$i],
                'order_count' => 0,
                'revenue' => 0,
            ];
        }
        
        // Process each commande
        foreach ($commandes as $commande) {
            $month = (int)$commande->getCreatedAt()->format('n');
            $price = 0;
            
            // Get price from stock
            if ($commande->getProduit() && $commande->getProduit()->getStocks() && !$commande->getProduit()->getStocks()->isEmpty()) {
                $stock = $commande->getProduit()->getStocks()->first();
                if ($stock) {
                    $price = $stock->getPrixProduit();
                }
            }
            
            // Update monthly data
            $monthlyData[$month]['order_count']++;
            $monthlyData[$month]['revenue'] += $price;
            
            // Update total counts
            $totalRevenue += $price;
            $totalOrders++;
            
            // Update product data
            $productName = $commande->getProduit() ? $commande->getProduit()->getNomProduit() : 'Inconnu';
            if (!isset($productData[$productName])) {
                $productData[$productName] = [
                    'name' => $productName,
                    'quantity' => 0,
                    'revenue' => 0,
                    'prices' => [], // To calculate average
                ];
            }
            
            $productData[$productName]['quantity']++;
            $productData[$productName]['revenue'] += $price;
            $productData[$productName]['prices'][] = $price;
        }
        
        // Clean up data for view
        $formattedMonthlyData = array_values($monthlyData);
        
        // Process product data
        $formattedProductData = [];
        foreach ($productData as $data) {
            $avgPrice = count($data['prices']) > 0 ? array_sum($data['prices']) / count($data['prices']) : 0;
            $formattedProductData[] = [
                'name' => $data['name'],
                'quantity' => $data['quantity'],
                'revenue' => $data['revenue'],
                'avg_price' => $avgPrice,
            ];
        }
        
        // Sort products by quantity sold (descending)
        usort($formattedProductData, function($a, $b) {
            return $b['quantity'] - $a['quantity'];
        });
        
        // Limit to top 10 products
        $topProducts = array_slice($formattedProductData, 0, 10);
        
        return $this->render('commande/stats.html.twig', [
            'monthly_data' => $formattedMonthlyData,
            'top_products' => $topProducts,
            'image_base_url' => $this->getParameter('image_base_url'),
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'year' => $year,
        ]);
    }

    #[Route('/api/stats', name: 'app_commande_api_stats', methods: ['GET'])]
    public function apiStats(Request $request, EntityManagerInterface $entityManager): Response
    {
        $year = $request->query->get('year', date('Y'));
        $timeRange = $request->query->getInt('timeRange', 30); // Default to 30 days
        
        // Create date range based on timeRange
        $endDate = new \DateTime();
        $startDate = clone $endDate;
        $startDate->modify("-{$timeRange} days");
        
        // Use year if timeRange is not specified
        if (!$request->query->has('timeRange')) {
            $startDate = new \DateTime($year . '-01-01 00:00:00');
            $endDate = new \DateTime($year . '-12-31 23:59:59');
        }
        
        // Get all commandes for the selected period
        $commandesQuery = $entityManager->createQuery(
            'SELECT c, p, s
             FROM App\Entity\Commande c
             JOIN c.produit p
             JOIN p.stocks s
             WHERE c.createdAt >= :startDate AND c.createdAt <= :endDate
             ORDER BY c.createdAt ASC'
        )
        ->setParameter('startDate', $startDate)
        ->setParameter('endDate', $endDate);
        
        $commandes = $commandesQuery->getResult();
        
        // Process data manually in PHP
        $productData = [];
        $totalRevenue = 0;
        $totalOrders = count($commandes);
        $previousPeriodRevenue = 0;
        
        // Process each commande
        foreach ($commandes as $commande) {
            $price = 0;
            
            // Get price from stock
            if ($commande->getProduit() && $commande->getProduit()->getStocks() && !$commande->getProduit()->getStocks()->isEmpty()) {
                $stock = $commande->getProduit()->getStocks()->first();
                if ($stock) {
                    $price = $stock->getPrixProduit();
                }
            }
            
            // Update total revenue
            $totalRevenue += $price;
            
            // Update product data
            $productName = $commande->getProduit() ? $commande->getProduit()->getNomProduit() : 'Inconnu';
            if (!isset($productData[$productName])) {
                $productData[$productName] = [
                    'name' => $productName,
                    'quantity' => 0,
                    'revenue' => 0,
                ];
            }
            
            $productData[$productName]['quantity']++;
            $productData[$productName]['revenue'] += $price;
        }
        
        // Sort products by quantity sold (descending)
        uasort($productData, function($a, $b) {
            return $b['quantity'] - $a['quantity'];
        });
        
        // Get top product name
        $topProduct = count($productData) > 0 ? array_keys($productData)[0] : '-';
        
        // Calculate average order value
        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
        
        // Get previous period data for growth calculation
        $previousEndDate = clone $startDate;
        $previousStartDate = clone $previousEndDate;
        $previousStartDate->modify("-{$timeRange} days");
        
        $previousCommandesQuery = $entityManager->createQuery(
            'SELECT c, p, s
             FROM App\Entity\Commande c
             JOIN c.produit p
             JOIN p.stocks s
             WHERE c.createdAt >= :startDate AND c.createdAt <= :endDate
             ORDER BY c.createdAt ASC'
        )
        ->setParameter('startDate', $previousStartDate)
        ->setParameter('endDate', $previousEndDate);
        
        $previousCommandes = $previousCommandesQuery->getResult();
        
        // Calculate previous period revenue
        foreach ($previousCommandes as $commande) {
            $price = 0;
            if ($commande->getProduit() && $commande->getProduit()->getStocks() && !$commande->getProduit()->getStocks()->isEmpty()) {
                $stock = $commande->getProduit()->getStocks()->first();
                if ($stock) {
                    $price = $stock->getPrixProduit();
                }
            }
            $previousPeriodRevenue += $price;
        }
        
        // Calculate growth percentage
        $growthPercentage = 0;
        if ($previousPeriodRevenue > 0) {
            $growthPercentage = (($totalRevenue - $previousPeriodRevenue) / $previousPeriodRevenue) * 100;
        }
        
        // Return JSON response
        return $this->json([
            'success' => true,
            'stats' => [
                'totalRevenue' => $totalRevenue,
                'totalOrders' => $totalOrders,
                'topProduct' => $topProduct,
                'avgOrderValue' => $avgOrderValue,
                'monthlyGrowth' => round($growthPercentage, 1)
            ],
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'timeRange' => $timeRange
            ]
        ]);
    }

    #[Route('/stats/pdf', name: 'app_commande_stats_pdf', methods: ['GET'])]
    public function statsPdf(Request $request, EntityManagerInterface $entityManager): Response
    {
        $year = $request->query->get('year', date('Y'));
        
        // Create date range for the selected year
        $startDate = new \DateTime($year . '-01-01 00:00:00');
        $endDate = new \DateTime($year . '-12-31 23:59:59');
        
        // Get all commandes for the selected year
        $commandesQuery = $entityManager->createQuery(
            'SELECT c, p, s
             FROM App\Entity\Commande c
             JOIN c.produit p
             JOIN p.stocks s
             WHERE c.createdAt >= :startDate AND c.createdAt <= :endDate
             ORDER BY c.createdAt ASC'
        )
        ->setParameter('startDate', $startDate)
        ->setParameter('endDate', $endDate);
        
        $commandes = $commandesQuery->getResult();
        
        // Process data manually in PHP
        $monthlyData = [];
        $productData = [];
        $totalRevenue = 0;
        $totalOrders = 0;
        
        $monthNames = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        
        // Initialize months
        for ($i = 1; $i <= 12; $i++) {
            $monthlyData[$i] = [
                'month_name' => $monthNames[$i],
                'order_count' => 0,
                'revenue' => 0,
            ];
        }
        
        // Process each commande
        foreach ($commandes as $commande) {
            $month = (int)$commande->getCreatedAt()->format('n');
            $price = 0;
            
            // Get price from stock
            if ($commande->getProduit() && $commande->getProduit()->getStocks() && !$commande->getProduit()->getStocks()->isEmpty()) {
                $stock = $commande->getProduit()->getStocks()->first();
                if ($stock) {
                    $price = $stock->getPrixProduit();
                }
            }
            
            // Update monthly data
            $monthlyData[$month]['order_count']++;
            $monthlyData[$month]['revenue'] += $price;
            
            // Update total counts
            $totalRevenue += $price;
            $totalOrders++;
            
            // Update product data
            $productName = $commande->getProduit() ? $commande->getProduit()->getNomProduit() : 'Inconnu';
            if (!isset($productData[$productName])) {
                $productData[$productName] = [
                    'name' => $productName,
                    'quantity' => 0,
                    'revenue' => 0,
                    'prices' => [], // To calculate average
                ];
            }
            
            $productData[$productName]['quantity']++;
            $productData[$productName]['revenue'] += $price;
            $productData[$productName]['prices'][] = $price;
        }
        
        // Clean up data for view
        $formattedMonthlyData = array_values($monthlyData);
        
        // Process product data
        $formattedProductData = [];
        foreach ($productData as $data) {
            $avgPrice = count($data['prices']) > 0 ? array_sum($data['prices']) / count($data['prices']) : 0;
            $formattedProductData[] = [
                'name' => $data['name'],
                'quantity' => $data['quantity'],
                'revenue' => $data['revenue'],
                'avg_price' => $avgPrice,
            ];
        }
        
        // Sort products by quantity sold (descending)
        usort($formattedProductData, function($a, $b) {
            return $b['quantity'] - $a['quantity'];
        });
        
        // Limit to top 10 products
        $topProducts = array_slice($formattedProductData, 0, 10);
        
        // Generate PDF
        $html = $this->renderView('commande/stats_pdf.html.twig', [
            'monthly_data' => $formattedMonthlyData,
            'top_products' => $topProducts,
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'year' => $year,
        ]);
        
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $response = new Response($dompdf->output());
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment;filename=statistiques_commandes_' . $year . '.pdf');
        
        return $response;
    }

    #[Route('/{id}', name: 'app_commande_show', methods: ['GET'])]
    public function show(Commande $commande, CommandeRepository $commandeRepository): Response
    {
        // Get the order count and total price for this user and product
        $summary = $commandeRepository->getUserProductOrderSummary(
            $commande->getUtilisateur()->getId(),
            $commande->getProduit()->getId()
        );

        // Get stock information
        $stock = null;
        $produit = $commande->getProduit();
        
        if ($produit && $produit->getStocks() && !$produit->getStocks()->isEmpty()) {
            $stock = $produit->getStocks()->first();
        }

        // Get all users who ordered this product
        $rawUsers = $commandeRepository->getUsersWhoOrderedProduct($produit->getId());
        
        // Transform the user data to ensure compatibility with template
        $users = [];
        foreach ($rawUsers as $user) {
            $users[] = [
                'id' => $user['id'],
                'nickname' => $user['nickname'],
                'email' => $user['email'],
                'nom' => $user['nom'],
                'prenom' => $user['prenom'],
                'orderCount' => $user['order_count'],
                'totalPrice' => $user['total_price'],
                'lastStatus' => $user['last_status']
            ];
        }

        return $this->render('commande/show.html.twig', [
            'commande' => $commande,
            'order_count' => $summary['order_count'],
            'total_price' => $summary['total_price'],
            'stock' => $stock,
            'produit' => $produit,
            'image_base_url' => $this->getParameter('image_base_url'),
            'users' => $users
        ]);
    }

    #[Route('/{id}', name: 'app_commande_delete', methods: ['POST'])]
    public function delete(Request $request, Commande $commande, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$commande->getId(), $request->request->get('_token'))) {
            $entityManager->remove($commande);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_commande_index');
    }
}