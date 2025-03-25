<?php

namespace App\Repository;

use App\Entity\Commentaire_votes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class Commentaire_votesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commentaire_votes::class);
    }

    // Add custom methods as needed
}