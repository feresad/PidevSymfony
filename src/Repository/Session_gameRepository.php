<?php

namespace App\Repository;

use App\Entity\Session_game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Session_game>
 */
class Session_gameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Session_game::class);
    }

    public function add(Session_game $session_game, bool $flush = true): void
    {
        $this->getEntityManager()->persist($session_game);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Session_game $session_game, bool $flush = true): void
    {
        $this->getEntityManager()->remove($session_game);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getSessionById(int $id): ?Session_game
    {
        return $this->find($id);
    }

    public function getSessionsByCoachId(int $coachId): array
    {
        return $this->findBy(['coachId' => $coachId]);
    }

    public function getSessionsInPromo(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.prix < :prix')
            ->setParameter('prix', 60)
            ->getQuery()
            ->getResult();
    }

    public function findByGameName(string $gameName): array
    {
        return $this->createQueryBuilder('s')
            ->where('LOWER(s.game) LIKE LOWER(:game)')
            ->setParameter('game', '%' . $gameName . '%')
            ->getQuery()
            ->getResult();
    }
}