<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Questions;
use App\Entity\Utilisateur;
use App\Enum\VoteType;

#[ORM\Entity]
class QuestionVotes
{
    public function __construct()
    {
        $this->vote_type = VoteType::NONE;
    }

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Questions::class, inversedBy: "questionVotes")]
    #[ORM\JoinColumn(name: "question_id", referencedColumnName: "question_id", onDelete: "CASCADE")]
    private ?Questions $question_id = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", onDelete: "CASCADE")]
    private ?Utilisateur $user_id = null;

    #[ORM\Column(type: "string", enumType: VoteType::class)]
    private VoteType $vote_type;

    public function getQuestionId(): ?Questions
    {
        return $this->question_id;
    }
    
    public function setQuestionId(?Questions $question_id): self
    {
        $this->question_id = $question_id;
        return $this;
    }

    public function getUserId(): ?Utilisateur
    {
        return $this->user_id;
    }
    
    public function setUserId(?Utilisateur $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }

    public function getVoteType(): VoteType
    {
        return $this->vote_type;
    }

    public function setVoteType(VoteType $vote_type): self
    {
        $this->vote_type = $vote_type;
        return $this;
    }
}