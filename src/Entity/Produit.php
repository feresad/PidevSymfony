<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity]
class Produit
{
    public function __construct()
    {
        $this->commandes = new ArrayCollection();
        $this->stocks = new ArrayCollection();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 255)]
    private string $nom_produit;

    #[ORM\Column(type: "string", length: 255)]
    private string $description;

    #[ORM\Column(type: "integer")]
    private int $score;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $platform = null;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $region = null;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
    private ?string $activation_region = null;

    #[ORM\OneToMany(targetEntity: Commande::class, mappedBy: "produit")]
    private Collection $commandes;

    #[ORM\OneToMany(targetEntity: Stock::class, mappedBy: "produit")]
    private Collection $stocks;

    // Getters and Setters

    public function getId(): int
    {
        return $this->id;
    }

    public function getNomProduit(): string
    {
        return $this->nom_produit;
    }

    public function setNomProduit(string $nom_produit): self
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

    public function getPlatform(): ?string
    {
        return $this->platform;
    }

    public function setPlatform(?string $platform): self
    {
        $this->platform = $platform;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): self
    {
        $this->region = $region;
        return $this;
    }

    public function getActivationRegion(): ?string
    {
        return $this->activation_region;
    }

    public function setActivationRegion(?string $activation_region): self
    {
        $this->activation_region = $activation_region;
        return $this;
    }

    /**
     * @return Collection<int, Commande>
     */
    public function getCommandes(): Collection
    {
        return $this->commandes;
    }

    /**
     * @return Collection<int, Stock>
     */
    public function getStocks(): Collection
    {
        return $this->stocks;
    }
}