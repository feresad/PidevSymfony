<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Commentaire;
use App\Entity\Utilisateur;
use App\Enum\VoteType;

#[ORM\Entity]
class CommentaireVotes
{
    public function __construct()
    {
        $this->vote_type = VoteType::NONE; // Default value to match the table
    }

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Commentaire::class, inversedBy: "commentaireVotes")]
    #[ORM\JoinColumn(name: "commentaire_id", referencedColumnName: "commentaire_id", onDelete: "CASCADE")]
    private Commentaire $commentaire_id;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", onDelete: "CASCADE")]
    private Utilisateur $user_id;

    #[ORM\Column(type: "string", enumType: VoteType::class)]
    private VoteType $vote_type;

    public function getCommentaireId(): Commentaire
    {
        return $this->commentaire_id;
    }

    public function setCommentaireId(Commentaire $commentaire_id): self
    {
        $this->commentaire_id = $commentaire_id;
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