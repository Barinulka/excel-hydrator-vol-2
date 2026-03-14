<?php

namespace App\Mapper;

use App\DTO\Projects\ProjectContentDTO;
use App\Entity\Project;

final class ProjectContentMapper
{
    public function __construct(
        private ProjectModelStubMapper $projectModelStubMapper,
    ) {
    }

    public function map(?Project $project): ?ProjectContentDTO
    {
        if (null === $project) {
            return null;
        }

        $modelStubs = $this->projectModelStubMapper->mapForProject($project);

        return new ProjectContentDTO(
            shortId: $project->getShortId() ?? '',
            title: $project->getTitle() ?? 'Без названия',
            code: $project->getCode() ?? 'NO CODE',
            description: $project->getDescription(),
            modelStubs: $modelStubs,
            hasModels: [] !== $modelStubs,
        );
    }
}
