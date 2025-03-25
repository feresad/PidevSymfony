<?php

namespace App\Repository;

use App\Entity\Doctrine_migration_versions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class Doctrine_migration_versionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Doctrine_migration_versions::class);
    }

    // Add custom methods as needed
}