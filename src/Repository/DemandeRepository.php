<?php

    namespace App\Repository;

    use App\Entity\Demande;
    use App\Entity\Organisation;
    use App\Entity\Site;
    use App\Enum\Priorite;
    use App\Enum\StatutDemande;
    use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
    use Doctrine\Persistence\ManagerRegistry;
    use Knp\Component\Pager\Pagination\PaginationInterface;
    use Knp\Component\Pager\PaginatorInterface;

    /**
     * @extends ServiceEntityRepository<Demande>
     */
    class DemandeRepository extends ServiceEntityRepository
    {
        public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator)
        {
            parent::__construct($registry, Demande::class);
        }
        /**
         * @return Demande[]
         */
        public function findByOrganisation(Organisation $organisation): array
        {
            return $this->createQueryBuilder('d')
                ->andWhere('d.organisation = :org')
                ->setParameter('org', $organisation)
                ->orderBy('d.createdAt', 'DESC')
                ->getQuery()
                ->getResult();
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

        public function findByFilters(?Organisation $organisation, ?Site $site, ?StatutDemande $statut, ?Priorite $priorite, $search): array
        {
            $qb = $this->createQueryBuilder('d');
            if ($priorite !== null) {
                $qb->andWhere('d.priorite = :priorite')
                    ->setParameter('priorite', $priorite);
            }

            if ($site !== null) {
                $qb->andWhere('d.site = :site')
                    ->setParameter('site', $site);
            }

            if ($statut !== null) {
                $qb->andWhere('d.statut = :statut')
                    ->setParameter('statut', $statut);
            }
            if ($organisation !== null) {
                $qb->andWhere('d.organisation = :organisation')
                    ->setParameter('organisation', $organisation);
            }

            if ($search !== null) {
                $qb->andWhere('d.titre LIKE :search OR d.description LIKE :search')
                    ->setParameter('search', '%' . $search . '%');
            }
            return $qb->getQuery()->getResult();
        }

        public function paginateDemandes(int $page, int $limit): PaginationInterface
        {
            return $this->paginator->paginate(
                $this->createQueryBuilder('d'),
                $page,
                $limit
            );
        }
    }
