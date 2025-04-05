<?php

namespace App\Entity;

use App\Repository\CoachRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CoachRepository::class)]
class Coach extends Utilisateur
{
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $avalableSessionDates = null;

    #[ORM\Column(nullable: true)]
    private ?int $nombreSessionReseve = null;

    public function __construct()
    {
        $this->setRole("COACH");
    }

    public function getAvalableSessionDates(): ?\DateTimeInterface
    {
        return $this->avalableSessionDates;
    }

    public function setAvalableSessionDates(?\DateTimeInterface $avalableSessionDates): self
    {
        $this->avalableSessionDates = $avalableSessionDates;
        return $this;
    }

    public function getNombreSessionReseve(): ?int
    {
        return $this->nombreSessionReseve;
    }

    public function setNombreSessionReseve(?int $nombreSessionReseve): self
    {
        $this->nombreSessionReseve = $nombreSessionReseve;
        return $this;
    }
}