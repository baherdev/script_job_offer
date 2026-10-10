<?php

namespace App\Controller\Admin;

use App\Entity\SearchQuery;
use App\Entity\User;
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

    public function createEntity(string $entityFqcn): SearchQuery
    {
        $searchQuery = new SearchQuery();
        $user = $this->getUser();
        if ($user instanceof User) {
            $searchQuery->setCreatedBy($user);
        }

        return $searchQuery;
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
        yield AssociationField::new('createdBy', 'Créé par')->hideOnForm();
        yield AssociationField::new('interestedUsers', 'Intéressés');
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
    }
}
