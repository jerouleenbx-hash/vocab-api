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

#[Route('/api/words')]
class WordController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function list(WordRepository $wordRepository): JsonResponse
    {
        $words = $wordRepository->findAll();

        $data = array_map(fn(Word $word) => [
            'id' => $word->getId(),
            'word' => $word->getValue(),
            'definition' => $word->getDefinition(),
            'example' => $word->getExampleSentence(),
            'difficulty' => $word->getDifficulty(),
            'type' => $word->getType(),
        ], $words);

        return $this->json($data);
    }

    #[Route('', methods: ['POST'])]
    public function addWord(Request $request, 
        WordRepository $wordRepository,
        WordProgressRepository $progressRepository,
        EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();
        if (!$user) {
            $user = $em->getRepository(User::class)->find(7); // fallback pour test
            if (!$user) {
                return $this->json(['error' => 'No user in database'], 400);
            }
        }

        $word = new Word(
            $user,
            $data['word'] ?? '',
            $data['definition'] ?? '',
            $data['example'] ?? '',
            $data['difficulty'] ?? '',
            $data['type'] ?? '',
        );

        $em->persist($word);
        $em->flush();

        return $this->json([
            'status' => 'created',
            'id' => $word->getId()
        ]);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(WordRepository $wordRepository, int $id): JsonResponse
    {
        $word = $wordRepository->find($id);
        if (!$word) {
            return $this->json(['error' => 'Word not found'], 404);
        }

        return $this->json([
            'id' => $word->getId(),
            'word' => $word->getValue(),
            'definition' => $word->getDefinition(),
            'example' => $word->getExampleSentence(),
            'difficulty' => $word->getDifficulty(),
            'type' => $word->getType(),
        ]);
    }
}