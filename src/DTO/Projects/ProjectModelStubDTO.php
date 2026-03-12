<?php

namespace App\DTO\Projects;

final readonly class ProjectModelStubDTO
{
    public function __construct(
        public string $title,
        public string $description,
    ) {
    }
}
