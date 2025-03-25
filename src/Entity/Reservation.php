<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;


#[ORM\Entity]
class Reservation
{
    public function __construct()
    {
    }


    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "integer")]
    private int $session_id_id;

    #[ORM\Column(type: "integer")]
    private int $client_id;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $date_reservation;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getSession_id_id(): int
    {
        return $this->session_id_id;
    }

    public function setSession_id_id(int $session_id_id): self
    {
        $this->session_id_id = $session_id_id;
        return $this;
    }

    public function getClient_id(): int
    {
        return $this->client_id;
    }

    public function setClient_id(int $client_id): self
    {
        $this->client_id = $client_id;
        return $this;
    }

    public function getDate_reservation(): \DateTimeInterface
    {
        return $this->date_reservation;
    }

    public function setDate_reservation(\DateTimeInterface $date_reservation): self
    {
        $this->date_reservation = $date_reservation;
        return $this;
    }
}
