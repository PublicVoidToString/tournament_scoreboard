<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $initial_fee = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $additional_fee = null;

    #[ORM\Column]
    private ?int $attempt_limit = null;

    #[ORM\ManyToOne(inversedBy: 'categories')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tournament $tournament = null;

    #[ORM\ManyToOne(inversedBy: 'categories')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CategoryGroup $category_group = null;

    /**
     * @var Collection<int, Attempt>
     */
    #[ORM\OneToMany(targetEntity: Attempt::class, mappedBy: 'category', orphanRemoval: true)]
    private Collection $attempts;

    public function __construct()
    {
        $this->attempts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getInitialFee(): ?string
    {
        return $this->initial_fee;
    }

    public function setInitialFee(string $initial_fee): static
    {
        $this->initial_fee = $initial_fee;

        return $this;
    }

    public function getAdditionalFee(): ?string
    {
        return $this->additional_fee;
    }

    public function setAdditionalFee(string $additional_fee): static
    {
        $this->additional_fee = $additional_fee;

        return $this;
    }

    public function getAttemptLimit(): ?int
    {
        return $this->attempt_limit;
    }

    public function setAttemptLimit(int $attempt_limit): static
    {
        $this->attempt_limit = $attempt_limit;

        return $this;
    }

    public function getTournament(): ?Tournament
    {
        return $this->tournament;
    }

    public function setTournament(?Tournament $tournament): static
    {
        $this->tournament = $tournament;

        return $this;
    }

    public function getCategoryGroup(): ?CategoryGroup
    {
        return $this->category_group;
    }

    public function setCategoryGroup(?CategoryGroup $category_group): static
    {
        $this->category_group = $category_group;

        return $this;
    }

    /**
     * @return Collection<int, Attempt>
     */
    public function getAttempts(): Collection
    {
        return $this->attempts;
    }

    public function addAttempt(Attempt $attempt): static
    {
        if (!$this->attempts->contains($attempt)) {
            $this->attempts->add($attempt);
            $attempt->setCategory($this);
        }

        return $this;
    }

    public function removeAttempt(Attempt $attempt): static
    {
        if ($this->attempts->removeElement($attempt)) {
            // set the owning side to null (unless already changed)
            if ($attempt->getCategory() === $this) {
                $attempt->setCategory(null);
            }
        }

        return $this;
    }
}
