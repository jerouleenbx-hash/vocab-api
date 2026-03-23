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
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface; 
use App\Service\WordService; 


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
    public function words(
        Request $request, 
        WordRepository $wordRepository,
        EntityManagerInterface $em,        
        \App\Service\FSRSService $fsrs
        ): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            $user = $em->getRepository(User::class)->find(1); // fallback test
            if (!$user) {
                return $this->json(['error' => 'No user in database'], 400);
            }
        }

        $tag = $request->query->get('tag');     // récupère ?tag=...
        $difficulty = $request->query->get('level');        

        $words = $wordRepository->findByTagAndDifficulty($difficulty, $tag,  $user);        

        $data = [];

        foreach ($words as $word) {
            $masteryScore = $fsrs->masteryScore($word);
            $data[] = [
                'id' => $word['id'],
                'word' => $word['value'],
                'definition' => $word['definition'],
                'difficulty' => $word['level'],
                'type' => $word['type'],
                'tags' => $word['tags'],
                'example_sentence' => $word['example_sentence'],
                'score' => $masteryScore,
            ];
        }

        return $this->json($data);
    }


    #[Route('/quiz/definition', methods: ['GET'])]
    public function quizDefinition(
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

            $choices = $quizService->buildChoicesDefinition($word, $byType);

            $result[] = [
                'id' => $word['id'],
                'word' => $word['value'],
                'definition' => $word['definition'],
                'difficulty' => $word['difficulty'],
                'type' => $word['type'],
                'tags' => $word['tags'],
                'example' => $word['example_sentence'],
                'choices' => $choices,
            ];
        }

        return $this->json($result);
    }


    #[Route('/quiz/word', methods: ['GET'])]
    public function quizWord(
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

            $choices = $quizService->buildChoicesWord($word, $byType);

            $result[] = [
                'id' => $word['id'],
                'word' => $word['value'],
                'definition' => $word['definition'],
                'difficulty' => $word['difficulty'],
                'type' => $word['type'],
                'tags' => $word['tags'],
                'example' => $word['example_sentence'],
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
                    lapses = :lapses,
                    score = :score
                WHERE user_id = :user AND word_id = :word
            ", [
                'stability' => $updated['stability'],
                'difficulty' => $updated['difficulty'],
                'next_review' => $updated['next_review']->format('Y-m-d H:i:s'),
                'last_review' => $updated['last_review']->format('Y-m-d H:i:s'),
                'reps' => $updated['reps'],
                'lapses' => $progress['lapses'],
                'user' => $user->getId(),
                'word' => $wordId,
                'score' => $grade
            ]);

        } else {

            $conn->executeStatement("
                INSERT INTO word_progress 
                (user_id, word_id, stability, difficulty, next_review, last_review, reps, score, lapses)
                VALUES (:user, :word, :stability, :difficulty, :next_review, :last_review, :reps, :score, 0)
            ", [
                'user' => $user->getId(),
                'word' => $wordId,
                'stability' => $updated['stability'],
                'difficulty' => $updated['difficulty'],
                'next_review' => $updated['next_review']->format('Y-m-d H:i:s'),
                'last_review' => $updated['last_review']->format('Y-m-d H:i:s'),
                'reps' => $updated['reps'],
                'score' => $grade
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


    #[Route('/import', name: 'import', methods: ['POST'])]
    public function import(
        Request $request,
        WordService $wordImportService,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            $user = $em->getRepository(User::class)->find(1); // fallback test
            if (!$user) {
                return $this->json(['error' => 'No user in database'], 400);
            }
        }

        // Récupère le fichier depuis la requête FormData
        $file = $request->files->get('file');

        if (!$file) {
            return $this->json(['error' => 'No file uploaded.'], 400);
        }

        $filePath = $file->getRealPath();

        try {
            $wordImportService->importFromCsv($filePath, $user);
            return $this->json(['message' => 'CSV imported successfully!'], 200);
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred during import.'], 500);
        }
    }
}