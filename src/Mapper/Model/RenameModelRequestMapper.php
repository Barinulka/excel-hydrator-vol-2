<?php

namespace App\Mapper\Model;

use App\DTO\Model\Request\RenameModelRequestDTO;

final class RenameModelRequestMapper
{
    public function mapRenameDto(array $data): RenameModelRequestDTO
    {
        $dto = new RenameModelRequestDTO();
        $dto->title = isset($data['title']) ? trim((string) $data['title']) : null;

        return $dto;
    }
}
