<?php

namespace App\Application\UseCase\Quiz\WordToDefinition;

use App\Domain\Repository\WordRepository;
use App\Domain\Repository\WordProgressRepository;
use App\Infrastructure\Persistence\Doctrine\Word;

class GenerateWordToDefinitionQuiz
{
    public function __construct(
        private WordRepository $wordRepo,
        private WordProgressRepository $progressRepo
    ) {}

    public function execute(int $userId, int $limit = 4): WordToDefinitionQuizOutput
    {
        // 1️⃣ Récupérer les mots à réviser
        $dueWords = $this->progressRepo->findDueWords($userId);
        if (!$dueWords) {
            throw new \DomainException('No words to review.');
        }

        $mainWordProgress = $dueWords[0];
        $word = $mainWordProgress->getWord();

        // 2️⃣ Générer distractors
        $distractors = $this->wordRepo->findRandomByDifficulty($word->getDifficulty(), $limit - 1);
        $options = array_map(fn(Word $w) => $w->getDefinition(), $distractors);
        $options[] = $word->getDefinition();
        shuffle($options);

        // 3️⃣ Masquer le mot dans la phrase
        $maskedSentence = preg_replace('/\b' . preg_quote($word->getValue(), '/') . '\b/i', '______', $word->getExampleSentence());

        return new WordToDefinitionQuizOutput(
            $word->getValue(),
            $maskedSentence,
            $options
        );
    }
}