<?php

namespace App\Repository;

use App\Entity\Messenger_messages;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class Messenger_messagesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Messenger_messages::class);
    }

    // Add custom methods as needed
}