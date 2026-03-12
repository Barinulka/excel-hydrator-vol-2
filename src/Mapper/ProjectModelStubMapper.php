<?php

namespace App\Mapper;

use App\DTO\Projects\ProjectModelStubDTO;
use App\Entity\Project;

final class ProjectModelStubMapper
{
    /**
     * @return list<ProjectModelStubDTO>
     */
    public function mapForProject(Project $project): array
    {
        $projectTitle = $project->getTitle() ?? 'Проект';

        return [
            new ProjectModelStubDTO(
                title: sprintf('%s / Базовый сценарий', $projectTitle),
                description: 'Заглушка модели: здесь позже появятся реальные параметры и показатели.',
            ),
            new ProjectModelStubDTO(
                title: sprintf('%s / Оптимистичный сценарий', $projectTitle),
                description: 'Заглушка модели: карточка для демонстрации переключения по проектам.',
            ),
        ];
    }
}

