<?php

namespace App\Mapper\Model;

use App\DTO\Model\Request\UpdateTimeParamsRequestDTO;

final class UpdateTimeParamsRequestMapper
{
    public function mapCreateDto(array $data): UpdateTimeParamsRequestDTO
    {
        return $this->mapDto($data);
    }

    public function mapUpdateDto(array $data): UpdateTimeParamsRequestDTO
    {
       return $this->mapDto($data);
    }

    private function mapDto(array $data): UpdateTimeParamsRequestDTO
    {
        $dto = new UpdateTimeParamsRequestDTO();
        $dto->investmentStartMonth = $data['investmentStartMonth'] ?? null;
        $dto->investmentDurationMonths = isset($data['investmentDurationMonths']) ? (int) $data['investmentDurationMonths'] : null;
        $dto->commercialOperationDurationMonths = isset($data['commercialOperationDurationMonths']) ? (int) $data['commercialOperationDurationMonths'] : null;
        $dto->forecastStep = $data['forecastStep'] ?? null;

        return $dto;
    }
}
