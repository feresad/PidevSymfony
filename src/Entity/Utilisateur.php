<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Reports;

#[ORM\Entity]
class Utilisateur
{
    public function __construct()
    {
    }


    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 255)]
    private string $nom;

    #[ORM\Column(type: "string", length: 255)]
    private string $prenom;

    #[ORM\Column(type: "string", length: 255)]
    private string $email;

    #[ORM\Column(type: "string", length: 255)]
    private string $nickname;

    #[ORM\Column(type: "integer")]
    private int $numero;

    #[ORM\Column(type: "string", length: 255)]
    private string $mot_passe;

    #[ORM\Column(type: "string")]
    private string $role;

    #[ORM\Column(type: "string", length: 50)]
    private string $privilege;

    #[ORM\Column(type: "boolean")]
    private bool $ban;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $banTime;

    #[ORM\Column(type: "integer")]
    private int $countRep;

    #[ORM\Column(type: "string", length: 255)]
    private string $photo;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getNickname(): string
    {
        return $this->nickname;
    }

    public function setNickname(string $nickname): self
    {
        $this->nickname = $nickname;
        return $this;
    }

    public function getNumero(): int
    {
        return $this->numero;
    }

    public function setNumero(int $numero): self
    {
        $this->numero = $numero;
        return $this;
    }

    public function getMot_passe(): string
    {
        return $this->mot_passe;
    }

    public function setMot_passe(string $mot_passe): self
    {
        $this->mot_passe = $mot_passe;
        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getPrivilege(): string
    {
        return $this->privilege;
    }

    public function setPrivilege(string $privilege): self
    {
        $this->privilege = $privilege;
        return $this;
    }

    public function getBan(): bool
    {
        return $this->ban;
    }

    public function setBan(bool $ban): self
    {
        $this->ban = $ban;
        return $this;
    }

    public function getBanTime(): \DateTimeInterface
    {
        return $this->banTime;
    }

    public function setBanTime(\DateTimeInterface $banTime): self
    {
        $this->banTime = $banTime;
        return $this;
    }

    public function getCountRep(): int
    {
        return $this->countRep;
    }

    public function setCountRep(int $countRep): self
    {
        $this->countRep = $countRep;
        return $this;
    }

    public function getPhoto(): string
    {
        return $this->photo;
    }

    public function setPhoto(string $photo): self
    {
        $this->photo = $photo;
        return $this;
    }
}
