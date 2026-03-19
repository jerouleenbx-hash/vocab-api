<?php

namespace App\Repository;

use App\Entity\Word;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr;

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

    public function findByFilters(?string $level, ?string $tag): array
    {
        $qb = $this->createQueryBuilder('w');

        if ($level && $level !== 'All') {
            $qb->andWhere('w.difficulty = :level')
            ->setParameter('level', $level);
        }

        if ($tag) {
            $qb->andWhere('w.tags LIKE :tag')
            ->setParameter('tag', '%' . $tag . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function findNextWordForUser(int $userId): ?Word
    {
        return $this->createQueryBuilder('w')
            ->leftJoin('App\Entity\WordProgress', 'p', 'WITH', 'p.word = w.id AND p.user = :user')
            ->setParameter('user', $userId)
            ->orderBy('p.score', 'ASC')
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


    public function findAllTags(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "SELECT distinct tags FROM word";
    
        return $conn->executeQuery($sql)->fetchFirstColumn();
    }

    /**
     * Get ALL the words for one specific user
     */
    public function findAllByType(User $user): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT id, value, definition, type
            FROM word
            where user_id = :user
        ";
        
        $params = [
            'user' => $user->getId(),
        ];

        return $conn->executeQuery($sql, $params)->fetchAllAssociative();
    }


    /**
     * Get a list of words with (optional) difficulty and tag for one specific user
     */
    public function findByTagAndDifficulty(?string $difficulty, ?string $tag, User $user): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT distinct
                w.id,
                w.value,
                w.definition,
                w.difficulty as level,
                w.type,
                w.tags,
                w.example_sentence,
                wp.score,
                wp.stability,
                wp.reps,
                wp.lapses,
                wp.difficulty,
                wp.last_review
            FROM word w
            LEFT JOIN word_progress wp 
                ON wp.word_id = w.id 
                AND wp.user_id = :user
            WHERE 1 = 1
        ";

        $params = [
            'user' => $user->getId(),
        ];

        if ($tag) {
            $sql .= " AND w.tags LIKE :tag";
            $params['tag'] = '%' . $tag . '%';
        }

        if ($difficulty && $difficulty !== 'All') {
            $sql .= " AND w.difficulty = :difficulty";
            $params['difficulty'] = $difficulty;
        }

        $sql .= " ORDER BY w.difficulty ASC";

        return $conn->executeQuery($sql, $params)->fetchAllAssociative();
    }

    /**
     * Get a list of words with (optional) difficulty and tag for one specific user
     */
    public function findByDifficultyAndTagOrderedByScore(?string $difficulty, ?string $tag, User $user): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT distinct
                w.id,
                w.value,
                w.definition,
                w.difficulty,
                w.type,
                w.tags,
                w.example_sentence,
                wp.score
            FROM word w
            LEFT JOIN word_progress wp 
                ON wp.word_id = w.id 
                AND wp.user_id = :user
            WHERE 1 = 1
        ";

        $params = [
            'user' => $user->getId(),
        ];

        if ($tag) {
            $sql .= " AND w.tags LIKE :tag";
            $params['tag'] = '%' . $tag . '%';
        }

        if ($difficulty && $difficulty !== 'All') {
            $sql .= " AND w.difficulty = :difficulty";
            $params['difficulty'] = $difficulty;
        }

        $sql .= " ORDER BY wp.score DESC";

        return $conn->executeQuery($sql, $params)->fetchAllAssociative();
    }


    public function findQuizWordsFSRS(?string $difficulty, ?string $tag, int $userId): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT 
                w.id,
                w.value,
                w.definition,
                w.type,
                w.tags,
                w.difficulty,
                w.example_sentence,
                wp.stability,
                wp.difficulty AS user_difficulty,
                wp.next_review
            FROM word w
            LEFT JOIN word_progress wp 
                ON wp.word_id = w.id AND wp.user_id = :user
            WHERE 1=1
        ";

        $params = ['user' => $userId];

        if ($difficulty && $difficulty !== 'All') {
            $sql .= " AND w.difficulty = :difficulty";
            $params['difficulty'] = $difficulty;
        }

        if ($tag) {
            $sql .= " AND w.tags LIKE :tag";
            $params['tag'] = '%' . $tag . '%';
        }

        $sql .= "
            ORDER BY 
                wp.next_review IS NULL DESC,
                wp.next_review ASC,
                wp.stability ASC
            LIMIT 20
        ";

        return $conn->executeQuery($sql, $params)->fetchAllAssociative();
    }    
}