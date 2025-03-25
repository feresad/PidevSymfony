<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Evenement;
use App\Repository\Client_evenementRepository;

#[ORM\Entity(repositoryClass: Client_evenementRepository::class)]
class ClientEvenement
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: "client_evenements")]
    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Client $client_id = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Evenement::class, inversedBy: "client_evenements")]
    #[ORM\JoinColumn(name: 'evenement_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Evenement $evenement_id = null;

    public function __construct()
    {
        // Initialization if needed
    }

    public function getClient_id(): ?Client
    {
        return $this->client_id;
    }

    public function setClient_id(?Client $client_id): self
    {
        $this->client_id = $client_id;
        return $this;
    }

    public function getEvenement_id(): ?Evenement
    {
        return $this->evenement_id;
    }

    public function setEvenement_id(?Evenement $evenement_id): self
    {
        $this->evenement_id = $evenement_id;
        return $this;
    }
}
