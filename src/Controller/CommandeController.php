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
            ->leftJoin('p.stocks', 's')
            ->groupBy('p.id'); // Group by product to avoid duplicates

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