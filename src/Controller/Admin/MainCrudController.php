<?php

namespace App\Controller\Admin;

use App\EasyAdmin\Field\CKEditorField;
use App\EasyAdmin\Field\VichImageField;
use App\Entity\Main;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class MainCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Main::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Главная страница')
            ->setEntityLabelInPlural('Главная страница')
            ->setPageTitle(Crud::PAGE_EDIT, 'Редактировать главную страницу')
            ->setSearchFields(null)
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $actionObj = $actions;
        $actionObj->remove(Crud::PAGE_INDEX, Action::DELETE);
        $actionObj->remove(Crud::PAGE_INDEX, Action::NEW);

        $actionObj->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE, Action::SAVE_AND_RETURN);
        $actionObj->update(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE, function (Action $action) {
            return $action->setIcon('fa fa-check')->setLabel('Сохранить и продолжить редактирование');
        });

        return $actionObj;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('title', 'Заголовок');
        yield CKEditorField::new('description')
            ->setLabel('Описание')
            ->hideOnIndex();
        yield VichImageField::new('imageFile')
            ->setLabel('Изображение')
            ->hideOnIndex()
            ->setHelp('Формат картинок webp/jpeg/png');
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Main
            && null === $entityInstance->getId()
            && $entityManager->getRepository(Main::class)->count([]) > 0
        ) {
            throw new \LogicException('Для сущности "Главная страница" разрешена только одна запись.');
        }

        parent::persistEntity($entityManager, $entityInstance);
    }
}
