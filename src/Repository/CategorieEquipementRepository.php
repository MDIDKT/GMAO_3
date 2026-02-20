<?php

namespace App\Repository;

use App\Entity\CategorieEquipement;
use App\Entity\Organisation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CategorieEquipement>
 */
class CategorieEquipementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategorieEquipement::class);
    }

    /**
     * @return CategorieEquipement[]
     */
    public function findByOrganisation(Organisation $organisation): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.organisation = :organisation')
            ->setParameter('organisation', $organisation)
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return CategorieEquipement[] Returns an array of CategorieEquipement objects
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

    //    public function findOneBySomeField($value): ?CategorieEquipement
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
