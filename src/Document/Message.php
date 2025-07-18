<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

#[MongoDB\Document(collection: "messages")]
class Message
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: "string")]
    #[Assert\NotBlank(message: "L'objet du message ne peut pas être vide.")]
    private ?string $subject = null;

    #[MongoDB\Field(type: "string")]
    #[Assert\NotBlank(message: "Le message ne peut pas être vide.")]
    private ?string $content = null;

    #[MongoDB\Field(type: "date")]
    private ?\DateTimeImmutable $createdAt = null;

    #[MongoDB\Field(type: "string")]
    private ?string $authorId = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters & setters

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getAuthorId(): ?string
    {
        return $this->authorId;
    }

    public function setAuthorId(?string $authorId): static
    {
        $this->authorId = $authorId;
        return $this;
    }
}
