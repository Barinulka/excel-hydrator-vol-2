<?php

namespace App\DTO\Project;

final readonly class ProjectSidebarItemDTO
{
    public function __construct(
        public string $shortId,
        public string $title,
        public string $code,
        public bool $isActive
    ) {
    }
}
