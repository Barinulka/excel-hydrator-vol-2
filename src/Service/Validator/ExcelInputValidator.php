<?php

namespace App\Service\Validator;

use App\DTO\ExcelInputData;
use App\Exception\Excel\ExcelValidationException;

final class ExcelInputValidator
{
    public function validate(ExcelInputData $data): void
    {
        if ([] === $data->sheetData) {
            throw new ExcelValidationException('Data is required.');
        }

        foreach ($data->sheetData as $sheetName => $cells) {
            if (!is_string($sheetName) || '' === trim($sheetName)) {
                throw new ExcelValidationException('Sheet name must be a non-empty string.');
            }

            if (!is_array($cells)) {
                throw new ExcelValidationException(sprintf('Sheet "%s" data must be an object of cell/value pairs.', $sheetName));
            }

            foreach (array_keys($cells) as $cell) {
                if (!is_string($cell) || 1 !== preg_match('/^[A-Z]+[0-9]+$/i', $cell)) {
                    throw new ExcelValidationException(sprintf('Invalid cell reference: %s', (string) $cell));
                }
            }
        }
    }
}
