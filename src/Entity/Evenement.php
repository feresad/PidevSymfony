<?php

namespace App\Entity;

use App\Repository\EvenementRepository;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: EvenementRepository::class)]
class Evenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom de l'événement ne peut pas être vide.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le nom de l'événement ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $nomEvent = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Le nombre maximum de places ne peut pas être vide.")]
    #[Assert\Positive(message: "Le nombre maximum de places doit être un nombre positif.")]
    private ?int $maxPlacesEvent = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: false)]
    #[Assert\NotNull(message: "La date de l'événement est obligatoire.")]
    #[Assert\GreaterThanOrEqual("tomorrow", message: "L'événement doit être prévu au moins 24 heures à l'avance.")]
    private ?\DateTimeInterface $dateEvent = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le lieu de l'événement ne peut pas être vide.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le lieu de l'événement ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $lieuEvent = null;
    
    #[ORM\Column(type: "string", length: 255)]
    private string $photoEvent;

    #[ORM\ManyToOne(targetEntity: CategorieEvent::class, inversedBy: 'evenements')]
    #[ORM\JoinColumn(name: 'categorie_id', referencedColumnName: 'id')]
    #[Assert\NotNull(message: "L'événement doit appartenir à une catégorie.")]
    private ?CategorieEvent $categorie = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomEvent(): ?string
    {
        return $this->nomEvent;
    }

    public function setNomEvent(string $nomEvent): static
    {
        $this->nomEvent = $nomEvent;

        return $this;
    }

    public function getMaxPlacesEvent(): ?int
    {
        return $this->maxPlacesEvent;
    }

    public function setMaxPlacesEvent(int $maxPlacesEvent): static
    {
        $this->maxPlacesEvent = $maxPlacesEvent;

        return $this;
    }

    public function getDateEvent(): ?\DateTimeInterface
    {
        return $this->dateEvent;
    }

    public function setDateEvent(?\DateTimeInterface $dateEvent): static
    {
        $this->dateEvent = $dateEvent;

        return $this;
    }

    public function getLieuEvent(): ?string
    {
        return $this->lieuEvent;
    }

    public function setLieuEvent(string $lieuEvent): static
    {
        $this->lieuEvent = $lieuEvent;

        return $this;
    }

    public function getCategorie(): ?CategorieEvent
    {
        return $this->categorie;
    }

    public function setCategorie(?CategorieEvent $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }
    public function getPhotoEvent(): string
    {
        return $this->photoEvent;
    }

    public function setPhotoEvent(string $photo_event): self
    {
        $this->photoEvent = $photo_event;
        return $this;
    }
    /**
     * Custom validation to ensure the event date is not in the past.
     */
    #[Assert\Callback]
    public function validateDateEvent(ExecutionContextInterface $context): void
    {
        if ($this->dateEvent !== null) {
            $now = new \DateTime();
            if ($this->dateEvent < $now) {
                $context->buildViolation('La date de l\'événement ne peut pas être dans le passé.')
                    ->atPath('dateEvent')
                    ->addViolation();
            }
        }
    }
}
