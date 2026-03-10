<?php

namespace App\Entity;

use App\Repository\ModelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ModelRepository::class)]
#[ORM\Table(name: 'model', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'uniq_model_project_version', columns: ['project_id', 'version_number']),
])]
#[UniqueEntity(fields: ['project', 'versionNumber'], message: 'Версия модели уже существует для этого проекта.')]
class Model
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'models')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author = null;

    #[ORM\ManyToOne(inversedBy: 'models')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Project $project = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Название модели обязательно для заполнения.')]
    private ?string $title = null;

    #[ORM\Column(name: 'short_id', length: 10, unique: true)]
    #[Assert\NotBlank(message: 'Короткий идентификатор модели обязателен.')]
    #[Assert\Regex(
        pattern: '/^[A-Za-z0-9]{10}$/',
        message: 'Короткий идентификатор модели должен содержать 10 символов латиницы и цифр.',
    )]
    private ?string $short_id = null;

    #[ORM\Column(length: 32)]
    #[Assert\Choice(choices: ['draft', 'active', 'archived'], message: 'Недопустимый статус модели.')]
    private ?string $status = 'draft';

    /**
     * @var Collection<int, ModelTabData>
     */
    #[ORM\OneToMany(targetEntity: ModelTabData::class, mappedBy: 'model', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $modelTabData;

    #[ORM\Column(name: 'version_number')]
    #[Assert\Positive(message: 'Номер версии должен быть больше нуля.')]
    private ?int $versionNumber = 1;

    public function __construct()
    {
        $this->modelTabData = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(Project $project): static
    {
        $this->project = $project;

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

    public function getShortId(): ?string
    {
        return $this->short_id;
    }

    public function setShortId(string $short_id): static
    {
        $this->short_id = $short_id;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<int, ModelTabData>
     */
    public function getModelTabData(): Collection
    {
        return $this->modelTabData;
    }

    public function addModelTabData(ModelTabData $modelTabData): static
    {
        if (!$this->modelTabData->contains($modelTabData)) {
            $this->modelTabData->add($modelTabData);
            $modelTabData->setModel($this);
        }

        return $this;
    }

    public function removeModelTabData(ModelTabData $modelTabData): static
    {
        if ($this->modelTabData->removeElement($modelTabData)) {
            if ($modelTabData->getModel() === $this) {
                $modelTabData->setModel(null);
            }
        }

        return $this;
    }

    public function getVersionNumber(): ?int
    {
        return $this->versionNumber;
    }

    public function setVersionNumber(int $versionNumber): static
    {
        $this->versionNumber = $versionNumber;

        return $this;
    }

    public function getVersionLabel(): string
    {
        return sprintf('v%d', $this->versionNumber ?? 1);
    }
}
