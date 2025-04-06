<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Questions;
use App\Entity\Utilisateur;
use App\Entity\CommentaireVotes;
use App\Entity\CommentaireReactions;

#[ORM\Entity]
class Commentaire
{
    public function __construct()
    {
        $this->commentaireVotes = new ArrayCollection();
        $this->commentaireReactions = new ArrayCollection();
        $this->childCommentaires = new ArrayCollection();
        $this->votes = 0;
        $this->creation_at = new \DateTime();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $commentaire_id;

    #[ORM\ManyToOne(targetEntity: Questions::class, inversedBy: "commentaires")]
    #[ORM\JoinColumn(name: "question_id", referencedColumnName: "question_id", onDelete: "CASCADE", nullable: true)]
    private ?Questions $question_id = null;

    #[ORM\ManyToOne(targetEntity: Commentaire::class, inversedBy: "childCommentaires")]
    #[ORM\JoinColumn(name: "parent_commentaire_id", referencedColumnName: "commentaire_id", onDelete: "CASCADE", nullable: true)]
    private ?Commentaire $parent_commentaire_id = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: "utilisateur_id", referencedColumnName: "id", onDelete: "CASCADE", nullable: true)]
    private ?Utilisateur $utilisateur_id = null;

    #[ORM\Column(type: "text")]
    private string $contenu;

    #[ORM\Column(type: "integer")]
    private int $votes;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $creation_at;

    #[ORM\OneToMany(mappedBy: "commentaire_id", targetEntity: CommentaireVotes::class)]
    private Collection $commentaireVotes;

    #[ORM\OneToMany(mappedBy: "commentaire_id", targetEntity: CommentaireReactions::class)]
    private Collection $commentaireReactions;

    #[ORM\OneToMany(mappedBy: "parent_commentaire_id", targetEntity: Commentaire::class)]
    private Collection $childCommentaires;

    public function getCommentaireId(): int
    {
        return $this->commentaire_id;
    }

    public function setCommentaireId(int $commentaire_id): self
    {
        $this->commentaire_id = $commentaire_id;
        return $this;
    }

    public function getQuestionId(): ?Questions
    {
        return $this->question_id;
    }

    public function setQuestionId(?Questions $question_id): self
    {
        $this->question_id = $question_id;
        return $this;
    }

    public function getParentCommentaireId(): ?Commentaire
    {
        return $this->parent_commentaire_id;
    }

    public function setParentCommentaireId(?Commentaire $parent_commentaire_id): self
    {
        $this->parent_commentaire_id = $parent_commentaire_id;
        return $this;
    }

    public function getUtilisateurId(): ?Utilisateur
    {
        return $this->utilisateur_id;
    }

    public function setUtilisateurId(?Utilisateur $utilisateur_id): self
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

    public function getCreationAt(): \DateTimeInterface
    {
        return $this->creation_at;
    }

    public function setCreationAt(\DateTimeInterface $creation_at): self
    {
        $this->creation_at = $creation_at;
        return $this;
    }

    public function getCommentaireVotes(): Collection
    {
        return $this->commentaireVotes;
    }

    public function addCommentaireVote(CommentaireVotes $commentaireVote): self
    {
        if (!$this->commentaireVotes->contains($commentaireVote)) {
            $this->commentaireVotes[] = $commentaireVote;
            $commentaireVote->setCommentaireId($this);
        }
        return $this;
    }

    public function removeCommentaireVote(CommentaireVotes $commentaireVote): self
    {
        $this->commentaireVotes->removeElement($commentaireVote);
        return $this;
    }

    public function getCommentaireReactions(): Collection
    {
        return $this->commentaireReactions;
    }

    public function addCommentaireReaction(CommentaireReactions $commentaireReaction): self
    {
        if (!$this->commentaireReactions->contains($commentaireReaction)) {
            $this->commentaireReactions[] = $commentaireReaction;
            $commentaireReaction->setCommentaireId($this);
        }
        return $this;
    }

    public function removeCommentaireReaction(CommentaireReactions $commentaireReaction): self
    {
        $this->commentaireReactions->removeElement($commentaireReaction);
        return $this;
    }

    public function getChildCommentaires(): Collection
    {
        return $this->childCommentaires;
    }

    public function addChildCommentaire(Commentaire $childCommentaire): self
    {
        if (!$this->childCommentaires->contains($childCommentaire)) {
            $this->childCommentaires[] = $childCommentaire;
            $childCommentaire->setParentCommentaireId($this);
        }
        return $this;
    }

    public function removeChildCommentaire(Commentaire $childCommentaire): self
    {
        if ($this->childCommentaires->removeElement($childCommentaire)) {
            if ($childCommentaire->getParentCommentaireId() === $this) {
                $childCommentaire->setParentCommentaireId(null);
            }
        }
        return $this;
    }
}