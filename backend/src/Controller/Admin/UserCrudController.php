<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\TextAlign;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
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
            ->setDefaultSort(['email' => 'ASC'])
            ->setDefaultRowAction(Action::DETAIL);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, static function (Action $action): Action {
                return $action->setLabel('View')->asTextLink();
            })
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, Action::EDIT, Action::DELETE]);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('email', 'Email')
            ->setFormType(EmailType::class);
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
        $blackListedEnterprises = AssociationField::new('blackListedEnterprises', 'Black Listed Enterprises')->hideOnIndex();
        if ($pageName === Crud::PAGE_DETAIL) {
            $blackListedEnterprises->setTemplatePath('admin/field/black_listed_enterprises.html.twig');
        }
        yield $blackListedEnterprises;
        $searchQueries = AssociationField::new('searchQueries', 'Recherches')
            ->hideOnForm()
            ->setTextAlign(TextAlign::LEFT);
        if (in_array($pageName, [Crud::PAGE_INDEX, Crud::PAGE_DETAIL], true)) {
            $searchQueries
                ->setTemplatePath('admin/field/search_queries.html.twig')
                ->formatValue(static function (mixed $value, User $user): array {
                    $queries = [];
                    foreach ([...$user->getSearchQueries(), ...$user->getInterestedSearchQueries()] as $searchQuery) {
                        $queries[$searchQuery->getId() ?? spl_object_id($searchQuery)] = $searchQuery;
                    }

                    return array_values($queries);
                });
        }
        yield $searchQueries;
        $jobApplications = AssociationField::new('jobApplications', 'Candidatures')->hideOnForm()->hideOnIndex();
        if ($pageName === Crud::PAGE_DETAIL) {
            $jobApplications->setTemplatePath('admin/field/job_applications.html.twig');
        }
        yield $jobApplications;
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
