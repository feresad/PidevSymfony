<?php

namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;

class UtilisateurRepository extends ServiceEntityRepository
{
    private $security;

    public function __construct(ManagerRegistry $registry, Security $security)
    {
        parent::__construct($registry, Utilisateur::class);
        $this->security = $security;
    }

    public function findByEmail(string $email): ?Utilisateur
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * Get the ID of the currently logged-in user
     * @return int|null The user ID or null if no user is logged in
     */
    public function getLoggedInUserId(): ?int
    {
        $user = $this->security->getUser();
        return $user instanceof Utilisateur ? $user->getId() : null;
    }

    /**
     * Get the email of the currently logged-in user
     * @return string|null The user email or null if no user is logged in
     */
    public function getLoggedInUserEmail(): ?string
    {
        $user = $this->security->getUser();
        return $user instanceof Utilisateur ? $user->getEmail() : null;
    }

    /**
     * Get the currently logged-in user
     * @return Utilisateur|null The user entity or null if no user is logged in
     */
    public function getLoggedInUser(): ?Utilisateur
    {
        $user = $this->security->getUser();
        return $user instanceof Utilisateur ? $user : null;
    }
}