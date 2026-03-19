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
use DateTime;

#[Route('/api/wordds')]
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
            'tags' => $word->getTags(),
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
            $data['tags'] ?? '',
        );

        $em->persist($word);
        $em->flush();

        return $this->json([
            'status' => 'created',
            'id' => $word->getId()
        ]);
    }   

    #[Route('/progress', methods: ['GET'])]
    public function show(WordProgressRepository $wordProgressRepository): JsonResponse
    {
        $progress = $wordProgressRepository->findAll();
        
        $data = array_map(fn(WordProgress $progress) => [
            'id' => $progress->getId(),
            'word' => $progress->getWord()->getId(),
            'date' => $progress->getLastSeenAt(),
            'score' => $progress->isScore(),
            'userid' => $progress->getUser(),
        ], $progress);

        return $this->json($data);
    }



}