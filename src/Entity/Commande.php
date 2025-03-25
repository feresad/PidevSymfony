<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use App\Entity\Produit;

#[ORM\Entity]
class Commande
{
    public function __construct()
    {
    }


    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

        #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: "commandes")]
    #[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Utilisateur $utilisateur_id;

        #[ORM\ManyToOne(targetEntity: Produit::class, inversedBy: "commandes")]
    #[ORM\JoinColumn(name: 'produit_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Produit $produit_id;

    #[ORM\Column(type: "string", length: 255)]
    private string $status;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getUtilisateur_id(): Utilisateur
    {
        return $this->utilisateur_id;
    }

    public function setUtilisateur_id(Utilisateur $utilisateur_id): self
    {
        $this->utilisateur_id = $utilisateur_id;
        return $this;
    }

    public function getProduit_id(): Produit
    {
        return $this->produit_id;
    }

    public function setProduit_id(Produit $produit_id): self
    {
        $this->produit_id = $produit_id;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }
}
