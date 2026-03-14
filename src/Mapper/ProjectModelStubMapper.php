<?php

namespace App\Mapper;

use App\DTO\Projects\ProjectModelStubDTO;
use App\Entity\Model;
use App\Entity\Project;

final class ProjectModelStubMapper
{
    /**
     * @return list<ProjectModelStubDTO>
     */
    public function mapForProject(Project $project): array
    {
        $models = $project->getModels()->toArray();

        usort($models, static function (Model $a, Model $b): int {
            return ($b->getVersionNumber() ?? 0) <=> ($a->getVersionNumber() ?? 0);
        });

        return array_map(
            static fn (Model $model): ProjectModelStubDTO => new ProjectModelStubDTO(
                title: $model->getTitle() ?? sprintf('Модель %s', $model->getVersionLabel()),
                description: sprintf('Версия: %s.', $model->getVersionLabel()),
                shortId: $model->getShortId() ?? '',
            ),
            $models
        );
    }
}
