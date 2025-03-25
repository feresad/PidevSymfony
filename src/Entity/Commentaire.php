<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Commentaire_votes;

#[ORM\Entity]
class Commentaire
{
    public function __construct()
    {
    }


    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $commentaire_id;

    #[ORM\Column(type: "integer")]
    private int $question_id;

    #[ORM\Column(type: "integer")]
    private int $parent_commentaire_id;

    #[ORM\Column(type: "integer")]
    private int $utilisateur_id;

    #[ORM\Column(type: "text")]
    private string $contenu;

    #[ORM\Column(type: "integer")]
    private int $votes;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $creation_at;

    public function getCommentaire_id(): int
    {
        return $this->commentaire_id;
    }

    public function setCommentaire_id(int $commentaire_id): self
    {
        $this->commentaire_id = $commentaire_id;
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

    public function getParent_commentaire_id(): int
    {
        return $this->parent_commentaire_id;
    }

    public function setParent_commentaire_id(int $parent_commentaire_id): self
    {
        $this->parent_commentaire_id = $parent_commentaire_id;
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

    public function getContenu(): string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): self
    {
        $this->contenu = $contenu;
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

    public function getCreation_at(): \DateTimeInterface
    {
        return $this->creation_at;
    }

    public function setCreation_at(\DateTimeInterface $creation_at): self
    {
        $this->creation_at = $creation_at;
        return $this;
    }
}
