<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Stock
{
    public function __construct()
    {
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Produit::class, inversedBy: "stocks")]
    #[ORM\JoinColumn(name: "produit_id", referencedColumnName: "id", nullable: true)]
    private ?Produit $produit = null;

    #[ORM\ManyToOne(targetEntity: Games::class)]
    #[ORM\JoinColumn(name: "games_id", referencedColumnName: "game_id", nullable: true)]
    private ?Games $games = null;

    #[ORM\Column(type: "integer")]
    private int $quantity;

    #[ORM\Column(type: "integer")]
    private int $prix_produit;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\Image(
        minWidth: 205,
        maxWidth: 205,
        minHeight: 250,
        maxHeight: 250,
        allowLandscape: false,
        allowPortrait: true,
        mimeTypes: ["image/jpeg", "image/png", "image/gif"],
        mimeTypesMessage: "Please upload a valid image (JPEG, PNG, GIF)"
    )]
    private string $image;

    // Getters and Setters

    public function getId(): int
    {
        return $this->id;
    }

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(?Produit $produit): self
    {
        $this->produit = $produit;
        return $this;
    }

    public function getGames(): ?Games
    {
        return $this->games;
    }

    public function setGames(?Games $games): self
    {
        $this->games = $games;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getPrixProduit(): int
    {
        return $this->prix_produit;
    }

    public function setPrixProduit(int $prix_produit): self
    {
        $this->prix_produit = $prix_produit;
        return $this;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function setImage(string $image): self
    {
        $this->image = $image;
        return $this;
    }
    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageFile(?File $imageFile): self
    {
        $this->imageFile = $imageFile;
        return $this;
    }
}