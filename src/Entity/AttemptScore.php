<?php

namespace App\Entity;

use App\Repository\AttemptScoreRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AttemptScoreRepository::class)]
class AttemptScore
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 1)]
    private ?string $score = null;

    #[ORM\ManyToOne(inversedBy: 'attemptScores')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Attempt $attempt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getScore(): ?string
    {
        return $this->score;
    }

    public function setScore(string $score): static
    {
        $this->score = $score;

        return $this;
    }

    public function getAttempt(): ?Attempt
    {
        return $this->attempt;
    }

    public function setAttempt(?Attempt $attempt): static
    {
        $this->attempt = $attempt;

        return $this;
    }
}
