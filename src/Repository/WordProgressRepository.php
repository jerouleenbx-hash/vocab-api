<?php

namespace App\Repository;

use App\Entity\WordProgress;
use App\Entity\User;
use App\Entity\Word;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WordProgressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WordProgress::class);
    }

    /**
     * Récupère le progrès d'un mot pour un utilisateur donné
     */
    public function findProgress(User $user, Word $word): ?WordProgress
    {
        return $this->findOneBy([
            'user' => $user,
            'word' => $word,
        ]);
    }

    /**
     * Met à jour ou crée un WordProgress
     */
    public function setScore(User $user, Word $word, int $score): WordProgress
    {
        $wordProgress =  findProgress($user, $word);
        if (!wordProgress) {
            $progress = new WordProgress($user, $word);
        }
        $progress->setScore($score);
        $progress->setLastSeenAt(new \DateTimeImmutable());
        $this->_em->flush();
        return $progress;
    }
}