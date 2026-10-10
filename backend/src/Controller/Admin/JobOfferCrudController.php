<?php

namespace App\Controller\Admin;

use App\Entity\JobOffer;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
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
            ->setDefaultSort(['createdAt' => 'DESC']);
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
        yield AssociationField::new('enterprise', 'Fiche entreprise');
        yield AssociationField::new('contacts', 'Contacts')->hideOnIndex();
        yield AssociationField::new('jobApplications', 'Candidatures')->hideOnIndex();
        yield TextField::new('location', 'Lieu');
        yield TextareaField::new('description')->hideOnIndex();
        yield TextField::new('url', 'URL');
        yield TextField::new('status', 'Statut');
        yield ChoiceField::new('sourceId', 'Source')->setChoices(JobOffer::SOURCES);
        yield IntegerField::new('minimumSalary', 'Salaire minimum')->hideOnIndex();
        yield ChoiceField::new('minimumSalaryCurrency', 'Devise salaire minimum')->setChoices(JobOffer::CURRENCIES)->hideOnIndex();
        yield IntegerField::new('maximumSalary', 'Salaire maximum')->hideOnIndex();
        yield ChoiceField::new('maximumSalaryCurrency', 'Devise salaire maximum')->setChoices(JobOffer::CURRENCIES)->hideOnIndex();
        yield IntegerField::new('presenceMode', 'Mode de présence');
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
        yield DateTimeField::new('processedAt', 'Traité le')->hideOnForm();
    }
}
