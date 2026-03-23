<?php

namespace App\Service;

use App\Entity\Word;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Psr\Log\LoggerInterface;

class WordService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}


     public function importFromCsv(string $filePath, User $user): void
    {
        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);

        foreach ($csv as $record) {
            $value = $record['value'] ?? '';
            $difficulty = $record['difficulty'] ?? '';
            $type = $record['type'] ?? '';
            $tags = $record['tags'] ?? null;

            // Vérifie si le mot existe déjà pour cet utilisateur
            $existingWord = $this->entityManager->getRepository(Word::class)
                ->findOneBy(['user' => $user, 'value' => $value]);

            if ($existingWord) {
                $this->logger->info(sprintf(
                    'Word "%s" already exists for user %d. Skipping.',
                    $value,
                    $user->getId()
                ));
                continue; // Passe au mot suivant
            }

            // Crée un nouveau mot
            $word = new Word(
                $user,
                $value,
                $record['definition'] ?? '',
                $record['example_sentence'] ?? '',
                $difficulty,
                $type,
                $tags
            );

            $this->entityManager->persist($word);
        }

        $this->entityManager->flush();
    }
}
