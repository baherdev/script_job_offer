<?php

namespace App\Controller\Admin;

use App\Entity\FileReference;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FileField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileReferenceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FileReference::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('File Reference')
            ->setEntityLabelInPlural('File References')
            ->setDefaultSort(['creationDate' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('fileName', 'Nom du fichier')->hideOnForm();
        yield TextField::new('filePath', 'Chemin')->hideOnForm();
        yield DateField::new('creationDate', 'Date de création')->hideOnForm();
        yield FileField::new('filePath', 'Fichier')
            ->setUploadDir('public')
            ->setUploadedFileNamePattern(function (UploadedFile $file, FileReference $fileReference): string {
                $originalName = basename(str_replace('\\', '/', $file->getClientOriginalName()));
                $fileReference->setFileName(mb_substr($originalName !== '' ? $originalName : 'file', 0, 255));

                $extension = strtolower((string) $file->getClientOriginalExtension());
                $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?: 'bin';

                return sprintf('uploads/file-references/%s.%s', bin2hex(random_bytes(16)), $extension);
            })
            ->setRequired($pageName === Crud::PAGE_NEW)
            ->isDeletable(false)
            ->hideOnIndex();
    }
}
