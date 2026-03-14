<?php

namespace App\DTO;

final readonly class ExcelInputData
{
    /**
     * @param array<string, array<string, mixed>> $sheetData
     */
    public function __construct(
        public array $sheetData,
    ) {
    }
}
