<?php

namespace App\Mapper\Project;

use App\DTO\Project\Request\CreateProjectRequestDTO;
use App\DTO\Project\Request\UpdateProjectRequestDTO;

final class ProjectRequestMapper
{
    public function mapCreateDto(array $data): CreateProjectRequestDTO
    {
        $dto = new CreateProjectRequestDTO();
        $dto->title = $data['title'] ?? null;
        $dto->code = $data['code'] ?? null;
        $dto->description = $data['description'] ?? null;

        return $dto;
    }

    public function mapUpdateDto(array $data): UpdateProjectRequestDTO
    {
        $dto = new UpdateProjectRequestDTO();
        $dto->title = $data['title'] ?? null;
        $dto->code = $data['code'] ?? null;
        $dto->description = $data['description'] ?? null;

        return $dto;
    }
}
