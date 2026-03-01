<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    //    /**
    //     * @return Category[] Returns an array of Category objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Category
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function getCategories(int $tournamentId): array{
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            WITH attempt_totals AS (
                SELECT 
                    a.id           AS attempt_id,
                    a.competitor_id,
                    a.category_id,
                    SUM(s.score)   AS total_score
                FROM attempt a
                JOIN attempt_score s ON s.attempt_id = a.id
                GROUP BY a.id, a.competitor_id, a.category_id
            ),
            competitor_best AS (
                SELECT
                    competitor_id,
                    category_id,
                    MAX(total_score) AS best_score
                FROM attempt_totals
                GROUP BY competitor_id, category_id
            ),
            ranked_scores AS (
                SELECT
                    category_id,
                    best_score,
                    ROW_NUMBER() OVER (PARTITION BY category_id ORDER BY best_score DESC) AS rn
                FROM competitor_best
            )
            SELECT 
                c.id,
                c.name,
                c.attempt_limit,
                cg.description,
                cg.abbreviation,
                c.category_group_id AS group_id,
                COALESCE(rs.best_score, 1) AS third_best_score,
                c.initial_fee,
                c.additional_fee,
                cg.scores_per_attempt
            FROM category c
            LEFT JOIN category_group cg ON c.category_group_id = cg.id
            LEFT JOIN ranked_scores rs ON rs.category_id = c.id AND rs.rn = 3
            WHERE c.tournament_id = :tournamentId
            ORDER BY c.category_group_id, c.id;
            ";
        return $conn->fetchAllAssociative($sql, ['tournamentId' => $tournamentId]);
    }

}
