<?php

namespace App\DTO\Project;

final readonly class ProjectPageDTO
{
    /**
     * @param list<ProjectSidebarItemDTO> $sidebarItems
     */
    public function __construct(
        public array $sidebarItems,
        public ?ProjectContentDTO $selectedProject,
        public string $projectView = 'list',
    ) {
    }
}
