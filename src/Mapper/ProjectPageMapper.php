<?php

namespace App\Mapper;

use App\DTO\Projects\ProjectPageDTO;
use App\Entity\Project;

final class ProjectPageMapper
{
    public function __construct(
        private ProjectSidebarItemMapper $projectSidebarItemMapper,
        private ProjectContentMapper $projectContentMapper,
    ) {
    }

    /**
     * @param list<Project> $projects
     */
    public function mapPage(array $projects, ?Project $selectedProject, string $projectView = 'list'): ProjectPageDTO
    {
        $selectedProjectId = $selectedProject?->getId();

        return new ProjectPageDTO(
            sidebarItems: $this->projectSidebarItemMapper->mapSidebarItems($projects, $selectedProjectId),
            selectedProject: $this->projectContentMapper->map($selectedProject),
            projectView: $projectView
        );
    }
}
