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

    public function getSessionsInPromoHome(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.prix < :prix')
            ->setParameter('prix', 60)
            ->getQuery()
            ->setMaxResults(6)
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

    /**
     * Returns all sessions with their reservation count
     * @return array [ ['session' => Session_game, 'reservationCount' => int], ... ]
     */
    public function findAllWithReservationCount(): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s, COUNT(r.id) AS reservationCount')
            ->leftJoin('App\\Entity\\Reservation', 'r', 'WITH', 'r.session = s')
            ->groupBy('s.id');
        $results = $qb->getQuery()->getResult();

        // Doctrine returns a mixed array, so we need to format it
        $sessions = [];
        foreach ($results as $result) {
            if (is_array($result)) {
                $sessions[] = [
                    'session' => $result[0],
                    'reservationCount' => (int)$result['reservationCount']
                ];
            } else {
                // In case Doctrine returns only the entity
                $sessions[] = [
                    'session' => $result,
                    'reservationCount' => 0
                ];
            }
        }
        return $sessions;
    }
}