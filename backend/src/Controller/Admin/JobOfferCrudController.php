<?php

namespace App\Controller\Admin;

use App\Entity\JobOffer;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;


class JobOfferCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return JobOffer::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Job Offer')
            ->setEntityLabelInPlural('Job Offers')
            ->setDefaultSort(['matchScore' => 'DESC', 'createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('title', 'Titre');
        yield TextField::new('company', 'Entreprise');
        yield TextField::new('location', 'Lieu');
        yield TextareaField::new('description')->hideOnIndex();
        yield TextField::new('url', 'URL');
        yield TextField::new('status', 'Statut');
        yield NumberField::new('matchScore', 'Score %')
            ->setNumDecimals(1)
            ->hideOnForm()
            ->setSortable(true);
        yield ArrayField::new('matchedKeywords', 'Mots-clés ✓')
            ->hideOnForm()
            ->hideOnIndex();
        yield ArrayField::new('missingKeywords', 'Mots-clés ✗')
            ->hideOnForm()
            ->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
        yield DateTimeField::new('processedAt', 'Traité le')->hideOnForm();
    }
}