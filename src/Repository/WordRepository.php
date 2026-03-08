<?php

namespace App\Repository;

use App\Infrastructure\Persistence\Doctrine\Word;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Word::class);
    }

    // 🟢 Méthodes personnalisées possibles, par exemple :
    /*
    public function findByUser($user)
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
    */

    public function findNextWordForUser(int $userId): ?Word
    {
        return $this->createQueryBuilder('w')
            ->leftJoin('App\Infrastructure\Persistence\Doctrine\WordProgress', 'p', 'WITH', 'p.word = w.id AND p.user = :user')
            ->setParameter('user', $userId)
            ->orderBy('p.correct', 'ASC')
            ->addOrderBy('p.lastSeenAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
    
 public function findRandomWords(int $limit = 4): array
{
    $conn = $this->getEntityManager()->getConnection();

    $limit = (int) $limit;

    $sql = "
        SELECT id
        FROM word
        ORDER BY RAND()
        LIMIT $limit
    ";

    $ids = $conn->executeQuery($sql)->fetchFirstColumn();

    if (!$ids) {
        return [];
    }

    return $this->createQueryBuilder('w')
        ->where('w.id IN (:ids)')
        ->setParameter('ids', $ids)
        ->getQuery()
        ->getResult();
}
}