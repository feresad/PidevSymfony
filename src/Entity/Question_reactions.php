<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use App\Entity\Utilisateur;

#[ORM\Entity]
class Question_reactions
{
    public function __construct()
    {
    }


    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $reaction_id;

        #[ORM\ManyToOne(targetEntity: Questions::class, inversedBy: "question_reactionss")]
    #[ORM\JoinColumn(name: 'question_id', referencedColumnName: 'question_id', onDelete: 'CASCADE')]
    private Questions $question_id;

        #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: "question_reactionss")]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Utilisateur $user_id;

    #[ORM\Column(type: "string", length: 255)]
    private string $emoji;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $created_at;

    public function getReaction_id(): int
    {
        return $this->reaction_id;
    }

    public function setReaction_id(int $reaction_id): self
    {
        $this->reaction_id = $reaction_id;
        return $this;
    }

    public function getQuestion_id(): int
    {
        return $this->question_id;
    }

    public function setQuestion_id(int $question_id): self
    {
        $this->question_id = $question_id;
        return $this;
    }

    public function getUser_id(): int
    {
        return $this->user_id;
    }

    public function setUser_id(int $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }

    public function getEmoji(): string
    {
        return $this->emoji;
    }

    public function setEmoji(string $emoji): self
    {
        $this->emoji = $emoji;
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
}
