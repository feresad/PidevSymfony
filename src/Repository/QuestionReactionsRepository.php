<?php

namespace App\Repository;

use App\Entity\QuestionReactions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class QuestionReactionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuestionReactions::class);
    }

    // Add custom methods as needed
}