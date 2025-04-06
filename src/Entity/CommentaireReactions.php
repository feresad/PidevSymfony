<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Commentaire;
use App\Entity\Utilisateur;

#[ORM\Entity]
#[ORM\Table(name: "commentaire_reactions")]
#[ORM\UniqueConstraint(name: "commentaire_user_unique", columns: ["commentaire_id", "user_id"])]
class CommentaireReactions
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Commentaire::class, inversedBy: "commentaireReactions")]
    #[ORM\JoinColumn(name: "commentaire_id", referencedColumnName: "commentaire_id", onDelete: "CASCADE")]
    private Commentaire $commentaire_id;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", onDelete: "CASCADE")]
    private Utilisateur $user_id;

    #[ORM\Column(type: "string", length: 255)]
    private string $emoji;

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

    public function getEmoji(): string
    {
        return $this->emoji;
    }

    public function setEmoji(string $emoji): self
    {
        $this->emoji = $emoji;
        return $this;
    }
}