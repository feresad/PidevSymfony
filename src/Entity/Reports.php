<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Utilisateur;

#[ORM\Entity]
#[ORM\Table(name: "reports")]
class Reports
{
    public function __construct()
    {
        $this->status = 'PENDING';
        $this->created_at = new \DateTime();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer", name: "reportId")]
    private int $reportId;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: "reportss")]
    #[ORM\JoinColumn(name: 'reporterId', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Utilisateur $reporterId;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: "reportss")]
    #[ORM\JoinColumn(name: 'reportedUserId', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Utilisateur $reportedUserId;

    #[ORM\Column(
        type: "string",
        columnDefinition: "ENUM('MINEUR_IMPLIQUE','HARCELEMENT','SUICIDE_AUTOMUTILATION','CONTENU_VIOLENT','VENTE_ARTICLES_RESTREINTS','CONTENU_ADULTE','ARNAQUE_FAUSSE_INFORMATION','CONTENU_NON_DESIRE')"
    )]
    private string $reason;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $evidence = null;

    #[ORM\Column(
        type: "string",
        columnDefinition: "ENUM('PENDING','REVIEWED','RESOLVED')",
        options: ["default" => "PENDING"]
    )]
    private ?string $status = 'PENDING';

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $created_at;

    // Getters and Setters remain unchanged...
    public function getReportId(): int
    {
        return $this->reportId;
    }

    public function setReportId(int $reportId): self
    {
        $this->reportId = $reportId;
        return $this;
    }

    public function getReporterId(): Utilisateur
    {
        return $this->reporterId;
    }

    public function setReporterId(Utilisateur $reporterId): self
    {
        $this->reporterId = $reporterId;
        return $this;
    }

    public function getReportedUserId(): Utilisateur
    {
        return $this->reportedUserId;
    }

    public function setReportedUserId(Utilisateur $reportedUserId): self
    {
        $this->reportedUserId = $reportedUserId;
        return $this;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }

    public function getEvidence(): ?string
    {
        return $this->evidence;
    }

    public function setEvidence(?string $evidence): self
    {
        $this->evidence = $evidence;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status ?? 'PENDING'; // Fallback to 'PENDING' if null
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }
}