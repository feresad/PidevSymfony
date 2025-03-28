<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Questions;
use App\Enum\GameType;

#[ORM\Entity]
class Games
{
    public function __construct()
    {
        $this->questions = new ArrayCollection();
        $this->game_type = GameType::OTHER; // Default value to match the table
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $game_id;

    #[ORM\Column(type: "string", length: 255, unique: true)]
    private string $game_name;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $image_path = null;

    #[ORM\Column(type: "string", enumType: GameType::class)]
    private GameType $game_type;

    #[ORM\OneToMany(mappedBy: "game_id", targetEntity: Questions::class)]
    private Collection $questions;

    public function getGameId(): int
    {
        return $this->game_id;
    }

    public function setGameId(int $game_id): self
    {
        $this->game_id = $game_id;
        return $this;
    }

    public function getGameName(): string
    {
        return $this->game_name;
    }

    public function setGameName(string $game_name): self
    {
        $this->game_name = $game_name;
        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->image_path;
    }

    public function setImagePath(?string $image_path): self
    {
        $this->image_path = $image_path;
        return $this;
    }

    public function getGameType(): GameType
    {
        return $this->game_type;
    }

    public function setGameType(GameType $game_type): self
    {
        $this->game_type = $game_type;
        return $this;
    }

    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(Questions $question): self
    {
        if (!$this->questions->contains($question)) {
            $this->questions[] = $question;
            $question->setGameId($this);
        }
        return $this;
    }

    public function removeQuestion(Questions $question): self
    {
        if ($this->questions->removeElement($question)) {
            if ($question->getGameId() === $this) {
                $question->setGameId(null);
            }
        }
        return $this;
    }
}