<?php

namespace App\Entity;

use App\Repository\WordRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User; 


#[ORM\Entity(repositoryClass: WordRepository::class)]
#[ORM\Table(name: 'word')]
#[ORM\UniqueConstraint(name: 'user_word_unique', columns: ['user_id', 'value'])]
class Word
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 255)]
    private string $value;

    #[ORM\Column(type: 'text')]
    private string $definition;

    #[ORM\Column(type: 'text')]
    private string $exampleSentence;

    #[ORM\Column(type: 'text')]
    private string $difficulty = '';

      #[ORM\Column(length: 255)]
    private string $type = '';

    #[ORM\Column(length: 2000, nullable: true)]
    private ?string $tags = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        User $user,
        string $value,
        string $definition,
        string $exampleSentence,
        string $difficulty,
        string $type,
        string $tags
    ) {
        $this->user = $user;
        $this->value = $value;
        $this->definition = $definition;
        $this->exampleSentence = $exampleSentence;
        $this->difficulty = $difficulty;
        $this->type = $type;
        $this->tags = $tags;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function getValue(): string { return $this->value; }
    public function getDefinition(): string { return $this->definition; }
    public function getExampleSentence(): string { return $this->exampleSentence; }
    public function getDifficulty(): string { return $this->difficulty; }
    public function getType(): string { return $this->type; }
    public function getTags(): ?string { return $this->tags; }
}