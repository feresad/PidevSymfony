<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;


#[ORM\Entity]
class Games
{
    public function __construct()
    {
    }


    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $game_id;

    #[ORM\Column(type: "string", length: 255)]
    private string $game_name;

    #[ORM\Column(type: "string", length: 255)]
    private string $image_path;

    #[ORM\Column(type: "string")]
    private string $game_type;

    public function getGame_id(): int
    {
        return $this->game_id;
    }

    public function setGame_id(int $game_id): self
    {
        $this->game_id = $game_id;
        return $this;
    }

    public function getGame_name(): string
    {
        return $this->game_name;
    }

    public function setGame_name(string $game_name): self
    {
        $this->game_name = $game_name;
        return $this;
    }

    public function getImage_path(): string
    {
        return $this->image_path;
    }

    public function setImage_path(string $image_path): self
    {
        $this->image_path = $image_path;
        return $this;
    }

    public function getGame_type(): string
    {
        return $this->game_type;
    }

    public function setGame_type(string $game_type): self
    {
        $this->game_type = $game_type;
        return $this;
    }
}
