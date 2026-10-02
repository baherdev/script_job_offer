<?php

namespace App\Service;

use App\Entity\FileReference;
use App\Repository\FileReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;

class FileReferenceService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FileReferenceRepository $fileReferences,
    ) {
    }

    public function create(int $fileType, string $fileName, string $fileVersion, string $filePath, bool $isActif = true): FileReference
    {
        $fileReference = (new FileReference())
            ->setFileType($fileType)
            ->setFileName($fileName)
            ->setFileVersion($fileVersion)
            ->setFilePath($filePath)
            ->setIsActif($isActif);

        $this->entityManager->persist($fileReference);
        $this->entityManager->flush();

        return $fileReference;
    }

    public function get(int $id): FileReference
    {
        return $this->fileReferences->find($id)
            ?? throw new \RuntimeException(sprintf('File reference %d was not found.', $id));
    }

    /**
     * @return list<FileReference>
     */
    public function findAll(): array
    {
        return $this->fileReferences->findBy([], ['id' => 'ASC']);
    }

    public function update(FileReference $fileReference, int $fileType, string $fileName, string $fileVersion, string $filePath, bool $isActif): FileReference
    {
        $fileReference
            ->setFileType($fileType)
            ->setFileName($fileName)
            ->setFileVersion($fileVersion)
            ->setFilePath($filePath)
            ->setIsActif($isActif);

        $this->entityManager->flush();

        return $fileReference;
    }

    public function delete(FileReference $fileReference): void
    {
        foreach ($fileReference->getJobOffers()->toArray() as $jobOffer) {
            $fileReference->removeJobOffer($jobOffer);
        }

        $this->entityManager->remove($fileReference);
        $this->entityManager->flush();
    }
}
