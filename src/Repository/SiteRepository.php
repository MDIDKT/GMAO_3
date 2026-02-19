<?php

namespace App\Repository;

use App\Entity\Organisation;
use App\Entity\Site;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Site>
 */
class SiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Site::class);
    }

    /**
     * @return Site[] Returns an array of Site objects
     */
    public function findOrganisation(Organisation $organisation, ?bool $actif = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.organisation = :organisation')
            ->setParameter('organisation', $organisation)
            ->orderBy('e.nom', 'ASC');

        if ($actif !== null) {
            $qb
                ->andWhere('e.actif = :actif')
                ->setParameter('actif', $actif);
        }

        return $qb->getQuery()->getResult();
    }
    //    /**
    //     * @return Site[] Returns an array of Site objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Site
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
