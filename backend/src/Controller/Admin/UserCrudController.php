<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('User')
            ->setEntityLabelInPlural('Users')
            ->setDefaultSort(['email' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield EmailField::new('email', 'Email');
        yield ChoiceField::new('roles', 'Rôles')
            ->setChoices([
                'User' => 'ROLE_USER',
                'Admin' => 'ROLE_ADMIN',
            ])
            ->allowMultipleChoices()
            ->renderExpanded();
        yield TextField::new('password', 'Mot de passe')
            ->setFormType(PasswordType::class)
            ->setRequired($pageName === Crud::PAGE_NEW)
            ->onlyOnForms()
            ->setHelp($pageName === Crud::PAGE_EDIT ? 'Laisser vide pour conserver le mot de passe actuel.' : '')
            ->setFormTypeOption('attr', ['autocomplete' => 'new-password']);
        yield AssociationField::new('searchQueries', 'Recherches')->hideOnForm();
        yield AssociationField::new('jobApplications', 'Candidatures')->hideOnForm();
    }

    public function createNewFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        return $this->hashPasswordOnSubmit(parent::createNewFormBuilder($entityDto, $formOptions, $context));
    }

    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $builder = parent::createEditFormBuilder($entityDto, $formOptions, $context);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            $user = $event->getForm()->getData();
            if (!is_array($data) || ($data['password'] ?? '') !== '' || !$user instanceof User) {
                return;
            }

            $data['password'] = $user->getPassword();
            $event->setData($data);
        });

        return $this->hashPasswordOnSubmit($builder);
    }

    private function hashPasswordOnSubmit(FormBuilderInterface $builder): FormBuilderInterface
    {
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            $user = $event->getData();
            if (!$user instanceof User) {
                return;
            }

            $plainPassword = $user->getPassword();
            if ($plainPassword === null || $plainPassword === '' || str_starts_with($plainPassword, '$')) {
                return;
            }

            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        });

        return $builder;
    }
}
