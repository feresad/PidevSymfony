<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use App\Entity\Utilisateur;

#[ORM\Entity]
class Coach
{
    public function __construct()
    {
    }


    #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: "coachs")]
    #[ORM\JoinColumn(name: 'id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Utilisateur $id;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $avalable_session_dates;

    #[ORM\Column(type: "integer")]
    private int $nombre_session_reseve;

    public function getId(): Utilisateur
    {
        return $this->id;
    }

    public function setId(Utilisateur $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getAvalable_session_dates(): \DateTimeInterface
    {
        return $this->avalable_session_dates;
    }

    public function setAvalable_session_dates(\DateTimeInterface $avalable_session_dates): self
    {
        $this->avalable_session_dates = $avalable_session_dates;
        return $this;
    }

    public function getNombre_session_reseve(): int
    {
        return $this->nombre_session_reseve;
    }

    public function setNombre_session_reseve(int $nombre_session_reseve): self
    {
        $this->nombre_session_reseve = $nombre_session_reseve;
        return $this;
    }
}
