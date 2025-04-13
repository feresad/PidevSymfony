<?php

namespace App\Repository;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    public function findCustomersByProductId(int $productId): array
    {
        return $this->createQueryBuilder('c')
            ->select('DISTINCT u.id, u.email, u.nom, u.prenom')
            ->join('c.utilisateur', 'u')
            ->join('c.produit', 'p')
            ->where('p.id = :productId')
            ->andWhere('c.status = :status')
            ->setParameter('productId', $productId)
            ->setParameter('status', 'terminé')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find users with commands and their accumulated prices
     */
    public function findUserCommandSummaries(?string $search = null, ?string $sort = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('u.id, u.nickname, u.email, u.nom, u.prenom, COUNT(c.id) as total_commands, SUM(s.prix_produit) as total_amount')
            ->join('c.utilisateur', 'u')
            ->join('c.produit', 'p')
            ->join('p.stocks', 's')
            ->groupBy('u.id')
            ->orderBy('u.nickname', 'ASC');

        // Add search filter if provided
        if ($search) {
            $qb->andWhere('u.nom LIKE :search OR u.prenom LIKE :search OR u.nickname LIKE :search OR u.email LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Add sorting if provided
        if ($sort) {
            switch ($sort) {
                case 'nickname_asc':
                    $qb->orderBy('u.nickname', 'ASC');
                    break;
                case 'nickname_desc':
                    $qb->orderBy('u.nickname', 'DESC');
                    break;
                case 'amount_asc':
                    $qb->orderBy('total_amount', 'ASC');
                    break;
                case 'amount_desc':
                    $qb->orderBy('total_amount', 'DESC');
                    break;
                case 'commands_asc':
                    $qb->orderBy('total_commands', 'ASC');
                    break;
                case 'commands_desc':
                    $qb->orderBy('total_commands', 'DESC');
                    break;
                default:
                    $qb->orderBy('u.nickname', 'ASC');
            }
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Count the total number of users with commands
     */
    public function countUsersWithCommands(?string $search = null): int
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(DISTINCT u.id)')
            ->join('c.utilisateur', 'u');

        // Add search filter if provided
        if ($search) {
            $qb->andWhere('u.nom LIKE :search OR u.prenom LIKE :search OR u.nickname LIKE :search OR u.email LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get the order count and total price for a specific user and product
     */
    public function getUserProductOrderSummary(int $utilisateurId, int $produitId): array
    {
        $result = $this->createQueryBuilder('c')
            ->select('COUNT(c.id) as order_count, SUM(s.prix_produit) as total_price')
            ->join('c.utilisateur', 'u')
            ->join('c.produit', 'p')
            ->join('p.stocks', 's')
            ->where('u.id = :utilisateurId')
            ->andWhere('p.id = :produitId')
            ->setParameter('utilisateurId', $utilisateurId)
            ->setParameter('produitId', $produitId)
            ->getQuery()
            ->getSingleResult();

        return [
            'order_count' => (int) $result['order_count'],
            'total_price' => $result['total_price'] ? (float) $result['total_price'] : 0.0,
        ];
    }

    /**
     * Get all users who ordered a specific product with their order summary
     */
    public function getUsersWhoOrderedProduct(int $produitId): array
    {
        return $this->createQueryBuilder('c')
            ->select('u.id, u.nickname, u.email, u.nom, u.prenom, COUNT(c.id) as order_count, SUM(s.prix_produit) as total_price, MAX(c.status) as last_status')
            ->join('c.utilisateur', 'u')
            ->join('c.produit', 'p')
            ->join('p.stocks', 's')
            ->where('p.id = :produitId')
            ->setParameter('produitId', $produitId)
            ->groupBy('u.id')
            ->orderBy('order_count', 'DESC')
            ->getQuery()
            ->getResult();
    }
}