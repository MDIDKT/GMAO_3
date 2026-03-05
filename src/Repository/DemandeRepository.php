<?php

    namespace App\Repository;

    use App\Entity\Demande;
    use App\Entity\Organisation;
    use App\Entity\Site;
    use App\Entity\User;
    use App\Enum\Priorite;
    use App\Enum\StatutDemande;
    use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
    use Doctrine\ORM\QueryBuilder;
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

        public function findByFilters(?Organisation $organisation, ?Site $site, ?StatutDemande $statut, ?Priorite $priorite, ?string $search, ?User $user = null): array
        {
            return $this->getQueryBuilderByFilters($organisation, $site, $statut, $priorite, $search, $user)
                ->getQuery()
                ->getResult();
        }

        public function getQueryBuilderByFilters(?Organisation $organisation, ?Site $site, ?StatutDemande $statut, ?Priorite $priorite, ?string $search, ?User $user = null): QueryBuilder
        {
            $qb = $this->createQueryBuilder('d');

            if ($organisation !== null) {
                $qb->andWhere('d.organisation = :organisation')
                    ->setParameter('organisation', $organisation);
            }

            if ($user !== null) {
                $qb->andWhere('d.user = :user')
                    ->setParameter('user', $user);
            }

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

            if ($search !== null && $search !== '') {
                $qb->andWhere('d.titre LIKE :search OR d.description LIKE :search')
                    ->setParameter('search', '%' . $search . '%');
            }

            $qb->orderBy('d.createdAt', 'DESC');

            return $qb;
        }

        public function paginateDemandes(QueryBuilder $qb, int $page, int $limit): PaginationInterface
        {
            return $this->paginator->paginate($qb, $page, $limit);
        }

        public function countP1Ouvertes(Organisation $organisation)
        {

            $qb = $this->createQueryBuilder('d');
            if ($organisation !== null) {
                $qb->andWhere('d.organisation = :organisation')
                    ->setParameter('organisation', $organisation);
            }

            $qb->select('COUNT(d.id)');
            $qb->andWhere('d.priorite = :priorite')
                ->setParameter('priorite', Priorite::P1_URGENTE);
            $qb->andWhere('d.statut NOT IN (:exclus)')
                ->setParameter('exclus', [StatutDemande::CLOTURE, StatutDemande::REJETEE]);

            return $qb->getQuery()->getSingleScalarResult();
        }

        public function countAQualifier(Organisation $organisation): int
        {
            $qb = $this->createQueryBuilder('d');
            if ($organisation !== null) {
                $qb->andWhere('d.organisation = :organisation')
                    ->setParameter('organisation', $organisation);
            }

            $qb->select('COUNT(d.id)');
            $qb->andWhere('d.statut = :statut')
                ->setParameter('statut', StatutDemande::A_QUALIFIER);

            return $qb->getQuery()->getSingleScalarResult();

        }

        public function countUrgent(Organisation $organisation): int
        {
            return (int) $this->createQueryBuilder('d')
                ->select('COUNT(d.id)')
                ->andWhere('d.organisation = :organisation')
                ->setParameter('organisation', $organisation)
                ->andWhere('d.priorite = :priorite')
                ->setParameter('priorite', Priorite::P1_URGENTE)
                ->andWhere('d.statut NOT IN (:exclus)')
                ->setParameter('exclus', [StatutDemande::CLOTURE, StatutDemande::REJETEE])
                ->getQuery()
                ->getSingleScalarResult();
        }

        public function countOpen(Organisation $organisation): int
        {
            return (int) $this->createQueryBuilder('d')
                ->select('COUNT(d.id)')
                ->andWhere('d.organisation = :organisation')
                ->setParameter('organisation', $organisation)
                ->andWhere('d.statut NOT IN (:exclus)')
                ->setParameter('exclus', [StatutDemande::CLOTURE, StatutDemande::REJETEE])
                ->getQuery()
                ->getSingleScalarResult();
        }

        public function countClosed(Organisation $organisation): int
        {
            return (int) $this->createQueryBuilder('d')
                ->select('COUNT(d.id)')
                ->andWhere('d.organisation = :organisation')
                ->setParameter('organisation', $organisation)
                ->andWhere('d.statut IN (:statuts)')
                ->setParameter('statuts', [StatutDemande::CLOTURE, StatutDemande::REJETEE])
                ->getQuery()
                ->getSingleScalarResult();
        }

        public function countTotal(Organisation $organisation): int
        {
            return (int) $this->createQueryBuilder('d')
                ->select('COUNT(d.id)')
                ->andWhere('d.organisation = :organisation')
                ->setParameter('organisation', $organisation)
                ->getQuery()
                ->getSingleScalarResult();
        }
    }
