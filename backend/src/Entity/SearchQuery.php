<?php

namespace App\Entity;

use App\Repository\SearchQueryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SearchQueryRepository::class)]
class SearchQuery
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $keyword = null;

    #[ORM\Column(length: 255)]
    private ?string $location = null;

    #[ORM\Column(nullable: true)]
    private ?int $distance = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'searchQueries')]
    #[ORM\JoinColumn(name: 'user_id', nullable: false)]
    private ?User $createdBy = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'interestedSearchQueries')]
    #[ORM\JoinTable(name: 'search_query_interested_user')]
    private Collection $interestedUsers;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->interestedUsers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKeyword(): ?string
    {
        return $this->keyword;
    }

    public function setKeyword(string $keyword): static
    {
        $this->keyword = $keyword;

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

    public function getDistance(): ?int
    {
        return $this->distance;
    }

    public function setDistance(?int $distance): static
    {
        $this->distance = $distance;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getInterestedUsers(): Collection
    {
        return $this->interestedUsers;
    }

    public function addInterestedUser(User $user): static
    {
        if (!$this->interestedUsers->contains($user)) {
            $this->interestedUsers->add($user);
            $user->addInterestedSearchQuery($this);
        }

        return $this;
    }

    public function removeInterestedUser(User $user): static
    {
        if ($this->interestedUsers->removeElement($user)) {
            $user->removeInterestedSearchQuery($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return trim(sprintf('%s — %s', $this->keyword, $this->location), ' —');
    }
}
