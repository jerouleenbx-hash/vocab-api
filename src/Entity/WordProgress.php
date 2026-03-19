<?php

namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\WordProgressRepository::class)]
#[ORM\Table(name: 'word_progress')]
class WordProgress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Word::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Word $word;

    #[ORM\Column(type: 'integer')]
    private int $score = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $lastSeenAt;

    #[ORM\Column]
    private ?float $stability = null;

    #[ORM\Column]
    private ?float $difficulty = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $last_review = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $next_review = null;

    #[ORM\Column]
    private ?int $reps = null;

    #[ORM\Column]
    private ?int $lapses = null;

    public function __construct(User $user, Word $word)
    {
        $this->user = $user;
        $this->word = $word;
        $this->lastSeenAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getWord(): Word
    {
        return $this->word;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score) : void
    {
        $this->score = $score;
    }

    public function getLastSeenAt(): \DateTimeImmutable
    {
        return $this->lastSeenAt;
    }

    public function setLastSeenAt(\DateTimeImmutable $lastSeenAt): void
    {
        $this->lastSeenAt = $lastSeenAt;
    }

    public function getStability(): ?float
    {
        return $this->stability;
    }

    public function setStability(float $stability): static
    {
        $this->stability = $stability;

        return $this;
    }

    public function getDifficulty(): ?float
    {
        return $this->difficulty;
    }

    public function setDifficulty(float $difficulty): static
    {
        $this->difficulty = $difficulty;

        return $this;
    }

    public function getLastReview(): ?\DateTime
    {
        return $this->last_review;
    }

    public function setLastReview(?\DateTime $last_review): static
    {
        $this->last_review = $last_review;

        return $this;
    }

    public function getNextReview(): ?\DateTime
    {
        return $this->next_review;
    }

    public function setNextReview(?\DateTime $next_review): static
    {
        $this->next_review = $next_review;

        return $this;
    }

    public function getReps(): ?int
    {
        return $this->reps;
    }

    public function setReps(int $reps): static
    {
        $this->reps = $reps;

        return $this;
    }

    public function getLapses(): ?int
    {
        return $this->lapses;
    }

    public function setLapses(int $lapses): static
    {
        $this->lapses = $lapses;

        return $this;
    }
}