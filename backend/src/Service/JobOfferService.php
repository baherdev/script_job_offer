<?php

namespace App\Service;

use App\Entity\Entreprise;
use App\Entity\JobOffer;
use App\Repository\JobOfferRepository;
use Doctrine\ORM\EntityManagerInterface;

class JobOfferService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly JobOfferRepository $jobOffers,
    ) {
    }

    public function create(
        Entreprise $entreprise,
        int $sourceId,
        string $originalLink,
        \DateTimeImmutable $publicationDate,
        int $minimumSalary,
        int $maximumSalary,
        string $location,
        int $presenceMode,
        string $positionTitle,
        string $description,
        int $applicationStatus,
        ?\DateTimeImmutable $applicationDate = null,
        ?\DateTimeImmutable $rejectionDate = null,
        ?\DateTimeImmutable $interviewDate1 = null,
        ?\DateTimeImmutable $interviewDate2 = null,
        ?\DateTimeImmutable $interviewDate3 = null,
        ?\DateTimeImmutable $applicationValidationDate = null,
        ?\DateTimeImmutable $cancellationDate = null,
        ?string $coverLetter = null,
        ?int $desiredSalary = null,
        ?\DateTimeImmutable $availabilityDate = null,
    ): JobOffer {
        $jobOffer = $this->apply(
            new JobOffer(),
            $entreprise,
            $sourceId,
            $originalLink,
            $publicationDate,
            $minimumSalary,
            $maximumSalary,
            $location,
            $presenceMode,
            $positionTitle,
            $description,
            $applicationStatus,
            $applicationDate,
            $rejectionDate,
            $interviewDate1,
            $interviewDate2,
            $interviewDate3,
            $applicationValidationDate,
            $cancellationDate,
            $coverLetter,
            $desiredSalary,
            $availabilityDate,
        );

        $this->entityManager->persist($jobOffer);
        $this->entityManager->flush();

        return $jobOffer;
    }

    public function get(int $id): JobOffer
    {
        return $this->jobOffers->find($id)
            ?? throw new \RuntimeException(sprintf('Job offer %d was not found.', $id));
    }

    /**
     * @return list<JobOffer>
     */
    public function findAll(): array
    {
        return $this->jobOffers->findBy([], ['id' => 'ASC']);
    }

    public function update(
        JobOffer $jobOffer,
        Entreprise $entreprise,
        int $sourceId,
        string $originalLink,
        \DateTimeImmutable $publicationDate,
        int $minimumSalary,
        int $maximumSalary,
        string $location,
        int $presenceMode,
        string $positionTitle,
        string $description,
        int $applicationStatus,
        ?\DateTimeImmutable $applicationDate = null,
        ?\DateTimeImmutable $rejectionDate = null,
        ?\DateTimeImmutable $interviewDate1 = null,
        ?\DateTimeImmutable $interviewDate2 = null,
        ?\DateTimeImmutable $interviewDate3 = null,
        ?\DateTimeImmutable $applicationValidationDate = null,
        ?\DateTimeImmutable $cancellationDate = null,
        ?string $coverLetter = null,
        ?int $desiredSalary = null,
        ?\DateTimeImmutable $availabilityDate = null,
    ): JobOffer {
        $this->apply(
            $jobOffer,
            $entreprise,
            $sourceId,
            $originalLink,
            $publicationDate,
            $minimumSalary,
            $maximumSalary,
            $location,
            $presenceMode,
            $positionTitle,
            $description,
            $applicationStatus,
            $applicationDate,
            $rejectionDate,
            $interviewDate1,
            $interviewDate2,
            $interviewDate3,
            $applicationValidationDate,
            $cancellationDate,
            $coverLetter,
            $desiredSalary,
            $availabilityDate,
        );

        $this->entityManager->flush();

        return $jobOffer;
    }

    public function delete(JobOffer $jobOffer): void
    {
        foreach ($jobOffer->getFileReferences()->toArray() as $fileReference) {
            $jobOffer->removeFileReference($fileReference);
        }

        foreach ($jobOffer->getContacts()->toArray() as $contact) {
            $jobOffer->removeContact($contact);
        }

        $jobOffer->getEntreprise()?->removeJobOffer($jobOffer);

        $this->entityManager->remove($jobOffer);
        $this->entityManager->flush();
    }

    private function apply(
        JobOffer $jobOffer,
        Entreprise $entreprise,
        int $sourceId,
        string $originalLink,
        \DateTimeImmutable $publicationDate,
        int $minimumSalary,
        int $maximumSalary,
        string $location,
        int $presenceMode,
        string $positionTitle,
        string $description,
        int $applicationStatus,
        ?\DateTimeImmutable $applicationDate,
        ?\DateTimeImmutable $rejectionDate,
        ?\DateTimeImmutable $interviewDate1,
        ?\DateTimeImmutable $interviewDate2,
        ?\DateTimeImmutable $interviewDate3,
        ?\DateTimeImmutable $applicationValidationDate,
        ?\DateTimeImmutable $cancellationDate,
        ?string $coverLetter,
        ?int $desiredSalary,
        ?\DateTimeImmutable $availabilityDate,
    ): JobOffer {
        return $jobOffer
            ->setEntreprise($entreprise)
            ->setSourceId($sourceId)
            ->setOriginalLink($originalLink)
            ->setPublicationDate($publicationDate)
            ->setMinimumSalary($minimumSalary)
            ->setMaximumSalary($maximumSalary)
            ->setLocation($location)
            ->setPresenceMode($presenceMode)
            ->setPositionTitle($positionTitle)
            ->setDescription($description)
            ->setApplicationStatus($applicationStatus)
            ->setApplicationDate($applicationDate)
            ->setRejectionDate($rejectionDate)
            ->setInterviewDate1($interviewDate1)
            ->setInterviewDate2($interviewDate2)
            ->setInterviewDate3($interviewDate3)
            ->setApplicationValidationDate($applicationValidationDate)
            ->setCancellationDate($cancellationDate)
            ->setCoverLetter($coverLetter)
            ->setDesiredSalary($desiredSalary)
            ->setAvailabilityDate($availabilityDate);
    }
}
