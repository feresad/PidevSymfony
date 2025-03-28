<?php

namespace App\Repository;

use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    public function findBySearchAndSort(?string $search, string $sort, int $page = 1, int $limit = 9): array
    {
        $queryBuilder = $this->createQueryBuilder('e');

        if ($search) {
            $queryBuilder->andWhere('e.nomEvent LIKE :search OR e.lieuEvent LIKE :search')
                         ->setParameter('search', '%' . $search . '%');
        }

        // Gestion du tri
        switch ($sort) {
            case 'nom_asc':
                $queryBuilder->orderBy('e.nomEvent', 'ASC');
                break;
            case 'nom_desc':
                $queryBuilder->orderBy('e.nomEvent', 'DESC');
                break;
            case 'date_asc':
                $queryBuilder->orderBy('e.dateEvent', 'ASC');
                break;
            case 'date_desc':
                $queryBuilder->orderBy('e.dateEvent', 'DESC');
                break;
            case 'lieu_asc':
                $queryBuilder->orderBy('e.lieuEvent', 'ASC');
                break;
            case 'lieu_desc':
                $queryBuilder->orderBy('e.lieuEvent', 'DESC');
                break;
            default:
                $queryBuilder->orderBy('e.nomEvent', 'ASC');
        }

        // Pagination
        $queryBuilder->setFirstResult(($page - 1) * $limit)
                     ->setMaxResults($limit);

        return $queryBuilder->getQuery()->getResult();
    }

    public function countBySearch(?string $search): int
    {
        $queryBuilder = $this->createQueryBuilder('e')
                             ->select('COUNT(e.id)');

        if ($search) {
            $queryBuilder->andWhere('e.nomEvent LIKE :search OR e.lieuEvent LIKE :search')
                         ->setParameter('search', '%' . $search . '%');
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }
}