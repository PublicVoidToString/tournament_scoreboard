<?php

namespace App\Repository;

use App\Entity\Tournament;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tournament>
 */
class TournamentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tournament::class);
    }

    public function getCategories(int $tournamentId): array{
    $conn = $this->getEntityManager()->getConnection();

    $sql = "
        WITH attempt_scores AS (
            SELECT 
                a.id AS attempt_id,
                a.category_id,
                SUM(s.score) AS total_score
            FROM attempt a
            JOIN attempt_score s 
                ON s.attempt_id = a.id
            GROUP BY a.id, a.category_id
        ),
        ranked_scores AS (
            SELECT 
                category_id,
                total_score,
                DENSE_RANK() OVER (PARTITION BY category_id ORDER BY total_score DESC) AS rnk
            FROM attempt_scores
        )
        SELECT 
            c.id,
            c.name,
            c.attempt_limit,
            cg.description,
            c.category_group_id AS group_id,
            COALESCE(rs.total_score, 1) AS third_best_score,
        	c.initial_fee,
        	c.additional_fee
        FROM category c
        LEFT JOIN category_group cg 
            ON c.category_group_id = cg.id
        LEFT JOIN ranked_scores rs
            ON rs.category_id = c.id AND rs.rnk = 3
        ORDER BY c.category_group_id, c.id;
    ";

    return $conn->fetchAllAssociative($sql, ['tournamentId' => $tournamentId]);
    }
    public function getAttempts(int $tournamentId): array{
        return $this->createQueryBuilder('tournament')
            ->select('
            attempt.id AS attempt_id,
            competitor.id AS competitor_id,
            category.id AS category_id,
            category.initial_fee,
            category.additional_fee
            ')
            ->leftjoin('tournament.categories', 'category')
            ->join('category.attempts', 'attempt')
            ->join('attempt.competitor', 'competitor')
            ->where('tournament.id = :tournamentId')
            ->setParameter('tournamentId', $tournamentId)  
            ->getQuery()
            ->getResult();
    }
    
    public function getCompetitorsWithScores(int $tournamentId, array $categories): array
    {
        $categoryIds = array_map(fn($c) => $c['id'], $categories);

        return $this->createQueryBuilder('tournament')
            ->select('CONCAT(competitor.first_name, \' \',  competitor.last_name) AS competitor_name , category.id AS category_id, SUM(attemptScore.score) AS score')
            ->join('tournament.categories', 'category')
            ->join('category.attempts', 'attempt')
            ->join('attempt.competitor', 'competitor')
            ->join('attempt.attemptScores', 'attemptScore')
            ->where('tournament.id = :tournamentId')
            ->andWhere('category.id IN (:categoryIds)')
            ->setParameter('tournamentId', $tournamentId)
            ->setParameter('categoryIds', $categoryIds)
            ->groupBy('category.id, competitor.id, attempt.id')
            ->orderBy('competitor.id, category.id')
            ->getQuery()
            ->getResult();
    }
    public function getCompetitors(int $tournamentId): array{
        return $this->createQueryBuilder('tournament')
            ->select('competitor.id')
            ->join('tournament.categories', 'category')
            ->join('category.attempts', 'attempt')
            ->join('attempt.competitor', 'competitor')
            ->where('tournament.id = :tournamentId')
            ->setParameter('tournamentId', $tournamentId)  
            ->groupBy('competitor.id')
            ->getQuery()
            ->getResult();
    }

    public function getScoreboardData(int $tournamentId): array
    { //TODO IS SERACHING ALL TOURNAMENTS SHOULD ONLY BE SEARCHING ONE
        return $this->createQueryBuilder('tournament')
            ->select('
                category.id AS category_id,
                category.name AS category_name,
                competitor.id AS competitor_id,
                competitor.first_name,
                competitor.last_name,
                attempt.id AS attempt_id,
                SUM(s.score) AS attempt_score
            ')
            ->join('tournament.categories', 'category')
            ->join('category.attempts', 'attempt')
            ->join('attempt.competitor', 'competitor')
            ->join('attempt.attemptScores', 's')
            ->groupBy('category.id, category.name, competitor.id, competitor.first_name, competitor.last_name, attempt.id')
            ->getQuery()
            ->getResult();
    }

public function getEmptyAttempts(int $tournamentId): array
{
    return $this->createQueryBuilder('tournament')
        ->select('
            attempt.id AS attempt_id,
            category.id AS category_id,
            category.name AS category_name,
            category.attempt_limit AS category_attempt_limit,
            competitor.id AS competitor_id,
            CONCAT(competitor.first_name, \' \', competitor.last_name) AS competitor_name
        ')
        ->leftJoin('tournament.categories', 'category')
        ->join('category.attempts', 'attempt')
        ->join('attempt.competitor', 'competitor')
        ->leftJoin('attempt.attemptScores', 'score')
        ->where('tournament.id = :tournamentId')
        ->andWhere('score.id IS NULL')
        ->setParameter('tournamentId', $tournamentId)
        ->orderBy('attempt.id')
        ->getQuery()
        ->getResult();
}
    
}
