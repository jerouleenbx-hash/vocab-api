<?php

namespace App\Application\UseCase\Quiz\DefinitionToWord;

class DefinitionToWordQuizOutput
{
    public function __construct(
        public string $definition,
        public array $words,
        public string $exampleSentence
    ) {}
}