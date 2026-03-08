<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CategorieEquipement;
use App\Entity\Organisation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @extends ServiceEntityRepository<CategorieEquipement>
 */
class CategorieEquipementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly PaginatorInterface $paginator)
    {
        parent::__construct($registry, CategorieEquipement::class);
    }

    public function getQueryBuilderByOrganisation(Organisation $organisation): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.organisation = :organisation')
            ->setParameter('organisation', $organisation)
            ->orderBy('c.nom', 'ASC');
    }

    /**
     * @return CategorieEquipement[]
     */
    public function findByOrganisation(Organisation $organisation): array
    {
        return $this->getQueryBuilderByOrganisation($organisation)
            ->getQuery()
            ->getResult();
    }

    public function paginateCategories(QueryBuilder $qb, int $page, int $limit): PaginationInterface
    {
        return $this->paginator->paginate($qb, $page, $limit);
    }
}
