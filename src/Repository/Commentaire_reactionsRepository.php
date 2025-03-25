<?php

namespace App\Repository;

use App\Entity\Commentaire_reactions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class Commentaire_reactionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commentaire_reactions::class);
    }

    // Add custom methods as needed
}