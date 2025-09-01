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
        return $this->createQueryBuilder('tournament')
            ->select('category.id, category.name, category.attempt_limit, category_group.description , category_group.id AS group_id')
            ->join('tournament.categories', 'category')
            ->join('category.category_group', 'category_group')
            ->where('tournament.id = :tournamentId')
            ->setParameter('tournamentId', $tournamentId)  
            ->groupBy('category.id, category_group.id')
            ->orderBy('category_group.id')
            ->getQuery()
            ->getResult();
    }
    public function getCompetitorsWithScores(int $tournamentId, array $categories): array
    {
        $categoryIds = array_map(fn($c) => $c['id'], $categories);

        return $this->createQueryBuilder('tournament')
            ->select('CONCAT(competitor.first_name, \' \',  competitor.last_name) AS competitor_name , category.id AS category_id, attempt.attempt_number AS attempt_number, SUM(attemptScore.score) AS score')
            ->join('tournament.categories', 'category')
            ->join('category.attempts', 'attempt')
            ->join('attempt.competitor', 'competitor')
            ->join('attempt.attemptScores', 'attemptScore')
            ->where('tournament.id = :tournamentId')
            ->andWhere('category.id IN (:categoryIds)')
            ->setParameter('tournamentId', $tournamentId)
            ->setParameter('categoryIds', $categoryIds)
            ->groupBy('category.id, competitor.id, attempt.id')
            ->orderBy('competitor.id, category.id, attempt.attempt_number')
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
    {
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
}
