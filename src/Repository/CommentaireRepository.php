<?php

namespace App\Repository;

use App\Entity\Commentaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommentaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commentaire::class);
    }

    public function findAllWithPaginationAndFilters(int $page, int $itemsPerPage, string $searchTerm, string $sortBy, string $sortOrder): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.utilisateur_id', 'u') // Fixed: Use property name utilisateur_id
            ->leftJoin('c.question_id', 'q')    // Fixed: Use property name question_id
            ->addOrderBy("c.$sortBy", $sortOrder);

        if ($searchTerm) {
            $qb->andWhere('c.contenu LIKE :search OR u.nickname LIKE :search OR q.title LIKE :search')
               ->setParameter('search', '%' . $searchTerm . '%');
        }

        return $qb->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage)
            ->getQuery()
            ->getResult();
    }

    public function countWithFilters(string $searchTerm): int
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.commentaire_id)')
            ->leftJoin('c.utilisateur_id', 'u') // Fixed: Use property name utilisateur_id
            ->leftJoin('c.question_id', 'q');   // Fixed: Use property name question_id

        if ($searchTerm) {
            $qb->andWhere('c.contenu LIKE :search OR u.nickname LIKE :search OR q.title LIKE :search')
               ->setParameter('search', '%' . $searchTerm . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}