<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Commande;

#[ORM\Entity]
class Produit
{
    public function __construct()
    {
    }


    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 255)]
    private string $nom_produit;

    #[ORM\Column(type: "string", length: 255)]
    private string $description;

    #[ORM\Column(type: "integer")]
    private int $score;

    #[ORM\Column(type: "string", length: 50)]
    private string $platform;

    #[ORM\Column(type: "string", length: 50)]
    private string $type;

    #[ORM\Column(type: "string", length: 50)]
    private string $region;

    #[ORM\Column(type: "string", length: 50)]
    private string $activation_region;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getNom_produit(): string
    {
        return $this->nom_produit;
    }

    public function setNom_produit(string $nom_produit): self
    {
        $this->nom_produit = $nom_produit;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): self
    {
        $this->score = $score;
        return $this;
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function setPlatform(string $platform): self
    {
        $this->platform = $platform;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getRegion(): string
    {
        return $this->region;
    }

    public function setRegion(string $region): self
    {
        $this->region = $region;
        return $this;
    }

    public function getActivation_region(): string
    {
        return $this->activation_region;
    }

    public function setActivation_region(string $activation_region): self
    {
        $this->activation_region = $activation_region;
        return $this;
    }
}
