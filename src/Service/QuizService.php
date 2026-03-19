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



    public function buildChoicesDefinition(array $word, array $allWordsByType): array
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


    public function buildChoicesWord(array $word, array $allWordsByType): array
    {
        $candidates = $allWordsByType[$word['type']] ?? [];

        $candidates = array_filter($candidates, fn($w) => $w['id'] !== $word['id']);

        // Score de similarité
        usort($candidates, function ($a, $b) use ($word) {
            return $this->similarity($b['definition'], $word['definition'])
                <=> $this->similarity($a['definition'], $word['definition']);
        });

        $wrong = [];
        $used = [$word['value']];

        foreach ($candidates as $c) {
            if (count($wrong) >= 3) break;

            if (!in_array($c['value'], $used, true)) {
                $wrong[] = $c['value'];
                $used[] = $c['value'];
            }
        }

        while (count($wrong) < 3) {
            $wrong[] = "---";
        }

        $choices = array_merge($wrong, [$word['value']]);
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