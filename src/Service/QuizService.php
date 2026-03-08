<?php

namespace App\Service;

use App\Infrastructure\Persistence\Doctrine\User;
use App\Infrastructure\Persistence\Doctrine\Word;
use App\Infrastructure\Persistence\Doctrine\WordProgress;
use App\Repository\WordRepository;
use App\Repository\WordProgressRepository;
use Doctrine\ORM\EntityManagerInterface;

class QuizService
{
    public function __construct(
        private WordRepository $wordRepository,
        private WordProgressRepository $progressRepository,
        private EntityManagerInterface $em
    ) {}

    public function getNextWord(User $user): ?Word
    {
        return $this->wordRepository->findNextWordForUser($user->getId());
    }

    public function answer(User $user, Word $word, string $answer): bool
    {
        $isCorrect = strtolower($answer) === strtolower($word->getWord());

        $progress = $this->progressRepository->findOneBy([
            'user' => $user,
            'word' => $word
        ]);

        if (!$progress) {
            $progress = new WordProgress();
            $progress->setUser($user);
            $progress->setWord($word);
        }

        if ($isCorrect) {
            $progress->setCorrect($progress->getCorrect() + 1);
        } else {
            $progress->setCorrect(0);
        }

        $progress->setLastSeenAt(new \DateTime());

        $this->em->persist($progress);
        $this->em->flush();

        return $isCorrect;
    }

    public function getMultipleChoice(User $user): array
    {
        $word = $this->getNextWord($user);

        $choices = [$word->getWord()];

        $randomWords = $this->wordRepository->findRandomWords(3);

        foreach ($randomWords as $w) {
            if ($w->getWord() !== $word->getWord()) {
                $choices[] = $w->getWord();
            }
        }

        shuffle($choices);

        return [
            'word_id' => $word->getId(),
            'definition' => $word->getDefinition(),
            'example' => $word->getExampleSentence(),
            'choices' => $choices
        ];
    }
}