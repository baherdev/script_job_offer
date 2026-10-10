<?php

namespace App\Controller\Admin;

use App\Entity\JobApplication;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

class JobApplicationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return JobApplication::class;
    }

    public function createEntity(string $entityFqcn): JobApplication
    {
        $jobApplication = new JobApplication();
        $user = $this->getUser();
        if ($user instanceof User) {
            $jobApplication->setUser($user);
        }

        return $jobApplication;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Job Application')
            ->setEntityLabelInPlural('Job Applications')
            ->setDefaultSort(['applicationDate' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('jobOffer', 'Job offer');
        yield AssociationField::new('user', 'User');
        yield ChoiceField::new('applicationStatus', 'Application status')->setChoices(JobApplication::STATUSES);
        yield DateField::new('applicationDate', 'Application date');
        yield DateField::new('interviewDate1', 'Interview date 1')->hideOnIndex();
        yield DateField::new('interviewDate2', 'Interview date 2')->hideOnIndex();
        yield DateField::new('interviewDate3', 'Interview date 3')->hideOnIndex();
        yield DateField::new('rejectionDate', 'Rejection date')->hideOnIndex();
        yield DateField::new('cancellationDate', 'Cancellation date')->hideOnIndex();
        yield DateField::new('firstStartDate', 'Date of first start')->hideOnIndex();
        yield IntegerField::new('desiredSalaryAmount', 'Desired salary amount')->hideOnIndex();
        yield ChoiceField::new('desiredSalaryCurrency', 'Desired salary currency')->setChoices(JobApplication::CURRENCIES);
        yield AssociationField::new('coverLetter', 'Cover letter')->hideOnIndex();
        yield AssociationField::new('cv', 'CV')->hideOnIndex();
        yield AssociationField::new('referenceFiles', 'References')->hideOnIndex();
        yield AssociationField::new('diplomas', 'Diplomas')->hideOnIndex();
        yield AssociationField::new('additionalFiles', 'Additional files')->hideOnIndex();
    }
}
