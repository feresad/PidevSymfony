<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use App\Entity\Utilisateur;

#[ORM\Entity]
class Question_votes
{
    public function __construct()
    {
    }


    #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: Questions::class, inversedBy: "question_votess")]
    #[ORM\JoinColumn(name: 'question_id', referencedColumnName: 'question_id', onDelete: 'CASCADE')]
    private Questions $question_id;

    #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: "question_votess")]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Utilisateur $user_id;

    #[ORM\Column(type: "string")]
    private string $vote_type;

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

    public function getVote_type(): string
    {
        return $this->vote_type;
    }

    public function setVote_type(string $vote_type): self
    {
        $this->vote_type = $vote_type;
        return $this;
    }
}
