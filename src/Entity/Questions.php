<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Question_votes;

#[ORM\Entity]
class Questions
{
    public function __construct()
    {
    }


    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $question_id;

    #[ORM\Column(type: "integer")]
    private int $game_id;

    #[ORM\Column(type: "integer")]
    private int $utilisateur_id;

    #[ORM\Column(type: "string", length: 255)]
    private string $title;

    #[ORM\Column(type: "text")]
    private string $content;

    #[ORM\Column(type: "integer")]
    private int $votes;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $created_at;

    #[ORM\Column(type: "string", length: 255)]
    private string $media_path;

    #[ORM\Column(type: "string")]
    private string $media_type;

    public function getQuestion_id(): int
    {
        return $this->question_id;
    }

    public function setQuestion_id(int $question_id): self
    {
        $this->question_id = $question_id;
        return $this;
    }

    public function getGame_id(): int
    {
        return $this->game_id;
    }

    public function setGame_id(int $game_id): self
    {
        $this->game_id = $game_id;
        return $this;
    }

    public function getUtilisateur_id(): int
    {
        return $this->utilisateur_id;
    }

    public function setUtilisateur_id(int $utilisateur_id): self
    {
        $this->utilisateur_id = $utilisateur_id;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getVotes(): int
    {
        return $this->votes;
    }

    public function setVotes(int $votes): self
    {
        $this->votes = $votes;
        return $this;
    }

    public function getCreated_at(): \DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreated_at(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getMedia_path(): string
    {
        return $this->media_path;
    }

    public function setMedia_path(string $media_path): self
    {
        $this->media_path = $media_path;
        return $this;
    }

    public function getMedia_type(): string
    {
        return $this->media_type;
    }

    public function setMedia_type(string $media_type): self
    {
        $this->media_type = $media_type;
        return $this;
    }
}
