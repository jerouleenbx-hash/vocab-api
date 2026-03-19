<?php

namespace App\Controller;

use App\Entity\Word;
use App\Entity\WordProgress;
use App\Entity\User;
use App\Repository\WordRepository;
use App\Repository\WordProgressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\QuizService;

#[Route('/api')]
class MainController extends AbstractController
{
    /**
     * Méthode appelée pour avoir tous les tags
     */
    #[Route('/tags', name: 'api_tags')]
    public function tags(WordRepository $wordRepository): Response
    {
        $tags = $wordRepository->findAllTags();

        return $this->json($tags);
    }

    /**
     * Méthode appelée pour avoir tous les mots
     */
    #[Route('/words', name: 'api_words')]
    public function words(Request $request, WordRepository $wordRepository): JsonResponse
    {
        $tag = $request->query->get('tag');     // récupère ?tag=...
        $difficulty = $request->query->get('level');

        //$words = $wordRepository->findByFilters($tag, $difficulty);
        $words = $wordRepository->findAll();

        $data = array_map(fn(Word $word) => [
            'id' => $word->getId(),
            'word' => $word->getValue(),
            'definition' => $word->getDefinition(),
            'example' => $word->getExampleSentence(),
            'difficulty' => $difficulty,
            'type' => $word->getType(),
            'tags' => $word->getTags(),
        ], $words);

        return $this->json($data);
    }


    /**
     * Méthode appelée à chaque réponse pour mettre à jour le score
     */
    #[Route('/answer_old', methods: ['POST'])]
    public function answer_old(
        Request $request,
        WordRepository $wordRepository,
        WordProgressRepository $progressRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            $user = $em->getRepository(User::class)->find(1); // fallback test
            if (!$user) {
                return $this->json(['error' => 'No user in database'], 400);
            }
        }

        $data = json_decode($request->getContent(), true);
        $wordId = $data['word_id'] ?? null;
        $score = $data['score'] ?? 0;

        if (!$wordId) {
            return $this->json(['error' => 'Missing word_id'], 400);
        }

        $word = $wordRepository->find($wordId);
        if (!$word) {
            return $this->json(['error' => 'Word not found'], 404);
        }

        $progress = $em->getRepository(WordProgress::class)->findProgress($user, $word);
        if (!$progress) {
            $progress = new WordProgress($user, $word);            
        }
        $em->persist($progress);
        $progress->setScore($score);
        $progress->setLastSeenAt(new \DateTimeImmutable());
        $em->flush();

        return $this->json([
            'message' => 'Progress updated',
            'word_id' => $word->getId(),
            'score' => $score,
        ]);
    }

