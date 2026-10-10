<?php

namespace App\Entity;

use App\Repository\JobApplicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: JobApplicationRepository::class)]
class JobApplication
{
    public const STATUSES = [
        'Not yet applied' => 0,
        'Application submitted' => 1,
        'First interview' => 2,
        'Second interview' => 3,
        'Third interview' => 4,
        'Application accepted' => 5,
        'Application rejected' => 6,
        'Application cancelled' => 7,
    ];

    public const CURRENCIES = [
        'NA' => 0,
        'EUR' => 1,
        'CHF' => 2,
        'TND' => 3,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'jobApplications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?JobOffer $jobOffer = null;

    #[ORM\ManyToOne(inversedBy: 'jobApplications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $applicationStatus = 0;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $applicationDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $interviewDate1 = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $interviewDate2 = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $interviewDate3 = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $rejectionDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $cancellationDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $firstStartDate = null;

    #[ORM\Column(nullable: true)]
    private ?int $desiredSalaryAmount = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $desiredSalaryCurrency = 0;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?FileReference $coverLetter = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?FileReference $cv = null;

    /**
     * @var Collection<int, FileReference>
     */
    #[ORM\ManyToMany(targetEntity: FileReference::class)]
    #[ORM\JoinTable(name: 'job_application_reference_file')]
    private Collection $referenceFiles;

    /**
     * @var Collection<int, FileReference>
     */
    #[ORM\ManyToMany(targetEntity: FileReference::class)]
    #[ORM\JoinTable(name: 'job_application_diploma_file')]
    private Collection $diplomas;

    /**
     * @var Collection<int, FileReference>
     */
    #[ORM\ManyToMany(targetEntity: FileReference::class)]
    #[ORM\JoinTable(name: 'job_application_additional_file')]
    private Collection $additionalFiles;

    public function __construct()
    {
        $this->referenceFiles = new ArrayCollection();
        $this->diplomas = new ArrayCollection();
        $this->additionalFiles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJobOffer(): ?JobOffer
    {
        return $this->jobOffer;
    }

    public function setJobOffer(?JobOffer $jobOffer): static
    {
        $this->jobOffer = $jobOffer;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getApplicationStatus(): int
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

    public function getRejectionDate(): ?\DateTimeImmutable
    {
        return $this->rejectionDate;
    }

    public function setRejectionDate(?\DateTimeImmutable $rejectionDate): static
    {
        $this->rejectionDate = $rejectionDate;

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

    public function getFirstStartDate(): ?\DateTimeImmutable
    {
        return $this->firstStartDate;
    }

    public function setFirstStartDate(?\DateTimeImmutable $firstStartDate): static
    {
        $this->firstStartDate = $firstStartDate;

        return $this;
    }

    public function getDesiredSalaryAmount(): ?int
    {
        return $this->desiredSalaryAmount;
    }

    public function setDesiredSalaryAmount(?int $desiredSalaryAmount): static
    {
        $this->desiredSalaryAmount = $desiredSalaryAmount;

        return $this;
    }

    public function getDesiredSalaryCurrency(): int
    {
        return $this->desiredSalaryCurrency;
    }

    public function setDesiredSalaryCurrency(int $desiredSalaryCurrency): static
    {
        $this->desiredSalaryCurrency = $desiredSalaryCurrency;

        return $this;
    }

    public function getCoverLetter(): ?FileReference
    {
        return $this->coverLetter;
    }

    public function setCoverLetter(?FileReference $coverLetter): static
    {
        $this->coverLetter = $coverLetter;

        return $this;
    }

    public function getCv(): ?FileReference
    {
        return $this->cv;
    }

    public function setCv(?FileReference $cv): static
    {
        $this->cv = $cv;

        return $this;
    }

    /**
     * @return Collection<int, FileReference>
     */
    public function getReferenceFiles(): Collection
    {
        return $this->referenceFiles;
    }

    public function addReferenceFile(FileReference $referenceFile): static
    {
        if (!$this->referenceFiles->contains($referenceFile)) {
            $this->referenceFiles->add($referenceFile);
        }

        return $this;
    }

    public function removeReferenceFile(FileReference $referenceFile): static
    {
        $this->referenceFiles->removeElement($referenceFile);

        return $this;
    }

    /**
     * @return Collection<int, FileReference>
     */
    public function getDiplomas(): Collection
    {
        return $this->diplomas;
    }

    public function addDiploma(FileReference $diploma): static
    {
        if (!$this->diplomas->contains($diploma)) {
            $this->diplomas->add($diploma);
        }

        return $this;
    }

    public function removeDiploma(FileReference $diploma): static
    {
        $this->diplomas->removeElement($diploma);

        return $this;
    }

    /**
     * @return Collection<int, FileReference>
     */
    public function getAdditionalFiles(): Collection
    {
        return $this->additionalFiles;
    }

    public function addAdditionalFile(FileReference $additionalFile): static
    {
        if (!$this->additionalFiles->contains($additionalFile)) {
            $this->additionalFiles->add($additionalFile);
        }

        return $this;
    }

    public function removeAdditionalFile(FileReference $additionalFile): static
    {
        $this->additionalFiles->removeElement($additionalFile);

        return $this;
    }

    public function __toString(): string
    {
        return trim(sprintf('%s — %s', $this->jobOffer, $this->user), ' —');
    }
}
