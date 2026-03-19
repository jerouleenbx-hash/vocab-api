<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Word;
use App\Entity\WordProgress;
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
        $isScore = strtolower($answer) === strtolower($word->getWord());

        $progress = $this->progressRepository->findOneBy([
            'user' => $user,
            'word' => $word
        ]);

        if (!$progress) {
            $progress = new WordProgress();
            $progress->setUser($user);
            $progress->setWord($word);
        }

        if ($isScore) {
            $progress->setScore($progress->getScore() + 1);
        } else {
            $progress->setScore(0);
        }

        $progress->setLastSeenAt(new \DateTime());

        $this->em->persist($progress);
        $this->em->flush();

        return $isScore;
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


    public function buildChoices(array $word, array $allWordsByType): array
    {
        $candidates = $allWordsByType[$word['type']] ?? [];

        $candidates = array_filter($candidates, fn($w) => $w['id'] !== $word['id']);

        // Score de similarité
        usort($candidates, function ($a, $b) use ($word) {
            return $this->similarity($b['value'], $word['value'])
                <=> $this->similarity($a['value'], $word['value']);
        });

        $wrong = [];
        $used = [$word['definition']];

        foreach ($candidates as $c) {
            if (count($wrong) >= 3) break;

            if (!in_array($c['definition'], $used, true)) {
                $wrong[] = $c['definition'];
                $used[] = $c['definition'];
            }
        }

        while (count($wrong) < 3) {
            $wrong[] = "---";
        }

        $choices = array_merge($wrong, [$word['definition']]);
        shuffle($choices);

        return $choices;
    }

    private function similarity($a, $b): float
    {
        $lev = levenshtein($a, $b);
        $len = max(strlen($a), strlen($b));
        return 1 - ($lev / $len);
    }
}