<?php

namespace App\Controller\Admin;

use App\Entity\MonitoredPage;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

class MonitoredPageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return MonitoredPage::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Monitored Page')
            ->setEntityLabelInPlural('Monitored Pages')
            ->setDefaultSort(['url' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield UrlField::new('url', 'URL');
        yield TextField::new('hash', 'Hash');
        yield AssociationField::new('user', 'User');
    }
}
