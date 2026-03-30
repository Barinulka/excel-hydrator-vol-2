<?php

namespace App\Service\Project;

use App\DTO\Project\ProjectPageDTO;
use App\Entity\Project;
use App\Mapper\ProjectPageMapper;

final class ProjectPageBuilder
{
    public function __construct(
        private ProjectPageMapper $projectPageMapper,
    ) {
    }

    /**
     * @param list<Project> $projects
     */
    public function build(array $projects, ?Project $selectedProject = null, string $projectView = 'list'): ProjectPageDTO
    {
        $selectedProjectInList = $this->resolveSelectedProject($projects, $selectedProject);

        return $this->projectPageMapper->mapPage($projects, $selectedProjectInList, $projectView);
    }

    /**
     * @param list<Project> $projects
     */
    private function resolveSelectedProject(array $projects, ?Project $selectedProject = null): ?Project
    {
        if ([] === $projects) {
            return null;
        }

        if (null !== $selectedProject) {
            return $selectedProject;
        }

        return $projects[0];
    }
}
