<?php

namespace App\Repository;

use App\Entity\Intervention;
use App\Entity\Organisation;
use App\Entity\User;
use App\Enum\StatutIntervention;
use DateTime;
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

    public function countInterventionsDuJour(Organisation $organisation)
    {
        $qb = $this->createQueryBuilder('i');

        if ($organisation !== null) {
            $qb->andWhere('i.organisation = :organisation')
                ->setParameter('organisation', $organisation);
        }

        $qb->select('COUNT(i.id)');
        $qb->andWhere('i.datePlanifiee >= :dateToday')
            ->setParameter('dateToday', new DateTime('today'));
        $qb->andWhere('i.datePlanifiee < :dateTomorrow')
            ->setParameter('dateTomorrow', new DateTime('tomorrow'));
        return $qb->getQuery()->getSingleScalarResult();
    }

    public function countInterventionsEnRetard(Organisation $organisation)
    {
        $qb = $this->createQueryBuilder('i');
        if ($organisation !== null) {
            $qb->andWhere('i.organisation = :organisation')
                ->setParameter('organisation', $organisation);
        }
        $qb->select('COUNT(i.id)');
        $qb->andWhere('i.datePlanifiee < :dateToday')
            ->setParameter('dateToday', new DateTime('today'));
        $qb->andWhere('i.statut NOT IN (:exclus)')
            ->setParameter('exclus', [StatutIntervention::TERMINEE, StatutIntervention::VALIDEE]);
        return $qb->getQuery()->getSingleScalarResult();

    }

    public function countAPlanifier(Organisation $organisation): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.organisation = :organisation')
            ->setParameter('organisation', $organisation)
            ->andWhere('i.statut = :statut')
            ->setParameter('statut', StatutIntervention::A_PLANIFIER)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countEnCours(Organisation $organisation): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.organisation = :organisation')
            ->setParameter('organisation', $organisation)
            ->andWhere('i.statut = :statut')
            ->setParameter('statut', StatutIntervention::EN_COURS)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countTerminees(Organisation $organisation): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.organisation = :organisation')
            ->setParameter('organisation', $organisation)
            ->andWhere('i.statut IN (:statuts)')
            ->setParameter('statuts', [StatutIntervention::TERMINEE, StatutIntervention::VALIDEE])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countTotal(Organisation $organisation): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.organisation = :organisation')
            ->setParameter('organisation', $organisation)
            ->getQuery()
            ->getSingleScalarResult();
    }

}
