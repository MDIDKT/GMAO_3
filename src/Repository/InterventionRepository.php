<?php

namespace App\Repository;

use App\Entity\Intervention;
use App\Entity\Organisation;
use App\Entity\User;
use App\Enum\StatutIntervention;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @extends ServiceEntityRepository<Intervention>
 */
class InterventionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly PaginatorInterface $paginator)
    {
        parent::__construct($registry, Intervention::class);
    }

    public function findLastNumeroForPrefixAndYear(string $prefix, int $year): ?string
    {
        $pattern = sprintf('%s-%d-%%', $prefix, $year);

        $result = $this->createQueryBuilder('d')
            ->select('d.numero')
            ->andWhere('d.numero LIKE :pattern')
            ->setParameter('pattern', $pattern)
            ->orderBy('d.numero', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result['numero'] ?? null;
    }

    public function numeroExists(string $numero): bool
    {
        return (int)$this->createQueryBuilder('d')
                ->select('COUNT(d.id)')
                ->andWhere('d.numero = :numero')
                ->setParameter('numero', $numero)
                ->getQuery()
                ->getSingleScalarResult() > 0;
    }

    public function findByFilters(?Organisation $organisation, ?User $user = null, ?StatutIntervention $statut = null): array
    {
        return $this->getQueryBuilderByFilters($organisation, $user, $statut)
            ->getQuery()
            ->getResult();
    }

    public function getQueryBuilderByFilters(?Organisation $organisation, ?User $user = null, ?StatutIntervention $statut = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('d');

        if ($organisation !== null) {
            $qb->andWhere('d.organisation = :organisation')
                ->setParameter('organisation', $organisation);
        }

        if ($user !== null) {
            $qb->andWhere('d.technicien = :user')
                ->setParameter('user', $user);
        }

        if ($statut !== null) {
            $qb->andWhere('d.statut = :statut')
                ->setParameter('statut', $statut);
        }

        $qb->orderBy('d.createdAt', 'DESC');

        return $qb;
    }

    public function paginateInterventions(QueryBuilder $qb, int $page, int $limit): PaginationInterface
    {
        return $this->paginator->paginate($qb, $page, $limit);
    }

}
