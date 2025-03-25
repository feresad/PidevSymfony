<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use App\Entity\Utilisateur;

#[ORM\Entity]
class Commentaire_votes
{
    public function __construct()
    {
    }


    #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: Commentaire::class, inversedBy: "commentaire_votess")]
    #[ORM\JoinColumn(name: 'commentaire_id', referencedColumnName: 'commentaire_id', onDelete: 'CASCADE')]
    private Commentaire $commentaire_id;

    #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: "commentaire_votess")]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Utilisateur $user_id;

    #[ORM\Column(type: "string")]
    private string $vote_type;

    public function getCommentaire_id(): int
    {
        return $this->commentaire_id;
    }

    public function setCommentaire_id(int $commentaire_id): self
    {
        $this->commentaire_id = $commentaire_id;
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
