<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CategorieEquipement;
use App\Entity\Equipement;
use App\Entity\Organisation;
use App\Entity\Site;
use App\Enum\StatutEquipement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @extends ServiceEntityRepository<Equipement>
 */

class EquipementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly PaginatorInterface $paginator)
    {
        parent::__construct($registry, Equipement::class);
    }

    /**
     * @return Equipement[] Returns an array of Equipement objects
     * En faisant une jointure avec le site et le batiment
     * En fonction de l'organisation et du statut
     */
    public function findSite(Organisation $organisation, ?bool $actif = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->innerJoin('e.batiment', 'b')
            ->innerJoin('b.site', 's')
            ->andWhere('s.organisation = :organisation')
            ->setParameter('organisation', $organisation);
        if (null !== $actif) {
            $qb->andWhere('e.actif = :actif')
                ->setParameter('actif', $actif);
        }
        return $qb->getQuery()->getResult();
    }

    /**
     * @param \App\Entity\Organisation $organisation
     * @param bool|null $actif
     * @return array
     */
    public function findCategorie(Organisation $organisation, ?bool $actif = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->innerJoin('e.batiment', 'b')
            ->innerJoin('b.site', 's')
            ->innerJoin('e.categorie', 'c')
            ->andWhere('s.organisation = :organisation')
            ->setParameter('organisation', $organisation);
        if (null !== $actif) {
            $qb->andWhere('e.actif = :actif')
                ->setParameter('actif', $actif);
        }
        return $qb->getQuery()->getResult();
    }

    /**
     * @param \App\Entity\Organisation $organisation
     * @param bool|null $actif
     * @return array
     */
    public function findStatut(Organisation $organisation, ?bool $actif = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->innerJoin('e.batiment', 'b')
            ->innerJoin('b.site', 's')
            ->andWhere('s.organisation = :organisation')
            ->setParameter('organisation', $organisation);
        if (null !== $actif) {
            $qb->andWhere('e.actif = :actif')
                ->setParameter('actif', $actif);
        }
        return $qb->getQuery()->getResult();
    }

    public function getQueryBuilderByFilters(
        Organisation $organisation,
        ?Site $site = null,
        ?CategorieEquipement $categorie = null,
        ?StatutEquipement $statut = null,
        ?bool $actif = null
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('e')
            ->innerJoin('e.batiment', 'b')
            ->innerJoin('b.site', 's')
            ->leftJoin('e.categorie', 'c')
            ->andWhere('s.organisation = :organisation')
            ->setParameter('organisation', $organisation)
            ->orderBy('e.id', 'DESC');

        if (null !== $site) {
            $qb->andWhere('s = :site')
                ->setParameter('site', $site);
        }

        if (null !== $categorie) {
            $qb->andWhere('c = :categorie')
                ->setParameter('categorie', $categorie);
        }

        if (null !== $statut) {
            $qb->andWhere('e.statut = :statut')
                ->setParameter('statut', $statut);
        }

        if (null !== $actif) {
            $qb->andWhere('e.actif = :actif')
                ->setParameter('actif', $actif);
        }

        return $qb;
    }

    /**
     * @return Equipement[]
     */
    public function findByFilters(
        Organisation $organisation,
        ?Site $site = null,
        ?CategorieEquipement $categorie = null,
        ?StatutEquipement $statut = null,
        ?bool $actif = null
    ): array {
        return $this->getQueryBuilderByFilters($organisation, $site, $categorie, $statut, $actif)
            ->getQuery()
            ->getResult();
    }

    public function paginateEquipements(QueryBuilder $qb, int $page, int $limit): PaginationInterface
    {
        return $this->paginator->paginate($qb, $page, $limit);
    }

    public function countActive(Organisation $organisation): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->innerJoin('e.batiment', 'b')
            ->innerJoin('b.site', 's')
            ->andWhere('s.organisation = :organisation')
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
            ->innerJoin('e.batiment', 'b')
            ->innerJoin('b.site', 's')
            ->andWhere('s.organisation = :organisation')
            ->setParameter('organisation', $organisation)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
