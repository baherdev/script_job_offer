<?php

namespace App\Controller\Admin;

use App\Entity\Enterprise;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class EnterpriseCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Enterprise::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Enterprise')
            ->setEntityLabelInPlural('Enterprises')
            ->setDefaultSort(['label' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('label', 'Libellé');
        yield TextField::new('website', 'Site web');
        yield TextField::new('street', 'Rue');
        yield TextField::new('zipCode', 'Code postal');
        yield TextField::new('city', 'Ville');
        yield TextField::new('country', 'Pays');
    }
}
