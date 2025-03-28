<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Enum\RoleType;

#[ORM\Entity]
#[ORM\Table(name: "utilisateur")]
class Utilisateur
{
    public function __construct()
    {
        $this->privilege = 'regular';
        $this->ban = false;
        $this->countRep = 0;
        $this->questions = new ArrayCollection();
        $this->questionVotes = new ArrayCollection();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer", name: "id")]
    private int $id;

    #[ORM\Column(type: "string", length: 255, name: "nom")]
    private string $nom;

    #[ORM\Column(type: "string", length: 255, name: "prenom")]
    private string $prenom;

    #[ORM\Column(type: "string", length: 255, name: "email")]
    private string $email;

    #[ORM\Column(type: "string", length: 255, name: "nickname")]
    private string $nickname;

    #[ORM\Column(type: "integer", name: "numero")]
    private int $numero;

    #[ORM\Column(type: "string", length: 255, name: "mot_passe")]
    private string $mot_passe;

    #[ORM\Column(type: "string", enumType: RoleType::class, name: "role")]
    private RoleType $role;

    #[ORM\Column(type: "string", length: 50, name: "privilege", options: ["default" => "regular"])]
    private string $privilege;

    #[ORM\Column(type: "boolean", name: "ban", options: ["default" => 0])]
    private bool $ban;

    #[ORM\Column(type: "datetime", name: "banTime", nullable: true)]
    private ?\DateTimeInterface $banTime = null;

    #[ORM\Column(type: "integer", name: "countRep", options: ["default" => 0])]
    private int $countRep;

    #[ORM\Column(type: "string", length: 255, name: "photo", nullable: true)]
    private ?string $photo = null;

    #[ORM\OneToMany(mappedBy: "utilisateur_id", targetEntity: Questions::class)]
    private Collection $questions;

    #[ORM\OneToMany(mappedBy: "user_id", targetEntity: QuestionVotes::class)]
    private Collection $questionVotes;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getNickname(): string
    {
        return $this->nickname;
    }

    public function setNickname(string $nickname): self
    {
        $this->nickname = $nickname;
        return $this;
    }

    public function getNumero(): int
    {
        return $this->numero;
    }

    public function setNumero(int $numero): self
    {
        $this->numero = $numero;
        return $this;
    }

    public function getMotPasse(): string
    {
        return $this->mot_passe;
    }

    public function setMotPasse(string $mot_passe): self
    {
        $this->mot_passe = $mot_passe;
        return $this;
    }

    public function getRole(): RoleType
    {
        return $this->role;
    }

    public function setRole(RoleType $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getPrivilege(): string
    {
        return $this->privilege;
    }

    public function setPrivilege(string $privilege): self
    {
        $this->privilege = $privilege;
        return $this;
    }

    public function isBan(): bool
    {
        return $this->ban;
    }

    public function setBan(bool $ban): self
    {
        $this->ban = $ban;
        return $this;
    }

    public function getBanTime(): ?\DateTimeInterface
    {
        return $this->banTime;
    }

    public function setBanTime(?\DateTimeInterface $banTime): self
    {
        $this->banTime = $banTime;
        return $this;
    }

    public function getCountRep(): int
    {
        return $this->countRep;
    }

    public function setCountRep(int $countRep): self
    {
        $this->countRep = $countRep;
        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): self
    {
        $this->photo = $photo;
        return $this;
    }

    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(Questions $question): self
    {
        if (!$this->questions->contains($question)) {
            $this->questions[] = $question;
            $question->setUtilisateurId($this);
        }
        return $this;
    }

    public function removeQuestion(Questions $question): self
    {
        if ($this->questions->removeElement($question)) {
            if ($question->getUtilisateurId() === $this) {
                $question->setUtilisateurId(null);
            }
        }
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
            $questionVote->setUserId($this);
        }
        return $this;
    }

    public function removeQuestionVote(QuestionVotes $questionVote): self
    {
        if ($this->questionVotes->removeElement($questionVote)) {
            if ($questionVote->getUserId() === $this) {
                $questionVote->setUserId(null);
            }
        }
        return $this;
    }
}