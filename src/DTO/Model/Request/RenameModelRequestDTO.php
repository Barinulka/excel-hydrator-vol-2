<?php

namespace App\DTO\Model\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class RenameModelRequestDTO
{
    #[Assert\NotBlank(message: 'Название модели не может быть пустым.')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Название модели не должно превышать {{ limit }} символов.'
    )]
    public ?string $title = null;
}
