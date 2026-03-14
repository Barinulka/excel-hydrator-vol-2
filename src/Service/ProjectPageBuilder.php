<?php

namespace App\Service;

use App\DTO\Projects\ProjectPageDTO;
use App\Entity\Project;
use App\Entity\User;
use App\Mapper\ProjectPageMapper;

final class ProjectPageBuilder
{
    public function __construct(
        private ProjectService $projectService,
        private ProjectPageMapper $projectPageMapper,
    ) {
    }

    public function build(User $user, ?Project $selectedProject = null, string $projectView = 'list'): ProjectPageDTO
    {
        $projects = $this->projectService->getUserProjects($user);
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
            foreach ($projects as $project) {
                if ($project->getId() === $selectedProject->getId()) {
                    return $project;
                }
            }
        }

        return $projects[0];
    }
}
