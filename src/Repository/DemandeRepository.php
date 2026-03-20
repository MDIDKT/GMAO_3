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
    use DateTimeImmutable;

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

        public function findWithInterventions(int $id): ?Demande
        {
            return $this->createQueryBuilder('d')
                ->leftJoin('d.interventions', 'i')->addSelect('i')
                ->andWhere('d.id = :id')
                ->setParameter('id', $id)
                ->getQuery()
                ->getOneOrNullResult();
        }

        public function findByFilters(?Organisation $organisation, ?Site $site, ?StatutDemande $statut, ?Priorite $priorite, ?string $search, ?User $user = null): array
        {
            return $this->getQueryBuilderByFilters($organisation, $site, $statut, $priorite, $search, $user)
                ->getQuery()
                ->getResult();
        }

        public function getQueryBuilderByFilters(?Organisation $organisation, ?Site $site, ?StatutDemande $statut, ?Priorite $priorite, ?string $search, ?User $user = null): QueryBuilder
        {
            $qb = $this->createQueryBuilder('d')
                ->leftJoin('d.site', 's')->addSelect('s')
                ->leftJoin('d.batiment', 'b')->addSelect('b')
                ->leftJoin('d.equipement', 'eq')->addSelect('eq')
                ->leftJoin('d.user', 'u')->addSelect('u');

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
                ->setParameter('exclus', [StatutDemande::CLOTUREE, StatutDemande::REJETEE]);

            return $qb->getQuery()->getSingleScalarResult();
        }

        /**
         * @return Demande[]
         */
        public function findDashboardPriorityDemandes(Organisation $organisation, int $limit = 3): array
        {
            return $this->createQueryBuilder('d')
                ->leftJoin('d.site', 's')->addSelect('s')
                ->leftJoin('d.batiment', 'b')->addSelect('b')
                ->andWhere('d.organisation = :organisation')
                ->setParameter('organisation', $organisation)
                ->andWhere('d.priorite = :priorite')
                ->setParameter('priorite', Priorite::P1_URGENTE)
                ->andWhere('d.statut NOT IN (:exclus)')
                ->setParameter('exclus', [StatutDemande::CLOTUREE, StatutDemande::REJETEE])
                ->orderBy('d.createdAt', 'DESC')
                ->addOrderBy('d.id', 'DESC')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();
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
                ->setParameter('statut', StatutDemande::NOUVELLE);

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
                ->setParameter('exclus', [StatutDemande::CLOTUREE, StatutDemande::REJETEE])
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
                ->setParameter('exclus', [StatutDemande::CLOTUREE, StatutDemande::REJETEE])
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
                ->setParameter('statuts', [StatutDemande::CLOTUREE, StatutDemande::REJETEE])
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

        /**
         * KPI 1 : Demandes groupées par statut (filtré par site + période).
         * @return array<array{statut: string, total: int}>
         */
        public function countByStatut(Organisation $organisation, ?Site $site = null, ?DateTimeImmutable $dateDebut = null, ?DateTimeImmutable $dateFin = null): array
        {
            $qb = $this->createQueryBuilder('d')
                ->select('d.statut AS statut, COUNT(d.id) AS total')
                ->andWhere('d.organisation = :org')
                ->setParameter('org', $organisation)
                ->groupBy('d.statut');

            if ($site !== null) {
                $qb->andWhere('d.site = :site')->setParameter('site', $site);
            }
            if ($dateDebut !== null) {
                $qb->andWhere('d.createdAt >= :dateDebut')->setParameter('dateDebut', $dateDebut);
            }
            if ($dateFin !== null) {
                $qb->andWhere('d.createdAt <= :dateFin')->setParameter('dateFin', $dateFin);
            }

            return $qb->getQuery()->getResult();
        }

        /**
         * KPI 2 : Délai moyen de traitement (en heures) pour les demandes CLOTURE.
         */
        public function delaiMoyenTraitement(Organisation $organisation, ?Site $site = null, ?DateTimeImmutable $dateDebut = null, ?DateTimeImmutable $dateFin = null): ?float
        {
            $qb = $this->createQueryBuilder('d')
                ->select('AVG(TIMESTAMPDIFF(HOUR, d.createdAt, d.updatedAt)) AS delaiMoyen')
                ->andWhere('d.organisation = :org')
                ->setParameter('org', $organisation)
                ->andWhere('d.statut = :statut')
                ->setParameter('statut', StatutDemande::CLOTUREE);

            if ($site !== null) {
                $qb->andWhere('d.site = :site')->setParameter('site', $site);
            }
            if ($dateDebut !== null) {
                $qb->andWhere('d.createdAt >= :dateDebut')->setParameter('dateDebut', $dateDebut);
            }
            if ($dateFin !== null) {
                $qb->andWhere('d.createdAt <= :dateFin')->setParameter('dateFin', $dateFin);
            }

            $result = $qb->getQuery()->getSingleScalarResult();

            return $result !== null ? round((float) $result, 1) : null;
        }

        /**
         * KPI 4 : Demandes par site et priorité (tableau croisé).
         * @return array<array{siteNom: string, siteId: int, P1_URGENTE: int, P2_HAUTE: int, P3_NORMALE: int, P4_BASSE: int}>
         */
        public function countBySiteAndPriorite(Organisation $organisation, ?DateTimeImmutable $dateDebut = null, ?DateTimeImmutable $dateFin = null): array
        {
            $qb = $this->createQueryBuilder('d')
                ->select('s.id AS siteId, s.nom AS siteNom, d.priorite AS priorite, COUNT(d.id) AS total')
                ->join('d.site', 's')
                ->andWhere('d.organisation = :org')
                ->setParameter('org', $organisation)
                ->groupBy('s.id, s.nom, d.priorite')
                ->orderBy('s.nom', 'ASC');

            if ($dateDebut !== null) {
                $qb->andWhere('d.createdAt >= :dateDebut')->setParameter('dateDebut', $dateDebut);
            }
            if ($dateFin !== null) {
                $qb->andWhere('d.createdAt <= :dateFin')->setParameter('dateFin', $dateFin);
            }

            $rows = $qb->getQuery()->getResult();

            // Pivoter en tableau croisé
            $result = [];
            foreach ($rows as $row) {
                $siteNom = $row['siteNom'];
                if (!isset($result[$siteNom])) {
                    $result[$siteNom] = [
                        'siteNom' => $siteNom,
                        'P1_URGENTE' => 0,
                        'P2_HAUTE' => 0,
                        'P3_NORMALE' => 0,
                        'P4_BASSE' => 0,
                        'total' => 0,
                    ];
                }
                $prioriteKey = $row['priorite'] instanceof Priorite ? $row['priorite']->name : (string) $row['priorite'];
                if (isset($result[$siteNom][$prioriteKey])) {
                    $result[$siteNom][$prioriteKey] += (int) $row['total'];
                }
                $result[$siteNom]['total'] += (int) $row['total'];
            }

            return array_values($result);
        }
    }
