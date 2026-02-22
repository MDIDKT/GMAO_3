<?php

namespace App\Repository;

use App\Entity\Demande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Demande>
 */
class DemandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Demande::class);
    }

    //    /**
    //     * @return Demande[] Returns an array of Demande objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('d.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    public function findNumDemande(): ?array
    {
        $annee = date('Y');
        $pattern = 'DEM-' . $annee . '-%';

        return $this->createQueryBuilder('d')
            ->select('d.numero')
            ->andWhere('d.numero LIKE :pattern')
            ->setParameter('pattern', $pattern)
            ->orderBy('d.numero', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
