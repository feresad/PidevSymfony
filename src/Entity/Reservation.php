<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Session_game;
use App\Entity\User; // ← très important d'importer User aussi !

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $dateReservation;

    #[ORM\ManyToOne(targetEntity: Session_game::class)]
    #[ORM\JoinColumn(name: 'session_id_id', referencedColumnName: 'id', nullable: false)]
    private Session_game $session;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Utilisateur $client; // ← ici on remplace int par User

    // --- GETTERS & SETTERS ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateReservation(): \DateTimeInterface
    {
        return $this->dateReservation;
    }

    public function setDateReservation(\DateTimeInterface $dateReservation): self
    {
        $this->dateReservation = $dateReservation;
        return $this;
    }

    public function getSession(): Session_game
    {
        return $this->session;
    }

    public function setSession(Session_game $session): self
    {
        $this->session = $session;
        return $this;
    }

    public function getClient(): Utilisateur
    {
        return $this->client;
    }

    public function setClient(Utilisateur $client): self
    {
        $this->client = $client;
        return $this;
    }
    
}
