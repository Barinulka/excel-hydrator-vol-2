<?php

namespace App\DTO\Model\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateTimeParamsRequestDTO
{
    #[Assert\NotBlank(message: 'Укажите дату начала инвестиций.')]
    #[Assert\Regex(
        pattern: '/^\d{4}-(0[1-9]|1[0-2])$/',
        message: 'Формат даты должен быть YYYY-MM.'
    )]
    public ?string $investmentStartMonth = null;

    #[Assert\NotNull(message: 'Укажите длительность инвестиций.')]
    #[Assert\Positive(message: 'Длительность инвестиций должна быть больше нуля.')]
    #[Assert\LessThanOrEqual(
        value: 600,
        message: 'Длительность инвестиций слишком большая.'
    )]
    public ?int $investmentDurationMonths = null;

    #[Assert\NotNull(message: 'Укажите длительность коммерческой работы.')]
    #[Assert\Positive(message: 'Длительность коммерческой работы должна быть больше нуля.')]
    #[Assert\LessThanOrEqual(
        value: 1200,
        message: 'Длительность коммерческой работы слишком большая.'
    )]
    public ?int $commercialOperationDurationMonths = null;

    #[Assert\NotBlank(message: 'Выберите шаг прогнозирования.')]
    #[Assert\Choice(
        choices: ['month', 'quarter', 'year'],
        message: 'Недопустимый шаг прогнозирования.'
    )]
    public ?string $forecastStep = null;
}
