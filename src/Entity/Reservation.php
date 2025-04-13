<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ReservationRepository;

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

    #[ORM\Column(type: 'integer')]
    private int $clientId;

    public function getId(): ?int { return $this->id; }

    public function getDateReservation(): \DateTimeInterface { return $this->dateReservation; }
    public function setDateReservation(\DateTimeInterface $dateReservation): self { $this->dateReservation = $dateReservation; return $this; }

    public function getSession(): Session_game { return $this->session; }
    public function setSession(Session_game $session): self { $this->session = $session; return $this; }

    public function getClientId(): int { return $this->clientId; }
    public function setClientId(int $clientId): self { $this->clientId = $clientId; return $this; }
} 