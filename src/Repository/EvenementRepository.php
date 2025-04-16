<?php

namespace App\Repository;

use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    public function findBySearchAndSort(?string $search, string $sort, int $page = 1, int $limit = 9): array
{
    $queryBuilder = $this->createQueryBuilder('e')
        ->leftJoin('App\Entity\ClientEvenement', 'ce', 'WITH', 'ce.evenement = e.id')
        ->groupBy('e.id');

    if ($search) {
        $queryBuilder->andWhere('e.nomEvent LIKE :search OR e.lieuEvent LIKE :search')
                     ->setParameter('search', '%' . $search . '%');
    }

    // Gestion du tri
    switch ($sort) {
        case 'nom_asc':
            $queryBuilder->orderBy('e.nomEvent', 'ASC');
            break;
        case 'nom_desc':
            $queryBuilder->orderBy('e.nomEvent', 'DESC');
            break;
        case 'date_asc':
            $queryBuilder->orderBy('e.dateEvent', 'ASC');
            break;
        case 'date_desc':
            $queryBuilder->orderBy('e.dateEvent', 'DESC');
            break;
        case 'lieu_asc':
            $queryBuilder->orderBy('e.lieuEvent', 'ASC');
            break;
        case 'lieu_desc':
            $queryBuilder->orderBy('e.lieuEvent', 'DESC');
            break;
        case 'reservations_desc': // Tri par nombre de réservations
            $queryBuilder->addSelect('COUNT(ce.evenement) as HIDDEN reservation_count')
                         ->orderBy('reservation_count', 'DESC');
            break;
        default:
            $queryBuilder->orderBy('e.nomEvent', 'ASC');
    }

    // Pagination
    $queryBuilder->setFirstResult(($page - 1) * $limit)
                 ->setMaxResults($limit);

    return $queryBuilder->getQuery()->getResult();
}

    public function countBySearch(?string $search): int
    {
        $queryBuilder = $this->createQueryBuilder('e')
                             ->select('COUNT(e.id)');

        if ($search) {
            $queryBuilder->andWhere('e.nomEvent LIKE :search OR e.lieuEvent LIKE :search')
                         ->setParameter('search', '%' . $search . '%');
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function findByDateRange(\DateTimeInterface $start, \DateTimeInterface $end)
    {
        return $this->createQueryBuilder('e')
            ->where('e.dateEvent BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();
    }
    public function getReservationCountForEvent(int $eventId): int
{
    return (int) $this->createQueryBuilder('e')
        ->select('COUNT(ce.evenement)')
        ->leftJoin('App\Entity\ClientEvenement', 'ce', 'WITH', 'ce.evenement = e.id')
        ->where('e.id = :eventId')
        ->setParameter('eventId', $eventId)
        ->getQuery()
        ->getSingleScalarResult();
}
}