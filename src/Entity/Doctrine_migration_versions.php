<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;


#[ORM\Entity]
class Doctrine_migration_versions
{
    public function __construct()
    {
    }


    #[ORM\Id]
    #[ORM\Column(type: "string", length: 191)]
    private string $version;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $executed_at;

    #[ORM\Column(type: "integer")]
    private int $execution_time;

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;
        return $this;
    }

    public function getExecuted_at(): \DateTimeInterface
    {
        return $this->executed_at;
    }

    public function setExecuted_at(\DateTimeInterface $executed_at): self
    {
        $this->executed_at = $executed_at;
        return $this;
    }

    public function getExecution_time(): int
    {
        return $this->execution_time;
    }

    public function setExecution_time(int $execution_time): self
    {
        $this->execution_time = $execution_time;
        return $this;
    }
}
