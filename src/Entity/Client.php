<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use App\Entity\Utilisateur;
use App\Entity\Client_evenement;

#[ORM\Entity]
class Client
{
    public function __construct()
    {
    }


    #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: "clients")]
    #[ORM\JoinColumn(name: 'id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Utilisateur $id;

    public function getId(): Utilisateur
    {
        return $this->id;
    }

    public function setId(Utilisateur $id): self
    {
        $this->id = $id;
        return $this;
    }
}
