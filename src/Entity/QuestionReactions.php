<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Questions;
use App\Entity\Utilisateur;

#[ORM\Entity]
#[ORM\UniqueConstraint(name: "question_user_emoji_unique", columns: ["question_id", "user_id", "emoji"])]
class QuestionReactions
{
    public function __construct()
    {
        $this->created_at = new \DateTime(); // Default value to match the table
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $reaction_id;

    #[ORM\ManyToOne(targetEntity: Questions::class, inversedBy: "questionReactions")]
    #[ORM\JoinColumn(name: "question_id", referencedColumnName: "question_id", onDelete: "CASCADE")]
    private Questions $question_id;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", onDelete: "CASCADE")]
    private Utilisateur $user_id;

    #[ORM\Column(type: "string", length: 255)]
    private string $emoji;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $created_at;

    public function getReactionId(): int
    {
        return $this->reaction_id;
    }

    public function setReactionId(int $reaction_id): self
    {
        $this->reaction_id = $reaction_id;
        return $this;
    }

    public function getQuestionId(): Questions
    {
        return $this->question_id;
    }

    public function setQuestionId(Questions $question_id): self
    {
        $this->question_id = $question_id;
        return $this;
    }

    public function getUserId(): Utilisateur
    {
        return $this->user_id;
    }

    public function setUserId(Utilisateur $user_id): self
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

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }
}