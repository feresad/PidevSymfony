<?php

namespace App\Repository;

use App\Entity\Stock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stock::class);
    }

    /**
     * @return array Returns an array of products with their images and prices with pagination
     */
    public function findAllProductsWithDetailsPaginated(int $page = 1, int $limit = 4, string $search = '', string $sort = 'nom_asc'): array
    {
        $firstResult = ($page - 1) * $limit;

        $qb = $this->createQueryBuilder('s')
            ->select('s.id as stock_id, s.image, s.prix_produit, s.quantity, p.nom_produit, p.id as produit_id, p.description')
            ->join('s.produit', 'p')
            ->groupBy('p.id');
        
        if (!empty($search)) {
            $qb->andWhere('p.nom_produit LIKE :search OR p.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        switch ($sort) {
            case 'nom_desc':
                $qb->orderBy('p.nom_produit', 'DESC');
                break;
            case 'prix_asc':
                $qb->orderBy('s.prix_produit', 'ASC');
                break;
            case 'prix_desc':
                $qb->orderBy('s.prix_produit', 'DESC');
                break;
            case 'default':
                $qb->orderBy('p.score', 'DESC');
                break;
            case 'nom_asc':
                $qb->orderBy('p.nom_produit', 'ASC');
                break;
            default:
                $qb->orderBy('p.score', 'DESC');
                break;
        }
        
        return $qb->setFirstResult($firstResult)
                 ->setMaxResults($limit)
                 ->getQuery()
                 ->getResult();
    }

    /**
     * @return int Returns the total number of products matching search criteria
     */
    public function getTotalProducts(string $search = ''): int
    {
        $qb = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->join('s.produit', 'p');
        
        if (!empty($search)) {
            $qb->andWhere('p.nom_produit LIKE :search OR p.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        return $qb->getQuery()
                 ->getSingleScalarResult();
    }

    /**
     * @return array Returns an array of products with their images and prices
     */
    public function findAllProductsWithDetails(): array
    {
        return $this->createQueryBuilder('s')
            ->select('s.id as stock_id, s.image, s.prix_produit, s.quantity, p.nom_produit, p.id as produit_id, p.description')
            ->join('s.produit', 'p')
            ->orderBy('p.score', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array Returns an array of most popular products based on order count
     */
    public function findMostPopularProducts(int $limit = 6): array
    {
        return $this->createQueryBuilder('s')
            ->select('s.id as stock_id, s.image, s.prix_produit, s.quantity, p.nom_produit, p.id as produit_id, p.description')
            ->addSelect('COUNT(c.id) as orderCount')
            ->join('s.produit', 'p')
            ->leftJoin('p.commandes', 'c')
            ->groupBy('p.id')
            ->orderBy('orderCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Stock|null Returns product with its details
     */
    public function findOneByProduitId(int $id): ?Stock
    {
        return $this->createQueryBuilder('s')
            ->join('s.produit', 'p')
            ->where('p.id = :id')
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array Returns an array of featured products sorted by price
     */
    public function findFeaturedProductsByPrice(int $limit = 6): array
    {
        return $this->createQueryBuilder('s')
            ->select('s.id as stock_id, s.image, s.prix_produit, s.quantity, p.nom_produit, p.id as produit_id, p.description')
            ->join('s.produit', 'p')
            ->where('s.quantity > 0')
            ->groupBy('p.id')
            ->orderBy('s.prix_produit', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}