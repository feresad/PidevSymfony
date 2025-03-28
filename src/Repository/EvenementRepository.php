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
public function findBySearchAndSort(?string $search, string $sort): array
{
    $qb = $this->createQueryBuilder('e');

    if ($search) {
        $qb->where('e.nomEvent LIKE :search')
           ->orWhere('e.lieuEvent LIKE :search')
           ->setParameter('search', '%' . $search . '%');
    }

    switch ($sort) {
        case 'nom_asc':
            $qb->orderBy('e.nomEvent', 'ASC');
            break;
        case 'nom_desc':
            $qb->orderBy('e.nomEvent', 'DESC');
            break;
        case 'date_asc':
            $qb->orderBy('e.dateEvent', 'ASC');
            break;
        case 'date_desc':
            $qb->orderBy('e.dateEvent', 'DESC');
            break;
        case 'lieu_asc':
            $qb->orderBy('e.lieuEvent', 'ASC');
            break;
        case 'lieu_desc':
            $qb->orderBy('e.lieuEvent', 'DESC');
            break;
        default:
            $qb->orderBy('e.nomEvent', 'ASC');
    }

    return $qb->getQuery()->getResult();
}
}