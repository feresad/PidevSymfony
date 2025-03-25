<?php

namespace App\Repository;

use App\Entity\Categorie_event;
use App\Entity\CategorieEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class Categorie_eventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategorieEvent::class);
    }

    // Add custom methods as needed
}