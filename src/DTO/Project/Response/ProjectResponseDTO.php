<?php

namespace App\DTO\Project\Response;

final class ProjectResponseDTO
{
    public string $shortId;
    public string $title;
    public ?string $code = null;
    public ?string $description = null;
}
