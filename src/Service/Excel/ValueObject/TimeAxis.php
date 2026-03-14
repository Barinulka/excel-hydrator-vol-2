<?php

namespace App\Service\Excel\ValueObject;

final readonly class TimeAxis
{
    /**
     * @param list<string> $columns
     */
    public function __construct(
        public int $stepMonths,
        public int $periodCount,
        public string $stepLabel,
        public string $periodStartLabel,
        public string $periodEndLabel,
        public array $columns,
    ) {
    }
}
