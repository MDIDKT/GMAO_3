<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Batiment;
use App\Entity\Organisation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @extends ServiceEntityRepository<Batiment>
 */
class BatimentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly PaginatorInterface $paginator)
    {
        parent::__construct($registry, Batiment::class);
    }

    public function getQueryBuilderByOrganisation(Organisation $organisation, ?bool $actif = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('b')
            ->innerJoin('b.site', 's')
            ->addSelect('s')
            ->andWhere('s.organisation = :organisation')
            ->setParameter('organisation', $organisation)
            ->orderBy('s.nom', 'ASC')
            ->addOrderBy('b.nom', 'ASC');

        if ($actif !== null) {
            $qb->andWhere('b.actif = :actif')
                ->setParameter('actif', $actif);
        }

        return $qb;
    }

    /**
     * @return Batiment[] Returns an array of Batiment objects
     */
    public function findBatiments(Organisation $organisation, ?bool $actif = null): array
    {
        return $this->getQueryBuilderByOrganisation($organisation, $actif)
            ->getQuery()
            ->getResult();
    }

    public function paginateBatiments(QueryBuilder $qb, int $page, int $limit): PaginationInterface
    {
        return $this->paginator->paginate($qb, $page, $limit);
    }

    public function countActive(Organisation $organisation): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->innerJoin('b.site', 's')
            ->andWhere('s.organisation = :organisation')
            ->setParameter('organisation', $organisation)
            ->andWhere('b.actif = :actif')
            ->setParameter('actif', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countTotal(Organisation $organisation): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->innerJoin('b.site', 's')
            ->andWhere('s.organisation = :organisation')
            ->setParameter('organisation', $organisation)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
