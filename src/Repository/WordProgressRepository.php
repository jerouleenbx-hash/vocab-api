<?php

namespace App\Repository;

use App\Infrastructure\Persistence\Doctrine\WordProgress;
use App\Infrastructure\Persistence\Doctrine\User;
use App\Infrastructure\Persistence\Doctrine\Word;
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
    public function setCorrect(User $user, Word $word, bool $correct): WordProgress
    {
        $progress = $this->findProgress($user, $word);

        if (!$progress) {
            $progress = new WordProgress($user, $word);
            $this->_em->persist($progress);
        }

        $progress->setCorrect($correct);
        $progress->setLastSeenAt(new \DateTimeImmutable());

        $this->_em->flush();

        return $progress;
    }

    /**
     * Récupère tous les WordProgress d'un utilisateur
     */
    public function findAllByUser(User $user): array
    {
        return $this->createQueryBuilder('wp')
            ->andWhere('wp.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}