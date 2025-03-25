<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use App\Entity\Utilisateur;

#[ORM\Entity]
class Reports
{
    public function __construct()
    {
    }


    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $reportId;

        #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: "reportss")]
    #[ORM\JoinColumn(name: 'reporterId', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Utilisateur $reporterId;

        #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: "reportss")]
    #[ORM\JoinColumn(name: 'reportedUserId', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Utilisateur $reportedUserId;

    #[ORM\Column(type: "string")]
    private string $reason;

    #[ORM\Column(type: "string", length: 255)]
    private string $evidence;

    #[ORM\Column(type: "string")]
    private string $status;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $created_at;

    public function getReportId(): int
    {
        return $this->reportId;
    }

    public function setReportId(int $reportId): self
    {
        $this->reportId = $reportId;
        return $this;
    }

    public function getReporterId(): int
    {
        return $this->reporterId;
    }

    public function setReporterId(int $reporterId): self
    {
        $this->reporterId = $reporterId;
        return $this;
    }

    public function getReportedUserId(): int
    {
        return $this->reportedUserId;
    }

    public function setReportedUserId(int $reportedUserId): self
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

    public function getEvidence(): string
    {
        return $this->evidence;
    }

    public function setEvidence(string $evidence): self
    {
        $this->evidence = $evidence;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getCreated_at(): \DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreated_at(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }
}
