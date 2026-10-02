<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class JobOfferInput
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Type('integer')]
        public ?int $entrepriseId = null,
        #[Assert\NotNull]
        #[Assert\Type('integer')]
        public ?int $sourceId = null,
        #[Assert\NotBlank]
        public ?string $originalLink = null,
        #[Assert\NotNull]
        public ?\DateTimeImmutable $publicationDate = null,
        #[Assert\NotNull]
        #[Assert\Type('integer')]
        public ?int $minimumSalary = null,
        #[Assert\NotNull]
        #[Assert\Type('integer')]
        public ?int $maximumSalary = null,
        #[Assert\NotBlank]
        public ?string $location = null,
        #[Assert\NotNull]
        #[Assert\Type('integer')]
        public ?int $presenceMode = null,
        #[Assert\NotBlank]
        public ?string $positionTitle = null,
        #[Assert\NotBlank]
        public ?string $description = null,
        #[Assert\NotNull]
        #[Assert\Type('integer')]
        public ?int $applicationStatus = null,
        public ?\DateTimeImmutable $applicationDate = null,
        public ?\DateTimeImmutable $rejectionDate = null,
        public ?\DateTimeImmutable $interviewDate1 = null,
        public ?\DateTimeImmutable $interviewDate2 = null,
        public ?\DateTimeImmutable $interviewDate3 = null,
        public ?\DateTimeImmutable $applicationValidationDate = null,
        public ?\DateTimeImmutable $cancellationDate = null,
        public ?string $coverLetter = null,
        #[Assert\Type('integer')]
        public ?int $desiredSalary = null,
        public ?\DateTimeImmutable $availabilityDate = null,
    ) {
    }
}
