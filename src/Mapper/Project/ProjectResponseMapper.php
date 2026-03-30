<?php

namespace App\Mapper\Project;

use App\DTO\Project\Response\ProjectResponseDTO;
use App\Entity\Project;

final class ProjectResponseMapper
{
    public function map(Project $project): ProjectResponseDTO
    {
        $projectResponse = new ProjectResponseDTO();
        $projectResponse->shortId = $project->getShortId();
        $projectResponse->title = $project->getTitle();
        $projectResponse->code = $project->getCode();
        $projectResponse->description = $project->getDescription();

        return $projectResponse;
    }
}
