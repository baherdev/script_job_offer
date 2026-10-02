<?php

namespace App\Entity;

use App\Repository\FileReferenceRepository;
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

    public function __construct()
    {
        $this->creationDate = new \DateTimeImmutable();
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
}
