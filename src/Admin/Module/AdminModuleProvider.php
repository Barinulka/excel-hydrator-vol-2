<?php

namespace App\Admin\Module;

use App\Controller\Admin\MainCrudController;
use App\Controller\Admin\UserCrudController;
use App\Repository\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

final readonly class  AdminModuleProvider
{
    const int SINGLE_PAGE_ENTITY_ID = 1;

    public function __construct(
        private AdminUrlGenerator $adminUrlGenerator,
        private UserRepository $userRepository,
    ) {
    }

    public function getDashboardModules(): array
    {
        return [
            [
                'title' => 'Пользователи',
                'description' => 'Список пользователей и их роли в системе.',
                'icon' => 'fa fa-users',
                'count' => $this->userRepository->count([]),
                'url' => (clone $this->adminUrlGenerator)
                    ->setController(UserCrudController::class)
                    ->setAction(Action::INDEX)
                    ->generateUrl(),
            ],
            [
                'title' => 'Главная страница',
                'description' => 'Контент для блока главной страницы сайта.',
                'icon' => 'fa fa-house',
                'count' => null,
                'url' => (clone $this->adminUrlGenerator)
                    ->setController(MainCrudController::class)
                    ->setAction(Action::EDIT)
                    ->setEntityId(self::SINGLE_PAGE_ENTITY_ID)
                    ->generateUrl(),
            ],
        ];
    }
}
