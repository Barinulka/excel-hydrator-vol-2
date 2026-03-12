<?php

namespace App\DTO\Projects;

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
