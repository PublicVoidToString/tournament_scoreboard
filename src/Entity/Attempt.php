<?php

namespace App\Entity;

use App\Repository\AttemptRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AttemptRepository::class)]
class Attempt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'attempts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Competitor $competitor = null;

    #[ORM\ManyToOne(inversedBy: 'attempts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    /**
     * @var Collection<int, AttemptScore>
     */
    #[ORM\OneToMany(targetEntity: AttemptScore::class, mappedBy: 'attempt', orphanRemoval: true)]
    private Collection $attemptScores;

    public function __construct()
    {
        $this->attemptScores = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompetitor(): ?Competitor
    {
        return $this->competitor;
    }

    public function setCompetitor(?Competitor $competitor): static
    {
        $this->competitor = $competitor;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, AttemptScore>
     */
    public function getAttemptScores(): Collection
    {
        return $this->attemptScores;
    }

    public function addAttemptScore(AttemptScore $attemptScore): static
    {
        if (!$this->attemptScores->contains($attemptScore)) {
            $this->attemptScores->add($attemptScore);
            $attemptScore->setAttempt($this);
        }

        return $this;
    }

    public function removeAttemptScore(AttemptScore $attemptScore): static
    {
        if ($this->attemptScores->removeElement($attemptScore)) {
            // set the owning side to null (unless already changed)
            if ($attemptScore->getAttempt() === $this) {
                $attemptScore->setAttempt(null);
            }
        }

        return $this;
    }
}
