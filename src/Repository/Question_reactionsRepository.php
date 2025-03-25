<?php

namespace App\Repository;

use App\Entity\Question_reactions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class Question_reactionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Question_reactions::class);
    }

    // Add custom methods as needed
}