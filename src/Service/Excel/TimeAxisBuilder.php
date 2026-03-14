<?php

namespace App\Service\Excel;

use App\Exception\Excel\ExcelValidationException;
use App\Service\Excel\ValueObject\TimeAxis;

final class TimeAxisBuilder
{
    /**
     * @throws ExcelValidationException
     */
    public function build(
        string $forecastStep,
        int $investmentDurationMonths,
        int $commercialOperationDurationMonths,
        int $firstPeriodColumnIndex = 5,
    ): TimeAxis {
        if (!in_array($forecastStep, ['month', 'quarter', 'year'], true)) {
            throw new ExcelValidationException('Недопустимый шаг прогнозирования для Excel выгрузки.');
        }

        if ($investmentDurationMonths <= 0 || $commercialOperationDurationMonths <= 0) {
            throw new ExcelValidationException('Длительности инвестиционной и коммерческой фаз должны быть больше нуля.');
        }

        $stepMonths = $this->stepMonths($forecastStep);
        $totalMonths = $investmentDurationMonths + $commercialOperationDurationMonths;
        $periodCount = max(1, (int) ceil($totalMonths / $stepMonths));

        $columns = [];
        for ($index = 0; $index < $periodCount; ++$index) {
            $columns[] = $this->columnNameByIndex($firstPeriodColumnIndex + $index);
        }

        return new TimeAxis(
            stepMonths: $stepMonths,
            periodCount: $periodCount,
            stepLabel: $this->stepLabel($forecastStep),
            periodStartLabel: $this->periodStartLabel($forecastStep),
            periodEndLabel: $this->periodEndLabel($forecastStep),
            columns: $columns,
        );
    }

    private function stepMonths(string $forecastStep): int
    {
        return match ($forecastStep) {
            'quarter' => 3,
            'year' => 12,
            default => 1,
        };
    }

    private function stepLabel(string $forecastStep): string
    {
        return match ($forecastStep) {
            'quarter' => 'квартал',
            'year' => 'год',
            default => 'месяц',
        };
    }

    private function periodStartLabel(string $forecastStep): string
    {
        return match ($forecastStep) {
            'quarter' => 'Начало квартала',
            'year' => 'Начало года',
            default => 'Начало месяца',
        };
    }

    private function periodEndLabel(string $forecastStep): string
    {
        return match ($forecastStep) {
            'quarter' => 'Окончание квартала',
            'year' => 'Окончание года',
            default => 'Окончание месяца',
        };
    }

    private function columnNameByIndex(int $index): string
    {
        if ($index <= 0) {
            throw new \InvalidArgumentException('Индекс колонки должен быть больше нуля.');
        }

        $name = '';
        $current = $index;

        while ($current > 0) {
            $mod = ($current - 1) % 26;
            $name = chr(65 + $mod).$name;
            $current = intdiv($current - $mod - 1, 26);
        }

        return $name;
    }
}
