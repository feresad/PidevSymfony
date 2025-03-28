<?php

namespace App\Entity;

use App\Repository\Categorie_eventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: Categorie_eventRepository::class)]
class CategorieEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom de la catégorie ne peut pas être vide.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le nom de la catégorie ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La description de la catégorie ne peut pas être vide.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "La description de la catégorie ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $descCategorieEvent = null;

    /**
     * @var Collection<int, Evenement>
     */
    #[ORM\OneToMany(targetEntity: Evenement::class, mappedBy: 'categorie')]
    private Collection $evenements;

    public function __construct()
    {
        $this->evenements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getDescCategorieEvent(): ?string
{
    return $this->descCategorieEvent;
}

public function setDescCategorieEvent(?string $descCategorieEvent): self
{
    $this->descCategorieEvent = $descCategorieEvent;
    return $this;
}

    /**
     * @return Collection<int, Evenement>
     */
    public function getEvenements(): Collection
    {
        return $this->evenements;
    }

    public function addEvenement(Evenement $evenement): static
    {
        if (!$this->evenements->contains($evenement)) {
            $this->evenements->add($evenement);
            $evenement->setCategorie($this);
        }
        return $this;
    }

    public function removeEvenement(Evenement $evenement): static
    {
        if ($this->evenements->removeElement($evenement)) {
            if ($evenement->getCategorie() === $this) {
                $evenement->setCategorie(null);
            }
        }
        return $this;
    }
}