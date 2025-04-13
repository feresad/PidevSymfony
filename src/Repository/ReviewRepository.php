<?php

namespace App\Repository;

use App\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /**
     * Find all reviews for a specific product, ordered by creation date (newest first).
     *
     * @param int $produitId The ID of the product
     * @return Review[] Returns an array of Review objects
     */
    public function findByProduitId(int $produitId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.produit = :produitId')
            ->setParameter('produitId', $produitId)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count the number of reviews for a specific product.
     *
     * @param int $produitId The ID of the product
     * @return int The number of reviews
     */
    public function countByProduitId(int $produitId): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.produit = :produitId')
            ->setParameter('produitId', $produitId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}