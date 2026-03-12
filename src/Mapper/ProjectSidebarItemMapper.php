<?php

namespace App\Mapper;

use App\DTO\Projects\ProjectSidebarItemDTO;
use App\Entity\Project;

final class ProjectSidebarItemMapper
{
    /**
     * @param list<Project> $projects
     * @return list<ProjectSidebarItemDTO>
     */
    public function mapSidebarItems(array $projects, ?int $selectedProjectId = null): array
    {
        return array_map(
            fn (Project $project): ProjectSidebarItemDTO => $this->toDto($project, $selectedProjectId),
            $projects
        );
    }

    private function toDto(Project $p, ?int $selectedId): ProjectSidebarItemDTO
    {
        return new ProjectSidebarItemDTO(
            shortId: $p->getShortId() ?? '',
            title: $p->getTitle() ?? 'Без названия',
            code: $p->getCode() ?? 'NO CODE',
            isActive: null !== $selectedId && $p->getId() === $selectedId
        );
    }
}
