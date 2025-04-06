<?php

namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function add(Reservation $reservation, bool $flush = true): void
    {
        $this->getEntityManager()->persist($reservation);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Reservation $reservation, bool $flush = true): void
    {
        $this->getEntityManager()->remove($reservation);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getReservationByClientAndSession(int $clientId, int $sessionId): ?Reservation
    {
        return $this->findOneBy(['clientId' => $clientId, 'session' => $sessionId]);
    }

    public function isSessionReserved(int $sessionId): bool
    {
        return $this->findOneBy(['session' => $sessionId]) !== null;
    }

    public function findBySessionAndDate(int $sessionId, \DateTime $date): ?Reservation
    {
        return $this->createQueryBuilder('r')
            ->where('r.session = :sessionId')
            ->andWhere('r.dateReservation = :date')
            ->setParameter('sessionId', $sessionId)
            ->setParameter('date', $date)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getReservationsByCoachId(int $coachId): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.session', 's')
            ->where('s.coachId = :coachId')
            ->setParameter('coachId', $coachId)
            ->getQuery()
            ->getResult();
    }

    public function getReservationsByClientId(int $clientId): array
    {
        return $this->findBy(['clientId' => $clientId]);
    }
}