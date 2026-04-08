<?php

namespace App\Entity;

use App\Enum\ModelForecastStep;
use App\Repository\ModelTimeParamsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ModelTimeParamsRepository::class)]
#[ORM\Table(name: 'model_time_params')]
class ModelTimeParams
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'timeParams')]
    #[ORM\JoinColumn(unique: true, nullable: false, onDelete: 'CASCADE')]
    private ?Model $model = null;

    #[Assert\NotNull(message: 'Не указана дата начала инвестиций.')]
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $investmentStartDate = null;

    #[Assert\NotNull(message: 'Не указана длительность инвестиций.')]
    #[Assert\Positive(message: 'Длительность инвестиций не может быть отрицательной')]
    #[Assert\Range(
        notInRangeMessage: 'Укажите длительность инвестиций в промежутке от {{ min }}мес. до {{ max }}мес.',
        min: 1,
        max: 600
    )]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $investmentDurationMonths = null;

    #[Assert\NotNull(message: 'Не указана длительность коммерческой работы.')]
    #[Assert\Positive(message: 'Длительность коммерческой работы не может быть отрицательной')]
    #[Assert\Range(
        notInRangeMessage: 'Укажите длительность коммерческой работы в промежутке от {{ min }}мес. до {{ max }}мес.',
        min: 1,
        max: 1200
    )]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $commercialOperationDurationMonths = null;

    #[Assert\NotNull(message: 'Не указана шаг прогнозирования.')]
    #[ORM\Column(length: 16, enumType: ModelForecastStep::class)]
    private ?ModelForecastStep $forecastStep = null;

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

    public function getInvestmentStartDate(): ?\DateTimeImmutable
    {
        return $this->investmentStartDate;
    }

    public function setInvestmentStartDate(\DateTimeImmutable $investmentStartDate): static
    {
        $this->investmentStartDate = $investmentStartDate;

        return $this;
    }

    public function getInvestmentDurationMonths(): ?int
    {
        return $this->investmentDurationMonths;
    }

    public function setInvestmentDurationMonths(int $investmentDurationMonths): static
    {
        $this->investmentDurationMonths = $investmentDurationMonths;

        return $this;
    }

    public function getCommercialOperationDurationMonths(): ?int
    {
        return $this->commercialOperationDurationMonths;
    }

    public function setCommercialOperationDurationMonths(int $commercialOperationDurationMonths): static
    {
        $this->commercialOperationDurationMonths = $commercialOperationDurationMonths;

        return $this;
    }

    public function getForecastStep(): ?ModelForecastStep
    {
        return $this->forecastStep;
    }

    public function setForecastStep(ModelForecastStep $forecastStep): static
    {
        $this->forecastStep = $forecastStep;

        return $this;
    }
}
