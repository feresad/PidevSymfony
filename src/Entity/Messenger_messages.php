<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;


#[ORM\Entity]
class Messenger_messages
{
    public function __construct()
    {
    }


    #[ORM\Id]
    #[ORM\Column(type: "bigint")]
    private string $id;

    #[ORM\Column(type: "text")]
    private string $body;

    #[ORM\Column(type: "text")]
    private string $headers;

    #[ORM\Column(type: "string", length: 190)]
    private string $queue_name;

    #[ORM\Column(type: "string")]
    private string $created_at;

    #[ORM\Column(type: "string")]
    private string $available_at;

    #[ORM\Column(type: "string")]
    private string $delivered_at;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function getHeaders(): string
    {
        return $this->headers;
    }

    public function setHeaders(string $headers): self
    {
        $this->headers = $headers;
        return $this;
    }

    public function getQueue_name(): string
    {
        return $this->queue_name;
    }

    public function setQueue_name(string $queue_name): self
    {
        $this->queue_name = $queue_name;
        return $this;
    }

    public function getCreated_at(): mixed
    {
        return $this->created_at;
    }

    public function setCreated_at(mixed $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getAvailable_at(): mixed
    {
        return $this->available_at;
    }

    public function setAvailable_at(mixed $available_at): self
    {
        $this->available_at = $available_at;
        return $this;
    }

    public function getDelivered_at(): mixed
    {
        return $this->delivered_at;
    }

    public function setDelivered_at(mixed $delivered_at): self
    {
        $this->delivered_at = $delivered_at;
        return $this;
    }
}
