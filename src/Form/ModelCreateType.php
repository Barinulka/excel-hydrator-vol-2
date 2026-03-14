<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ModelCreateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('investmentStartMonth', TextType::class, [
                'label' => 'Дата начала инвестиций',
                'constraints' => [
                    new Assert\NotBlank(message: 'Укажите дату начала инвестиций.'),
                    new Assert\Regex(
                        pattern: '/^\d{4}-\d{2}$/',
                        message: 'Формат даты должен быть YYYY-MM.',
                    ),
                ],
                'attr' => [
                    'class' => 'project-form__input',
                ],
            ])
            ->add('investmentDurationMonths', IntegerType::class, [
                'label' => 'Длительность инвестиций (мес.)',
                'constraints' => [
                    new Assert\NotBlank(message: 'Укажите длительность инвестиций.'),
                    new Assert\Positive(message: 'Длительность инвестиций должна быть больше нуля.'),
                    new Assert\LessThanOrEqual(value: 600, message: 'Длительность инвестиций слишком большая.'),
                ],
                'attr' => [
                    'class' => 'project-form__input',
                    'min' => 1,
                    'max' => 600,
                    'step' => 1,
                ],
            ])
            ->add('commercialOperationDurationMonths', IntegerType::class, [
                'label' => 'Длительность коммерческой работы (мес.)',
                'constraints' => [
                    new Assert\NotBlank(message: 'Укажите длительность коммерческой работы.'),
                    new Assert\Positive(message: 'Длительность коммерческой работы должна быть больше нуля.'),
                    new Assert\LessThanOrEqual(value: 1200, message: 'Длительность коммерческой работы слишком большая.'),
                ],
                'attr' => [
                    'class' => 'project-form__input',
                    'min' => 1,
                    'max' => 1200,
                    'step' => 1,
                ],
            ])
            ->add('forecastStep', ChoiceType::class, [
                'label' => 'Шаг прогнозирования',
                'constraints' => [
                    new Assert\NotBlank(message: 'Выберите шаг прогнозирования.'),
                    new Assert\Choice(
                        choices: ['month', 'quarter', 'year'],
                        message: 'Недопустимый шаг прогнозирования.',
                    ),
                ],
                'choices' => [
                    'мес.' => 'month',
                    'кв.' => 'quarter',
                    'год' => 'year',
                ],
                'attr' => [
                    'class' => 'project-form__input',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
