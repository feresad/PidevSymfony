<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Games;
use App\Entity\Utilisateur;
use App\Entity\QuestionVotes;
use App\Entity\QuestionReactions;
use App\Entity\Commentaire;
use App\Enum\MediaType;

#[ORM\Entity]
#[ORM\Table(name: "questions")]
class Questions
{
    public function __construct()
    {
        $this->questionVotes = new ArrayCollection();
        $this->questionReactions = new ArrayCollection();
        $this->commentaires = new ArrayCollection();
        $this->media_type = MediaType::IMAGE; // Default value to match the table's not null constraint
        $this->votes = 0; // Default value for votes
        $this->created_at = new \DateTime(); // Default value for created_at
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $question_id;

    #[ORM\ManyToOne(targetEntity: Games::class, inversedBy: "questions")]
    #[ORM\JoinColumn(name: "game_id", referencedColumnName: "game_id", onDelete: "CASCADE", nullable: true)]
    private ?Games $game_id = null;

  // In Questions.php
#[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: "questions")]
#[ORM\JoinColumn(name: "utilisateur_id", referencedColumnName: "id", nullable: false)]
private Utilisateur $utilisateur_id;

    #[ORM\Column(type: "string", length: 255)]
    private string $title;

    #[ORM\Column(type: "text")]
    private string $content;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $votes = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $media_path = null;

    #[ORM\Column(type: "string", enumType: MediaType::class)]
    private MediaType $media_type;

    #[ORM\OneToMany(mappedBy: "question_id", targetEntity: QuestionVotes::class)]
    private Collection $questionVotes;

    #[ORM\OneToMany(mappedBy: "question_id", targetEntity: QuestionReactions::class)]
    private Collection $questionReactions;

    #[ORM\OneToMany(mappedBy: "question_id", targetEntity: Commentaire::class)]
    private Collection $commentaires;

    public function getQuestionId(): int
    {
        return $this->question_id;
    }

    public function setQuestionId(int $question_id): self
    {
        $this->question_id = $question_id;
        return $this;
    }

    public function getGameId(): ?Games
    {
        return $this->game_id;
    }

    public function setGameId(?Games $game_id): self
    {
        $this->game_id = $game_id;
        return $this;
    }

    public function getUtilisateurId(): Utilisateur
    {
        return $this->utilisateur_id;
    }
    
    public function setUtilisateurId(?Utilisateur $utilisateur_id): self
    {
        $this->utilisateur_id = $utilisateur_id;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getVotes(): ?int
    {
        return $this->votes;
    }

    public function setVotes(?int $votes): self
    {
        $this->votes = $votes;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getMediaPath(): ?string
    {
        return $this->media_path;
    }

    public function setMediaPath(?string $media_path): self
    {
        $this->media_path = $media_path;
        return $this;
    }

    public function getMediaType(): MediaType
    {
        return $this->media_type;
    }

    public function setMediaType(MediaType $media_type): self
    {
        $this->media_type = $media_type;
        return $this;
    }

    public function getQuestionVotes(): Collection
    {
        return $this->questionVotes;
    }

    public function addQuestionVote(QuestionVotes $questionVote): self
    {
        if (!$this->questionVotes->contains($questionVote)) {
            $this->questionVotes[] = $questionVote;
            $questionVote->setQuestionId($this);
        }
        return $this;
    }

    public function removeQuestionVote(QuestionVotes $questionVote): self
    {
        $this->questionVotes->removeElement($questionVote);
        return $this;
    }

    public function getQuestionReactions(): Collection
    {
        return $this->questionReactions;
    }

    public function addQuestionReaction(QuestionReactions $questionReaction): self
    {
        if (!$this->questionReactions->contains($questionReaction)) {
            $this->questionReactions[] = $questionReaction;
            $questionReaction->setQuestionId($this);
        }
        return $this;
    }

    public function removeQuestionReaction(QuestionReactions $questionReaction): self
    {
        $this->questionReactions->removeElement($questionReaction);
        return $this;
    }

    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    public function addCommentaire(Commentaire $commentaire): self
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires[] = $commentaire;
            $commentaire->setQuestionId($this);
        }
        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): self
    {
        if ($this->commentaires->removeElement($commentaire)) {
            if ($commentaire->getQuestionId() === $this) {
                $commentaire->setQuestionId(null);
            }
        }
        return $this;
    }
}