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

    // Add custom methods as needed

    /**
     * @return array Returns an array of products with their images and prices
     */
    public function findAllProductsWithDetails(): array
    {
        return $this->createQueryBuilder('s')
            ->select('s.image', 's.prix_produit', 'p.nom_produit', 'p.id', 'p.description')
            ->join('s.produit', 'p')
            ->where('s.quantity > 0')
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
            ->select('s.image', 's.prix_produit', 'p.nom_produit', 'p.id', 'p.description')
            ->addSelect('COUNT(c.id) as orderCount')
            ->join('s.produit', 'p')
            ->leftJoin('p.commandes', 'c')
            ->where('s.quantity > 0')
            ->groupBy('s.id')
            ->orderBy('orderCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Stock|null Returns product with its details
     */
    public function findOneProductWithDetails(int $id): ?Stock
    {
        return $this->createQueryBuilder('s')
            ->join('s.produit', 'p')
            ->where('p.id = :id')
            ->andWhere('s.quantity > 0')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}