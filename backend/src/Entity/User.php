<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * @var Collection<int, SearchQuery>
     */
    #[ORM\OneToMany(targetEntity: SearchQuery::class, mappedBy: 'createdBy')]
    private Collection $searchQueries;

    /**
     * @var Collection<int, SearchQuery>
     */
    #[ORM\ManyToMany(targetEntity: SearchQuery::class, mappedBy: 'interestedUsers')]
    private Collection $interestedSearchQueries;

    /**
     * @var Collection<int, JobApplication>
     */
    #[ORM\OneToMany(targetEntity: JobApplication::class, mappedBy: 'user')]
    private Collection $jobApplications;

    public function __construct()
    {
        $this->searchQueries = new ArrayCollection();
        $this->interestedSearchQueries = new ArrayCollection();
        $this->jobApplications = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0" . self::class . "\0password"] = hash('crc32c', $this->password);
        unset(
            $data["\0" . self::class . "\0searchQueries"],
            $data["\0" . self::class . "\0interestedSearchQueries"],
            $data["\0" . self::class . "\0jobApplications"],
        );

        return $data;
    }

    public function __unserialize(array $data): void
    {
        $this->__construct();

        foreach ($data as $key => $value) {
            $property = strrchr($key, "\0");
            $property = $property === false ? $key : substr($property, 1);
            $this->$property = $value;
        }
    }

    /**
     * @return Collection<int, SearchQuery>
     */
    public function getSearchQueries(): Collection
    {
        return $this->searchQueries;
    }

    public function addSearchQuery(SearchQuery $searchQuery): static
    {
        if (!$this->searchQueries->contains($searchQuery)) {
            $this->searchQueries->add($searchQuery);
            $searchQuery->setCreatedBy($this);
        }

        return $this;
    }

    public function removeSearchQuery(SearchQuery $searchQuery): static
    {
        if ($this->searchQueries->removeElement($searchQuery)) {
            if ($searchQuery->getCreatedBy() === $this) {
                $searchQuery->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, SearchQuery>
     */
    public function getInterestedSearchQueries(): Collection
    {
        return $this->interestedSearchQueries;
    }

    public function addInterestedSearchQuery(SearchQuery $searchQuery): static
    {
        if (!$this->interestedSearchQueries->contains($searchQuery)) {
            $this->interestedSearchQueries->add($searchQuery);
            $searchQuery->addInterestedUser($this);
        }

        return $this;
    }

    public function removeInterestedSearchQuery(SearchQuery $searchQuery): static
    {
        if ($this->interestedSearchQueries->removeElement($searchQuery)) {
            $searchQuery->removeInterestedUser($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, JobApplication>
     */
    public function getJobApplications(): Collection
    {
        return $this->jobApplications;
    }

    public function addJobApplication(JobApplication $jobApplication): static
    {
        if (!$this->jobApplications->contains($jobApplication)) {
            $this->jobApplications->add($jobApplication);
            $jobApplication->setUser($this);
        }

        return $this;
    }

    public function removeJobApplication(JobApplication $jobApplication): static
    {
        if ($this->jobApplications->removeElement($jobApplication)) {
            if ($jobApplication->getUser() === $this) {
                $jobApplication->setUser(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->email;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }
}
