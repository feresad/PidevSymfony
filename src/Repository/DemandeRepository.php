<?php

namespace App\Repository;

use App\Entity\Demande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DemandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Demande::class);
    }

    public function searchByGameOrUser(?string $query)
    {
        $qb = $this->createQueryBuilder('d')
            ->join('d.userId', 'u');
        if ($query) {
            $qb->where('d.game LIKE :query')
               ->orWhere('u.nom LIKE :query')
               ->orWhere('u.prenom LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }
        return $qb->getQuery();
    }

    // Add custom methods as needed
}