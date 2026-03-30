<?php

namespace App\DTO\Project\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateProjectRequestDTO
{
    #[Assert\NotBlank(message: 'Введите заголовок')]
    public ?string $title = null;
    #[Assert\Regex(
        pattern: '/^[A-Za-z0-9_-]+$/',
        message: 'Код может содержать только латинские буквы, цифры, "_" и "-".'
    )]
    #[Assert\Length(
        max: 100,
        maxMessage: 'Код проекта не должен быть длиннее {{ limit }} символов.'
    )]
    public ?string $code = null;
    #[Assert\Length(
        max: 500,
        maxMessage: 'Описание проекта не должно быть длиннее {{ limit }} символов.'
    )]
    public ?string $description = null;
}
