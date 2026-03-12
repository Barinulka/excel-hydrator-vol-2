<?php

namespace App\DTO\Projects;

final readonly class ProjectContentDTO
{
    /**
     * @param list<ProjectModelStubDTO> $modelStubs
     */
    public function __construct(
        public string $shortId,
        public string $title,
        public string $code,
        public ?string $description,
        public array $modelStubs = [],
        public bool $hasModels = false,
    ) {
    }
}
