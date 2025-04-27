<?php

namespace App\Repository;

use App\Entity\CategorieEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class Categorie_eventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategorieEvent::class);
    }

    public function findBySearch(?string $search, int $page = 1, int $limit = 9): array
    {
        $queryBuilder = $this->createQueryBuilder('c');

        if ($search) {
            $queryBuilder->andWhere('c.nom LIKE :search')
                         ->setParameter('search', '%' . $search . '%');
        }

        // Pagination
        $queryBuilder->setFirstResult(($page - 1) * $limit)
                     ->setMaxResults($limit);

        return $queryBuilder->getQuery()->getResult();
    }

    public function countBySearch(?string $search): int
    {
        $queryBuilder = $this->createQueryBuilder('c')
                             ->select('COUNT(c.id)');

        if ($search) {
            $queryBuilder->andWhere('c.nom LIKE :search')
                         ->setParameter('search', '%' . $search . '%');
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }
}