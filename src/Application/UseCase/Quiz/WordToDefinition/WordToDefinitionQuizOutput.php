<?php

namespace App\Application\UseCase\Quiz\WordToDefinition;

class WordToDefinitionQuizOutput
{
    public function __construct(
        public string $word,
        public string $maskedSentence,
        public array $definitions
    ) {}
}