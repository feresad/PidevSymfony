<?php

namespace App\Repository;

use App\Entity\Coach;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CoachRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Coach::class);
    }

    public function searchByGameOrUser(?string $query)
    {
        $qb = $this->createQueryBuilder('c')
            ->join('c.userId', 'u');
        if ($query) {
            $qb->where('c.game LIKE :query')
               ->orWhere('u.nom LIKE :query')
               ->orWhere('u.prenom LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }
        return $qb->getQuery();
    }

    // Add custom methods as needed
}