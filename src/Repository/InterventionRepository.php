<?php

namespace App\Repository;

use App\Entity\Intervention;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Intervention>
 */
class InterventionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
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

    public function findByFilters($organisation, $user = null): array
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

        $qb->orderBy('d.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

}
