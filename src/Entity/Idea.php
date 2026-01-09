<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class Idea
{
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 200)]
    private ?string $title = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 10, max: 2000)]
    private ?string $description = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50)]
    private ?string $category = null;

    private ?\DateTimeImmutable $createdAt = null;

    private ?int $authorId = null;

    private ?string $authorName = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getAuthorId(): ?int
    {
        return $this->authorId;
    }

    public function setAuthorId(?int $authorId): self
    {
        $this->authorId = $authorId;
        return $this;
    }

    public function getAuthorName(): ?string
    {
        return $this->authorName;
    }

    public function setAuthorName(?string $authorName): self
    {
        $this->authorName = $authorName;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
            'authorId' => $this->authorId,
            'authorName' => $this->authorName,
        ];
    }

    public static function fromArray(array $data): self
    {
        $idea = new self();
        $idea->id = $data['id'] ?? null;
        $idea->title = $data['title'] ?? null;
        $idea->description = $data['description'] ?? null;
        $idea->category = $data['category'] ?? null;
        $idea->createdAt = isset($data['createdAt'])
            ? new \DateTimeImmutable($data['createdAt'])
            : new \DateTimeImmutable();
        $idea->authorId = $data['authorId'] ?? null;
        $idea->authorName = $data['authorName'] ?? null;
        return $idea;
    }
}
