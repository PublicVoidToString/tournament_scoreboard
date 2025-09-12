<?php

namespace App\Repository;

use App\Entity\CategoryGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CategoryGroup>
 */
class CategoryGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategoryGroup::class);
    }

    //    /**
    //     * @return CategoryGroup[] Returns an array of CategoryGroup objects
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

    //    public function findOneBySomeField($value): ?CategoryGroup
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function getCategoryGroups(int $tournamentId): array{
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT * FROM category_group;
        ";
        return $conn->fetchAllAssociative($sql, ['tournamentId' => $tournamentId]);
    }




}
