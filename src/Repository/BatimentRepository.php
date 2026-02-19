<?php

namespace App\Repository;

use App\Entity\Batiment;
use App\Entity\Organisation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Batiment>
 */
class BatimentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Batiment::class);
    }

    /**
     * @return Batiment[] Returns an array of Batiment objects
     */
    public function findBatiments(Organisation $organisation, ?bool $actif = null): array
    {
        $qb = $this->createQueryBuilder('b')
            ->innerJoin('b.site', 's')
            ->addSelect('s')
            ->andWhere('s.organisation = :organisation')
            ->setParameter('organisation', $organisation)
            ->orderBy('s.nom', 'ASC')
            ->addOrderBy('b.nom', 'ASC');

        if ($actif !== null) {
            $qb
                ->andWhere('b.actif = :actif')
                ->setParameter('actif', $actif);
        }

        return $qb->getQuery()->getResult();
    }

    //    /**
    //     * @return Batiment[] Returns an array of Batiment objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('b.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Batiment
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
