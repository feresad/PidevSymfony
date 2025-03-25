<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;


#[ORM\Entity]
class Session_game
{
    public function __construct()
    {
    }


    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "integer")]
    private int $coach_id;

    #[ORM\Column(type: "string", length: 255)]
    private string $game;

    #[ORM\Column(type: "string", length: 50)]
    private string $duree_session;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $date_creation;

    #[ORM\Column(type: "float")]
    private float $prix;

    #[ORM\Column(type: "string", length: 255)]
    private string $image_name;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getCoach_id(): int
    {
        return $this->coach_id;
    }

    public function setCoach_id(int $coach_id): self
    {
        $this->coach_id = $coach_id;
        return $this;
    }

    public function getGame(): string
    {
        return $this->game;
    }

    public function setGame(string $game): self
    {
        $this->game = $game;
        return $this;
    }

    public function getDuree_session(): string
    {
        return $this->duree_session;
    }

    public function setDuree_session(string $duree_session): self
    {
        $this->duree_session = $duree_session;
        return $this;
    }

    public function getDate_creation(): \DateTimeInterface
    {
        return $this->date_creation;
    }

    public function setDate_creation(\DateTimeInterface $date_creation): self
    {
        $this->date_creation = $date_creation;
        return $this;
    }

    public function getPrix(): string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): self
    {
        $this->prix = $prix;
        return $this;
    }

    public function getImage_name(): string
    {
        return $this->image_name;
    }

    public function setImage_name(string $image_name): self
    {
        $this->image_name = $image_name;
        return $this;
    }
}
