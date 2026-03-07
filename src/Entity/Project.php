<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
class Project
{
    use TimestampableEntity;

    public function __construct()
    {
        $this->publicId = new Ulid();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'ulid', unique: true, nullable: true)]
    private ?Ulid $publicId = null;

    #[ORM\Column(length: 10, unique: true, nullable: true)]
    private ?string $shortId = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Название проекта обязательно для заполнения.')]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Код проекта обязателен для заполнения.')]
    #[Assert\Regex(
        pattern: '/^[A-Za-z0-9_-]+$/',
        message: 'Код проекта может содержать только латиницу, цифры, дефис и нижнее подчеркивание.',
    )]
    private ?string $code = null;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    private ?User $author = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublicId(): ?Ulid
    {
        return $this->publicId;
    }

    public function ensurePublicId(): void
    {
        if (null === $this->publicId) {
            $this->publicId = new Ulid();
        }
    }

    public function getShortId(): ?string
    {
        return $this->shortId;
    }

    public function setShortId(string $shortId): static
    {
        $this->shortId = $shortId;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }
}
