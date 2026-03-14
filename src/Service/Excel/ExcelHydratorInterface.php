<?php

namespace App\Service\Excel;

interface ExcelHydratorInterface
{
    /**
     * @param array<string, array<string, mixed>> $data
     */
    public function hydrate(string $template, array $data): string;
}
