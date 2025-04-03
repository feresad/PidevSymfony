<?php

namespace App\Entity;
use App\Repository\ClientEvenementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClientEvenementRepository::class)]
#[ORM\Table(name: 'client_evenement')]
class ClientEvenement
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'clientEvenements')]
    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'id', nullable: false, columnDefinition: 'INT(11) NOT NULL', onDelete: 'CASCADE')]
    private ?Utilisateur $client = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Evenement::class, inversedBy: 'clientEvenements')]
    #[ORM\JoinColumn(name: 'evenement_id', referencedColumnName: 'id', nullable: false, columnDefinition: 'INT(11) NOT NULL', onDelete: 'CASCADE')]
    private ?Evenement $evenement = null;

    /**
     * @return Utilisateur|null
     */
    public function getClient(): ?Utilisateur
    {
        return $this->client;
    }

    /**
     * @param Utilisateur|null $client
     * @return $this
     */
    public function setClient(?Utilisateur $client): self
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @return Evenement|null
     */
    public function getEvenement(): ?Evenement
    {
        return $this->evenement;
    }

    /**
     * @param Evenement|null $evenement
     * @return $this
     */
    public function setEvenement(?Evenement $evenement): self
    {
        $this->evenement = $evenement;
        return $this;
    }
}