<?php

namespace App\Service\Excel;

use App\Entity\Model;
use App\Exception\Excel\ExcelValidationException;
use App\Service\Excel\ValueObject\TimeAxis;
use App\Service\ModelService;

final class ModelExcelPayloadBuilder
{
    private const FIRST_PERIOD_COLUMN_INDEX = 5; // E
    private const EXPORT_FORECAST_STEP = 'month';

    private const STYLE_INPUT = 'input';
    private const STYLE_CALCULATED = 'calculated';
    private const STYLE_REFERENCE = 'reference';
    private const STYLE_TECHNICAL = 'technical';
    private const STYLE_HINT = 'hint';

    private const INPUT_SHEET = 'Входные данные';
    private const TIMELINE_SHEET = 'Временные параметры';

    private const INPUT_START_DATE_CELL = 'D4';
    private const INPUT_INVESTMENT_DURATION_CELL = 'D5';
    private const INPUT_COMMERCIAL_DURATION_CELL = 'D6';
    private const INPUT_END_INVESTMENT_CELL = 'D9';
    private const INPUT_START_COMMERCIAL_CELL = 'D10';
    private const INPUT_END_COMMERCIAL_CELL = 'D11';

    private const TIMELINE_START_INVESTMENT_CELL = 'D7';
    private const TIMELINE_END_INVESTMENT_CELL = 'D8';
    private const TIMELINE_START_COMMERCIAL_CELL = 'D9';
    private const TIMELINE_END_COMMERCIAL_CELL = 'D10';

    public function __construct(
        private ModelService $modelService,
        private TimeAxisBuilder $timeAxisBuilder,
    ) {
    }

