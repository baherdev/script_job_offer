<?php

namespace App\Entity;

use App\Repository\JobOfferRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobOfferRepository::class)]
class JobOffer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $sourceId = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $originalLink = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $publicationDate = null;

    #[ORM\Column]
    private ?int $minimumSalary = null;

    #[ORM\Column]
    private ?int $maximumSalary = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $location = null;

    #[ORM\Column]
    private ?int $presenceMode = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $positionTitle = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column]
    private ?int $applicationStatus = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $applicationDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $rejectionDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $interviewDate1 = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $interviewDate2 = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $interviewDate3 = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $applicationValidationDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $cancellationDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $coverLetter = null;

    #[ORM\Column(nullable: true)]
    private ?int $desiredSalary = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $availabilityDate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSourceId(): ?int
    {
        return $this->sourceId;
    }

    public function setSourceId(int $sourceId): static
    {
        $this->sourceId = $sourceId;

        return $this;
    }

    public function getOriginalLink(): ?string
    {
        return $this->originalLink;
    }

    public function setOriginalLink(string $originalLink): static
    {
        $this->originalLink = $originalLink;

        return $this;
    }

    public function getPublicationDate(): ?\DateTimeImmutable
    {
        return $this->publicationDate;
    }

    public function setPublicationDate(\DateTimeImmutable $publicationDate): static
    {
        $this->publicationDate = $publicationDate;

        return $this;
    }

    public function getMinimumSalary(): ?int
    {
        return $this->minimumSalary;
    }

    public function setMinimumSalary(int $minimumSalary): static
    {
        $this->minimumSalary = $minimumSalary;

        return $this;
    }

    public function getMaximumSalary(): ?int
    {
        return $this->maximumSalary;
    }

    public function setMaximumSalary(int $maximumSalary): static
    {
        $this->maximumSalary = $maximumSalary;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getPresenceMode(): ?int
    {
        return $this->presenceMode;
    }

    public function setPresenceMode(int $presenceMode): static
    {
        $this->presenceMode = $presenceMode;

        return $this;
    }

    public function getPositionTitle(): ?string
    {
        return $this->positionTitle;
    }

    public function setPositionTitle(string $positionTitle): static
    {
        $this->positionTitle = $positionTitle;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getApplicationStatus(): ?int
    {
        return $this->applicationStatus;
    }

    public function setApplicationStatus(int $applicationStatus): static
    {
        $this->applicationStatus = $applicationStatus;

        return $this;
    }

    public function getApplicationDate(): ?\DateTimeImmutable
    {
        return $this->applicationDate;
    }

    public function setApplicationDate(?\DateTimeImmutable $applicationDate): static
    {
        $this->applicationDate = $applicationDate;

        return $this;
    }

    public function getRejectionDate(): ?\DateTimeImmutable
    {
        return $this->rejectionDate;
    }

    public function setRejectionDate(?\DateTimeImmutable $rejectionDate): static
    {
        $this->rejectionDate = $rejectionDate;

        return $this;
    }

    public function getInterviewDate1(): ?\DateTimeImmutable
    {
        return $this->interviewDate1;
    }

    public function setInterviewDate1(?\DateTimeImmutable $interviewDate1): static
    {
        $this->interviewDate1 = $interviewDate1;

        return $this;
    }

    public function getInterviewDate2(): ?\DateTimeImmutable
    {
        return $this->interviewDate2;
    }

    public function setInterviewDate2(?\DateTimeImmutable $interviewDate2): static
    {
        $this->interviewDate2 = $interviewDate2;

        return $this;
    }

    public function getInterviewDate3(): ?\DateTimeImmutable
    {
        return $this->interviewDate3;
    }

    public function setInterviewDate3(?\DateTimeImmutable $interviewDate3): static
    {
        $this->interviewDate3 = $interviewDate3;

        return $this;
    }

    public function getApplicationValidationDate(): ?\DateTimeImmutable
    {
        return $this->applicationValidationDate;
    }

    public function setApplicationValidationDate(?\DateTimeImmutable $applicationValidationDate): static
    {
        $this->applicationValidationDate = $applicationValidationDate;

        return $this;
    }

    public function getCancellationDate(): ?\DateTimeImmutable
    {
        return $this->cancellationDate;
    }

    public function setCancellationDate(?\DateTimeImmutable $cancellationDate): static
    {
        $this->cancellationDate = $cancellationDate;

        return $this;
    }

    public function getCoverLetter(): ?string
    {
        return $this->coverLetter;
    }

    public function setCoverLetter(?string $coverLetter): static
    {
        $this->coverLetter = $coverLetter;

        return $this;
    }

    public function getDesiredSalary(): ?int
    {
        return $this->desiredSalary;
    }

    public function setDesiredSalary(?int $desiredSalary): static
    {
        $this->desiredSalary = $desiredSalary;

        return $this;
    }

    public function getAvailabilityDate(): ?\DateTimeImmutable
    {
        return $this->availabilityDate;
    }

    public function setAvailabilityDate(?\DateTimeImmutable $availabilityDate): static
    {
        $this->availabilityDate = $availabilityDate;

        return $this;
    }
}
