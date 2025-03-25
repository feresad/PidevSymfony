<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;


#[ORM\Entity]
class Stock
{
    public function __construct()
    {
    }


    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "integer")]
    private int $produit_id;

    #[ORM\Column(type: "integer")]
    private int $games_id;

    #[ORM\Column(type: "integer")]
    private int $quantity;

    #[ORM\Column(type: "integer")]
    private int $prix_produit;

    #[ORM\Column(type: "string", length: 255)]
    private string $image;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getProduit_id(): int
    {
        return $this->produit_id;
    }

    public function setProduit_id(int $produit_id): self
    {
        $this->produit_id = $produit_id;
        return $this;
    }

    public function getGames_id(): int
    {
        return $this->games_id;
    }

    public function setGames_id(int $games_id): self
    {
        $this->games_id = $games_id;
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

    public function getPrix_produit(): int
    {
        return $this->prix_produit;
    }

    public function setPrix_produit(int $prix_produit): self
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
}
