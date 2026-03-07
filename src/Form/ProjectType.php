<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Project;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Название проекта',
                'attr' => [
                    'class' => 'project-form__input',
                    'placeholder' => 'Например, Retail Forecast',
                    'required' => true,
                    'data-project-form-validation-target' => 'title',
                    'data-action' => 'blur->project-form-validation#validateTitle',
                ],
            ])
            ->add('code', TextType::class, [
                'label' => 'Код проекта',
                'attr' => [
                    'class' => 'project-form__input',
                    'placeholder' => 'RETAIL',
                    'required' => true,
                    'pattern' => '[A-Za-z0-9_-]+',
                    'data-project-form-validation-target' => 'code',
                    'data-action' => 'blur->project-form-validation#validateCode',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Описание',
                'required' => false,
                'attr' => [
                    'class' => 'project-form__textarea',
                    'rows' => 5,
                    'placeholder' => 'Короткое описание проекта',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
        ]);
    }
}
