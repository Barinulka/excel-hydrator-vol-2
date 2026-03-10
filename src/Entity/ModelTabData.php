<?php

namespace App\Entity;

use App\Repository\ModelTabDataRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ModelTabDataRepository::class)]
#[ORM\Table(name: 'model_tab_data', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'uniq_model_tab_data_model_tab', columns: ['model_id', 'tab_key']),
])]
#[UniqueEntity(fields: ['model', 'tabKey'], message: 'Данные для выбранной вкладки уже существуют.')]
class ModelTabData
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'modelTabData')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Model $model = null;

    #[ORM\Column(name: 'tab_key', length: 64)]
    #[Assert\NotBlank(message: 'Ключ вкладки обязателен.')]
    #[Assert\Choice(choices: ['time_params', 'ebitda', 'salary', 'other'], message: 'Недопустимая вкладка.')]
    private ?string $tabKey = null;

    #[ORM\Column]
    private array $payload = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModel(): ?Model
    {
        return $this->model;
    }

    public function setModel(?Model $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getTabKey(): ?string
    {
        return $this->tabKey;
    }

    public function setTabKey(string $tabKey): static
    {
        $this->tabKey = $tabKey;

        return $this;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function setPayload(array $payload): static
    {
        $this->payload = $payload;

        return $this;
    }
}
