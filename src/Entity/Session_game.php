<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\Session_gameRepository;

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

    #[ORM\Column(type: 'integer', nullable: false)] // Non nullable
    private int $coachId = 1; // Valeur statique par défaut

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $imageName = null;

    public function getId(): ?int { return $this->id; }

    public function getPrix(): float { return $this->prix; }
    public function setPrix(float $prix): self { $this->prix = $prix; return $this; }

    public function getDateCreation(): \DateTimeInterface { return $this->dateCreation; }
    public function setDateCreation(\DateTimeInterface $dateCreation): self { $this->dateCreation = $dateCreation; return $this; }

    public function getDureeSession(): string { return $this->dureeSession; }
    public function setDureeSession(string $dureeSession): self { $this->dureeSession = $dureeSession; return $this; }

    public function getGame(): string { return $this->game; }
    public function setGame(string $game): self { $this->game = $game; return $this; }

    public function getCoachId(): int { return $this->coachId; }
    public function setCoachId(int $coachId): self { $this->coachId = $coachId; return $this; }

    public function getImageName(): ?string { return $this->imageName; }
    public function setImageName(?string $imageName): self { $this->imageName = $imageName; return $this; }

    
}