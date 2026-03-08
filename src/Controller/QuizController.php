<?php

namespace App\Controller;

use App\Infrastructure\Persistence\Doctrine\Word;
use App\Infrastructure\Persistence\Doctrine\WordProgress;
use App\Infrastructure\Persistence\Doctrine\User;
use App\Repository\WordRepository;
use App\Repository\WordProgressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/answer')]
class QuizController extends AbstractController
{

    #[Route('', methods: ['POST'])]
    public function answer(
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
        $correct = $data['correct'] ?? false;

        if (!$wordId) {
            return $this->json(['error' => 'Missing word_id'], 400);
        }

        $word = $wordRepository->find($wordId);
        if (!$word) {
            return $this->json(['error' => 'Word not found'], 404);
        }

        $progress = $progressRepository->findOneBy([
            'user' => $user,
            'word' => $word,
        ]);

        if (!$progress) {
            $progress = new WordProgress($user, $word);
            $em->persist($progress);
        }

        $progress->setCorrect($correct);
        $progress->setLastSeenAt(new \DateTimeImmutable());

        $em->flush();

        return $this->json([
            'message' => 'Progress updated',
            'word_id' => $word->getId(),
            'correct' => $correct,
        ]);
    }
}