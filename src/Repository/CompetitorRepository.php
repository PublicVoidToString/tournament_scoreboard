<?php

namespace App\Repository;

use App\Entity\Competitor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Competitor>
 */
class CompetitorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Competitor::class);
    }

    //    /**
    //     * @return Competitor[] Returns an array of Competitor objects
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

    //    public function findOneBySomeField($value): ?Competitor
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findAll(): array{
        return $this->createQueryBuilder('competitor')
            ->select('competitor.id, competitor.first_name, competitor.last_name, association.name as association_name')
            ->leftJoin('competitor.association', 'association')
            ->orderBy('competitor.id')
            ->getQuery()
            ->getResult();
    }

    public function findFromTournament(int $tournamentId): array{
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                competitor.id, 
                competitor.association_id,
                competitor.first_name, 
                competitor.last_name,
				association.name AS association
            FROM competitor
                LEFT JOIN attempt ON attempt.competitor_id = competitor.id
                LEFT JOIN category ON attempt.category_id = category.id
    			LEFT JOIN association ON competitor.association_id = association.id
			WHERE category.tournament_id = :tournamentId
			GROUP BY competitor.id, association.name
            ORDER BY competitor.id
        ";
        return $conn->fetchAllAssociative($sql, ['tournamentId' => $tournamentId]);
    }

    public function getAll(): array{
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                competitor.id, 
                competitor.association_id,
                competitor.first_name, 
                competitor.last_name,
				association.name AS association
            FROM competitor
    			LEFT JOIN association ON competitor.association_id = association.id
			GROUP BY competitor.id, association.name
            ORDER BY competitor.id
        ";
        return $conn->fetchAllAssociative($sql);
    }

    public function getAttempsFromTournament($tournamentId): array{
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                competitor.id AS competitor_id,
                category.id AS category_id,
                COUNT(attempt.id) AS attempts
            FROM competitor
            CROSS JOIN (
                SELECT * 
                FROM category
                WHERE category.tournament_id = :tournamentId
            ) AS category
            LEFT JOIN attempt 
                ON attempt.competitor_id = competitor.id
                AND attempt.category_id = category.id
            GROUP BY
                competitor.id,
                category.id,
                category.name,
                category.initial_fee, 
                category.additional_fee, 
                category.attempt_limit
            ORDER BY
                competitor.id,
                category.id;
        ";
        return $conn->fetchAllAssociative($sql, ['tournamentId' => $tournamentId]);

    }

}
