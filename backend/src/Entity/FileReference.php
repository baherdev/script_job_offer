<?php

namespace App\Entity;

use App\Repository\FileReferenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FileReferenceRepository::class)]
class FileReference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $fileType = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $fileName = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $fileVersion = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $filePath = null;

    #[ORM\Column]
    private \DateTimeImmutable $creationDate;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActif = true;

    /**
     * @var Collection<int, JobOffer>
     */
    #[ORM\ManyToMany(targetEntity: JobOffer::class, mappedBy: 'fileReferences')]
    private Collection $jobOffers;

    public function __construct()
    {
        $this->creationDate = new \DateTimeImmutable();
        $this->jobOffers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFileType(): ?int
    {
        return $this->fileType;
    }

    public function setFileType(int $fileType): static
    {
        $this->fileType = $fileType;

        return $this;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): static
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getFileVersion(): ?string
    {
        return $this->fileVersion;
    }

    public function setFileVersion(string $fileVersion): static
    {
        $this->fileVersion = $fileVersion;

        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): static
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getCreationDate(): \DateTimeImmutable
    {
        return $this->creationDate;
    }

    public function setCreationDate(\DateTimeImmutable $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function isActif(): bool
    {
        return $this->isActif;
    }

    public function setIsActif(bool $isActif): static
    {
        $this->isActif = $isActif;

        return $this;
    }

    /**
     * @return Collection<int, JobOffer>
     */
    public function getJobOffers(): Collection
    {
        return $this->jobOffers;
    }

    public function addJobOffer(JobOffer $jobOffer): static
    {
        if (!$this->jobOffers->contains($jobOffer)) {
            $this->jobOffers->add($jobOffer);
            $jobOffer->addFileReference($this);
        }

        return $this;
    }

    public function removeJobOffer(JobOffer $jobOffer): static
    {
        if ($this->jobOffers->removeElement($jobOffer)) {
            $jobOffer->removeFileReference($this);
        }

        return $this;
    }
}
