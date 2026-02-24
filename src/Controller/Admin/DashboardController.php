<?php

namespace App\Controller\Admin;

use App\Admin\Module\AdminModuleProvider;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    const int SINGLE_PAGE_ENTITY_ID = 1;

    public function __construct(
        private readonly AdminModuleProvider $adminModuleProvider,
    ) {
    }

    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'modules' => $this->adminModuleProvider->getDashboardModules(),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Excel Hydrator Vol 2')
            ->renderContentMaximized();
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addCssFile('styles/admin/dashboard.css');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToUrl('На сайт', 'fa fa-globe', '/')->setLinkTarget('_blank');
        yield MenuItem::section('&nbsp;');
        yield MenuItem::linkToDashboard('Рабочий стол', 'fa fa-tachometer');
        yield MenuItem::linkTo(MainCrudController::class, 'Главная страница', 'fa fa-house')
            ->setAction(Action::EDIT)
            ->setEntityId(self::SINGLE_PAGE_ENTITY_ID);
        // yield MenuItem::linkToCrud('The Label', 'fas fa-list', EntityClass::class);
    }
}
