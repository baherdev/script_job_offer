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

    #[ORM\Column(length: 255)]
    private ?string $fileName = null;

    #[ORM\Column(length: 255)]
    private ?string $filePath = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $creationDate = null;

    #[ORM\OneToOne(mappedBy: 'logo', targetEntity: Enterprise::class)]
    private ?Enterprise $enterprise = null;

    public function __construct()
    {
        $this->creationDate = new \DateTimeImmutable('today');
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): static
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getCreationDate(): ?\DateTimeImmutable
    {
        return $this->creationDate;
    }

    public function setCreationDate(\DateTimeImmutable $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getEnterprise(): ?Enterprise
    {
        return $this->enterprise;
    }

    public function setEnterprise(?Enterprise $enterprise): static
    {
        if ($this->enterprise === $enterprise) {
            return $this;
        }

        $previous = $this->enterprise;
        $this->enterprise = $enterprise;

        if ($previous !== null && $previous->getLogo() === $this) {
            $previous->setLogo(null);
        }

        if ($enterprise !== null && $enterprise->getLogo() !== $this) {
            $enterprise->setLogo($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->fileName;
    }
}
