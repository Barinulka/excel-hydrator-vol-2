<?php

namespace App\DTO\Project;

final readonly class ProjectModelStubDTO
{
    public function __construct(
        public string $title,
        public string $description,
        public string $shortId,
    ) {
    }
}
