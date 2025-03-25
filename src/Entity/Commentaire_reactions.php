<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use App\Entity\Utilisateur;

#[ORM\Entity]
class Commentaire_reactions
{
    public function __construct()
    {
    }


    #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: Commentaire::class, inversedBy: "commentaire_reactionss")]
    #[ORM\JoinColumn(name: 'commentaire_id', referencedColumnName: 'commentaire_id', onDelete: 'CASCADE')]
    private Commentaire $commentaire_id;

    #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: "commentaire_reactionss")]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Utilisateur $user_id;

    #[ORM\Column(type: "string", length: 255)]
    private string $emoji;

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
