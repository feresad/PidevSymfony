<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\Session_gameRepository;
use App\Entity\Utilisateur;

#[ORM\Entity(repositoryClass: Session_gameRepository::class)]
class Session_game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'float')]
    private float $prix;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $dateCreation;

    #[ORM\Column(type: 'string', length: 50)]
    private string $dureeSession;

    #[ORM\Column(type: 'string', length: 255)]
    private string $game;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $coach = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $imageName = null;

    #[ORM\OneToMany(mappedBy: 'session', targetEntity: Reservation::class, cascade: ['remove'])]
    private $reservations;

    // --- Getters & Setters ---

    public function getId(): ?int { return $this->id; }

    public function getPrix(): float { return $this->prix; }
    public function setPrix(float $prix): self { $this->prix = $prix; return $this; }

    public function getDateCreation(): \DateTimeInterface { return $this->dateCreation; }
    public function setDateCreation(\DateTimeInterface $dateCreation): self { $this->dateCreation = $dateCreation; return $this; }

    public function getDureeSession(): string { return $this->dureeSession; }
    public function setDureeSession(string $dureeSession): self { $this->dureeSession = $dureeSession; return $this; }

    public function getGame(): string { return $this->game; }
    public function setGame(string $game): self { $this->game = $game; return $this; }

    public function getCoach(): ?Utilisateur { return $this->coach; }
    public function setCoach(?Utilisateur $coach): self { $this->coach = $coach; return $this; }

    public function getImageName(): ?string { return $this->imageName; }
    public function setImageName(?string $imageName): self { $this->imageName = $imageName; return $this; }

    public function isExpired(): bool
    {
        $now = new \DateTime();
        $diff = $now->diff($this->dateCreation);
        return $diff->days > 30;
    }
}
