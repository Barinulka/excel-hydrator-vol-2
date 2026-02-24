<?php

namespace App\EasyAdmin\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use Vich\UploaderBundle\Form\Type\VichImageType;

final class VichImageField implements FieldInterface
{
    use FieldTrait;
    public static function new(string $propertyName, ?string $label = 'Image'): self
    {
        return new self()
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(VichImageType::class)
            ->addCssClass('field-vich-image')
            ->setTranslationParameters(['form.label.delete' => 'Удалить этот файл?'])
            ->setTemplatePath('admin\field\vich_image_field.html.twig')
            ;
    }
}