    /**
     * @return array{template: string, sheetData: array<string, array<string, mixed>>}
     */
    public function buildRequest(Model $model): array
    {
        $timeParams = $this->modelService->getTimeParamsFormData($model);

        $investmentStartMonth = (string) ($timeParams['investmentStartMonth'] ?? '');
        $startDate = \DateTimeImmutable::createFromFormat('!Y-m', $investmentStartMonth);
        if (!$startDate instanceof \DateTimeImmutable) {
            throw new ExcelValidationException('Невалидная дата начала инвестиций для выгрузки Excel.');
        }

        $investmentDurationMonths = (int) ($timeParams['investmentDurationMonths'] ?? 0);
        $commercialOperationDurationMonths = (int) ($timeParams['commercialOperationDurationMonths'] ?? 0);
        $timeAxis = $this->timeAxisBuilder->build(
            // Quarter/year export will be enabled later.
            forecastStep: self::EXPORT_FORECAST_STEP,
            investmentDurationMonths: $investmentDurationMonths,
            commercialOperationDurationMonths: $commercialOperationDurationMonths,
            firstPeriodColumnIndex: self::FIRST_PERIOD_COLUMN_INDEX,
        );

        $inputSheet = $this->buildInputSheet(
            startDate: $startDate,
            investmentDurationMonths: $investmentDurationMonths,
            commercialOperationDurationMonths: $commercialOperationDurationMonths,
            timeAxis: $timeAxis,
        );

        $timelineSheet = $this->buildTimelineSheet($timeAxis);
        $this->appendTimelinePeriodSeries($timelineSheet, $timeAxis);

        return [
            'template' => '',
            'sheetData' => [
                self::INPUT_SHEET => $inputSheet,
                self::TIMELINE_SHEET => $timelineSheet,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildInputSheet(
        \DateTimeImmutable $startDate,
        int $investmentDurationMonths,
        int $commercialOperationDurationMonths,
        TimeAxis $timeAxis,
    ): array {
        return [
            'C2' => $this->technicalValue('ед измерения'),
            'A4' => 'Дата начала инвестиций',
            'C4' => 'дата',
            self::INPUT_START_DATE_CELL => $this->dateValue($startDate, self::STYLE_INPUT),
            'A5' => 'Длительность инвестиций',
            'C5' => 'мес.',
            self::INPUT_INVESTMENT_DURATION_CELL => $this->inputValue($investmentDurationMonths),
            'A6' => 'Длительность коммерческой работы',
            'C6' => 'мес.',
            self::INPUT_COMMERCIAL_DURATION_CELL => $this->inputValue($commercialOperationDurationMonths),
            'A7' => 'Шаг прогнозирования',
            'C7' => $this->hintValue('выбор'),
            'D7' => $this->dropDownValue($timeAxis->stepLabel, ['месяц', 'квартал', 'год']),
            'A9' => 'Дата окончания инвестиционной фазы',
            'C9' => 'дата',
            self::INPUT_END_INVESTMENT_CELL => $this->dateFormula(sprintf('=EOMONTH(%s,%s-1)', self::INPUT_START_DATE_CELL, self::INPUT_INVESTMENT_DURATION_CELL)),
            'A10' => 'Дата начала коммерской эксплуатации',
            'C10' => 'дата',
            self::INPUT_START_COMMERCIAL_CELL => $this->dateFormula(sprintf('=%s+1', self::INPUT_END_INVESTMENT_CELL)),
            'A11' => 'Дата окончания коммерческой фазы',
            'C11' => 'дата',
            self::INPUT_END_COMMERCIAL_CELL => $this->dateFormula(sprintf('=EOMONTH(%s,%s-1)', self::INPUT_START_COMMERCIAL_CELL, self::INPUT_COMMERCIAL_DURATION_CELL)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildTimelineSheet(TimeAxis $timeAxis): array
    {
        return [
            'C2' => $this->technicalValue('ед.измернения'),
            'A4' => $timeAxis->periodStartLabel,
            'A5' => $timeAxis->periodEndLabel,
            'A7' => 'Дата начала инвестиций',
            'C7' => 'дата',
            self::TIMELINE_START_INVESTMENT_CELL => $this->dateFormula(
                sprintf("='%s'!%s", self::INPUT_SHEET, self::INPUT_START_DATE_CELL),
                self::STYLE_REFERENCE,
            ),
            'A8' => 'Дата окончания инвестиционной фазы',
            'C8' => 'дата',
            self::TIMELINE_END_INVESTMENT_CELL => $this->dateFormula(
                sprintf("='%s'!%s", self::INPUT_SHEET, self::INPUT_END_INVESTMENT_CELL),
                self::STYLE_REFERENCE,
            ),
            'A9' => 'Дата начала коммерской эксплуатации',
            'C9' => 'дата',
            self::TIMELINE_START_COMMERCIAL_CELL => $this->dateFormula(
                sprintf("='%s'!%s", self::INPUT_SHEET, self::INPUT_START_COMMERCIAL_CELL),
                self::STYLE_REFERENCE,
            ),
            'A10' => 'Дата окончания коммерческой фазы',
            'C10' => 'дата',
            self::TIMELINE_END_COMMERCIAL_CELL => $this->dateFormula(
                sprintf("='%s'!%s", self::INPUT_SHEET, self::INPUT_END_COMMERCIAL_CELL),
                self::STYLE_REFERENCE,
            ),
            'A13' => 'Флаг инвестиционной деятельности',
            'C13' => 'флаг',
            'A14' => 'Флаг операционной деятельности',
            'C14' => 'флаг',
            'A15' => 'Флаг начала операционной деятельности',
            'C15' => 'флаг',
        ];
    }

    /**
     * @param array<string, mixed> $timelineSheet
     */
    private function appendTimelinePeriodSeries(array &$timelineSheet, TimeAxis $timeAxis): void
    {
        foreach ($timeAxis->columns as $index => $column) {
            $previousColumn = $timeAxis->columns[$index - 1] ?? null;

            $timelineSheet[sprintf('%s4', $column)] = 0 === $index
                ? $this->dateFormula(sprintf("='%s'!%s", self::INPUT_SHEET, self::INPUT_START_DATE_CELL), self::STYLE_REFERENCE)
                : $this->dateFormula(sprintf('=%s5+1', $previousColumn), self::STYLE_CALCULATED);

            $timelineSheet[sprintf('%s5', $column)] = $this->dateFormula(sprintf('=EOMONTH(%s4,%d)', $column, $timeAxis->stepMonths - 1));
            $timelineSheet[sprintf('%s13', $column)] = $this->formula(sprintf('=IF(AND((%1$s4>=$D$7),(%1$s5<=$D$8)),1,0)', $column));
            $timelineSheet[sprintf('%s14', $column)] = $this->formula(sprintf('=IF(%s4>=$D$9,1,0)', $column));
            $timelineSheet[sprintf('%s15', $column)] = $this->formula(sprintf('=IF(%s4=$D$9,1,0)', $column));
        }
    }

    /**
     * @return array{formula: string, cell_style: string}
     */
    private function formula(string $formula, string $cellStyle = self::STYLE_CALCULATED): array
    {
        return [
            'formula' => $formula,
            'cell_style' => $cellStyle,
        ];
    }

    /**
     * @return array{formula: string, num_format: string, cell_style: string}
     */
    private function dateFormula(string $formula, string $cellStyle = self::STYLE_CALCULATED): array
    {
        return [
            'formula' => $formula,
            'num_format' => 'dd.mm.yyyy',
            'cell_style' => $cellStyle,
        ];
    }

    /**
     * @return array{value: int, num_format: string, cell_style: string}
     */
    private function dateValue(\DateTimeImmutable $date, string $cellStyle = self::STYLE_INPUT): array
    {
        $excelEpoch = new \DateTimeImmutable('1899-12-30');

        return [
            'value' => (int) $excelEpoch->diff($date)->format('%a'),
            'num_format' => 'dd.mm.yyyy',
            'cell_style' => $cellStyle,
        ];
    }

    /**
     * @param list<string> $options
     *
     * @return array{value: string, data_validation: array{type: string, options: list<string>}, cell_style: string}
     */
    private function dropDownValue(string $value, array $options): array
    {
        return [
            'value' => $value,
            'data_validation' => [
                'type' => 'list',
                'options' => $options,
            ],
            'cell_style' => self::STYLE_INPUT,
        ];
    }

    /**
     * @return array{value: int, cell_style: string}
     */
    private function inputValue(int $value): array
    {
        return [
            'value' => $value,
            'cell_style' => self::STYLE_INPUT,
        ];
    }

    /**
     * @return array{value: string, cell_style: string}
     */
    private function technicalValue(string $value): array
    {
        return [
            'value' => $value,
            'cell_style' => self::STYLE_TECHNICAL,
        ];
    }

    /**
     * @return array{value: string, cell_style: string}
     */
    private function hintValue(string $value): array
    {
        return [
            'value' => $value,
            'cell_style' => self::STYLE_HINT,
        ];
    }
}
