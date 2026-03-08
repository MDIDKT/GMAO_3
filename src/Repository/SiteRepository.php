<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Organisation;
use App\Entity\Site;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @extends ServiceEntityRepository<Site>
 */
class SiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly PaginatorInterface $paginator)
    {
        parent::__construct($registry, Site::class);
    }

    public function getQueryBuilderByOrganisation(Organisation $organisation, ?bool $actif = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.organisation = :organisation')
            ->setParameter('organisation', $organisation)
            ->orderBy('e.nom', 'ASC');

        if ($actif !== null) {
            $qb->andWhere('e.actif = :actif')
                ->setParameter('actif', $actif);
        }

        return $qb;
    }

    /**
     * @return Site[] Returns an array of Site objects
     */
    public function findOrganisation(Organisation $organisation, ?bool $actif = null): array
    {
        return $this->getQueryBuilderByOrganisation($organisation, $actif)
            ->getQuery()
            ->getResult();
    }

    public function paginateSites(QueryBuilder $qb, int $page, int $limit): PaginationInterface
    {
        return $this->paginator->paginate($qb, $page, $limit);
    }

    public function countActive(Organisation $organisation): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.organisation = :organisation')
            ->setParameter('organisation', $organisation)
            ->andWhere('e.actif = :actif')
            ->setParameter('actif', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countTotal(Organisation $organisation): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.organisation = :organisation')
            ->setParameter('organisation', $organisation)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
