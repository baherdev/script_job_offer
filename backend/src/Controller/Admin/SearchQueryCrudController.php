<?php

namespace App\Controller\Admin;

use App\Entity\SearchQuery;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class SearchQueryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SearchQuery::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Search Query')
            ->setEntityLabelInPlural('Search Queries')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('keyword', 'Mot-clé');
        yield TextField::new('location', 'Lieu');
        yield IntegerField::new('distance', 'Rayon (miles)');
        yield BooleanField::new('isActive', 'Actif');
        yield AssociationField::new('user', 'Utilisateur')->hideOnForm();
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
    }
}
