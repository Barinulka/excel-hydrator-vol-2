<?php

namespace App\DTO;

final readonly class GoHydrationDTO
{
    /**
     * @param array<string, array<string, mixed>> $data
     */
    public function __construct(
        public string $template,
        public array $data,
    ) {
    }
}