#[Route('/quiz/prioritized', methods: ['GET'])]
public function getPrioritizedQuizWords(
    Request $request,
    WordRepository $wordRepository,
    EntityManagerInterface $em
): JsonResponse {

    $level = $request->query->get('level');
    $tag = $request->query->get('tag');

    $user = $this->getUser();
    if (!$user) {
        $user = $em->getRepository(User::class)->find(1);
        if (!$user) {
            return $this->json(['error' => 'No user in database'], 400);
        }
    }

    // ⚡ Requête optimisée (tableaux)
    $filteredWords = $wordRepository->findByDifficultyAndTagOrderedByScore($level, $tag, $user);

    $allWords = $wordRepository->findAllByType($user);

    $allWordsByType = [];

    foreach ($allWords as $w) {
        $allWordsByType[$w['type']][] = $w;
    }

    if (empty($filteredWords)) {
        return $this->json(['error' => 'No words found'], 404);
    }

    // 🔥 Pré-groupement par type (évite O(n²))
    $wordsByType = [];
    foreach ($filteredWords as $w) {
        $wordsByType[$w['type']][] = $w;
    }

    $result = [];

    foreach ($filteredWords as $word) {

        // 7.1. mots du même type
        $sameTypeWords = $allWordsByType[$word['type']] ?? [];
        // Exclure le mot courant
        $sameTypeWords = array_filter($sameTypeWords, function($w) use ($word) {
            return $w['id'] !== $word['id'];
        });

        shuffle($sameTypeWords);

        // 7.2. choix uniques
        $wrongChoices = [];
        $usedDefinitions = [$word['definition']];

        foreach ($sameTypeWords as $w) {
            if (count($wrongChoices) >= 3) break;

            $definition = $w['definition'];

            if (!in_array($definition, $usedDefinitions, true)) {
                $wrongChoices[] = $definition;
                $usedDefinitions[] = $definition;
            }
        }

        // 7.3. fallback
        while (count($wrongChoices) < 3) {
            $wrongChoices[] = "---";
        }

        // 7.4. mélange
        $allChoices = array_merge($wrongChoices, [$word['definition']]);
        shuffle($allChoices);

        // 7.5. résultat
        $result[] = [
            'id' => $word['id'],
            'word' => $word['value'],
            'definition' => $word['definition'],
            'difficulty' => $word['difficulty'],
            'type' => $word['type'],
            'tags' => $word['tags'],
            'example' => $word['example_sentence'],
            'choices' => $allChoices,
        ];
    }

    return $this->json($result);
}



    #[Route('/quiz/fsrs', methods: ['GET'])]
    public function quizFSRS(
        Request $request,
        WordRepository $repo,
        QuizService $quizService,
        EntityManagerInterface $em
    ): JsonResponse {

        $user = $this->getUser();
        if (!$user) {
            $user = $em->getRepository(User::class)->find(1); // fallback test
            if (!$user) {
                return $this->json(['error' => 'No user in database'], 400);
            }
        }

        $level = $request->query->get('level');
        $tag = $request->query->get('tag');

        $words = $repo->findQuizWordsFSRS($level, $tag, $user->getId());

        // ⚡ charger tous les mots pour les choix
        $allWords = $em->getConnection()
            ->executeQuery("SELECT id, value, definition, example_sentence, type FROM word")
            ->fetchAllAssociative();

        $byType = [];
        foreach ($allWords as $w) {
            $byType[$w['type']][] = $w;
        }

        $result = [];

        foreach ($words as $word) {

            $choices = $quizService->buildChoices($word, $byType);

            $result[] = [
                'id' => $word['id'],
                'word' => $word['value'],
                'definition' => $word['definition'],
                'difficulty' => $word['difficulty'],
                'type' => $word['type'],
                'tags' => $word['tags'],
                'example_sentence' => $word['example_sentence'],
                'choices' => $choices,
            ];
        }

        return $this->json($result);
    }

    #[Route('/answer', methods: ['POST'])]
    public function answer(
        Request $request,
        \App\Service\FSRSService $fsrs,
        EntityManagerInterface $em
    ): JsonResponse {

        $user = $this->getUser();
        if (!$user) {
            $user = $em->getRepository(User::class)->find(1); // fallback test
            if (!$user) {
                return $this->json(['error' => 'No user in database'], 400);
            }
        }

        $data = json_decode($request->getContent(), true);

        $wordId = $data['wordId'] ?? null;
        $grade = $data['grade'] ?? null;

        if (!$wordId || $grade === null) {
            return $this->json(['error' => $wordId], 400);
        }

        $conn = $em->getConnection();

        // 🔍 1. récupérer progression existante
        $sql = "
            SELECT *
            FROM word_progress
            WHERE user_id = :user AND word_id = :word
            LIMIT 1
        ";

        $progress = $conn->executeQuery($sql, [
            'user' => $user->getId(),
            'word' => $wordId
        ])->fetchAssociative();

        // 🧠 2. appliquer FSRS
        $updated = $fsrs->update($progress ?: [], (int)$grade);

        // 💾 3. insert ou update
        if ($progress) {

            $conn->executeStatement("
                UPDATE word_progress
                SET 
                    stability = :stability,
                    difficulty = :difficulty,
                    next_review = :next_review,
                    last_review = :last_review,
                    reps = :reps,
                    lapses = :lapses
                WHERE user_id = :user AND word_id = :word
            ", [
                'stability' => $updated['stability'],
                'difficulty' => $updated['difficulty'],
                'next_review' => $updated['next_review']->format('Y-m-d H:i:s'),
                'last_review' => $updated['last_review']->format('Y-m-d H:i:s'),
                'reps' => $updated['reps'],
                'lapses' => $progress['lapses'],
                'user' => $user->getId(),
                'word' => $wordId
            ]);

        } else {

            $conn->executeStatement("
                INSERT INTO word_progress 
                (user_id, word_id, stability, difficulty, next_review, last_review, reps, lapses)
                VALUES (:user, :word, :stability, :difficulty, :next_review, :last_review, :reps, 0)
            ", [
                'user' => $user->getId(),
                'word' => $wordId,
                'stability' => $updated['stability'],
                'difficulty' => $updated['difficulty'],
                'next_review' => $updated['next_review']->format('Y-m-d H:i:s'),
                'last_review' => $updated['last_review']->format('Y-m-d H:i:s'),
                'reps' => $updated['reps']
            ]);
        }

        // 📤 4. réponse API
        return $this->json([
            'success' => true,
            'nextReview' => $updated['next_review']->format('Y-m-d H:i:s'),
            'stability' => $updated['stability'],
            'difficulty' => $updated['difficulty']
        ]);
    }
}