<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository;

use App\Entity\Equipement;
use App\Entity\Site;
use App\Entity\User;
use App\Enum\StatutEquipement;
use App\Repository\EquipementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class EquipementRepositoryTest extends KernelTestCase
{
    public function testFindByFiltersIncludesEquipmentWithoutBuilding(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@gmao.fr']);

        self::assertInstanceOf(User::class, $user, 'Utilisateur fixture introuvable.');
        self::assertNotNull($user->getOrganisation(), 'Organisation introuvable pour l utilisateur fixture.');

        $site = $entityManager->getRepository(Site::class)->findOneBy([
            'organisation' => $user->getOrganisation(),
            'actif' => true,
        ]);

        self::assertInstanceOf(Site::class, $site, 'Site actif introuvable pour l organisation fixture.');

        $equipement = (new Equipement())
            ->setNom('Test equipement sans batiment ' . bin2hex(random_bytes(4)))
            ->setMarque('Test')
            ->setModele('SansBatiment')
            ->setNumeroDeSerie('TMP-' . bin2hex(random_bytes(6)))
            ->setStatut(StatutEquipement::EN_SERVICE)
            ->setActif(true)
            ->setSite($site)
            ->setOrganisation($user->getOrganisation());

        $entityManager->persist($equipement);
        $entityManager->flush();

        try {
            $repository = $container->get(EquipementRepository::class);
            $results = $repository->findByFilters($user->getOrganisation(), $site, null, null, true);
            $ids = array_map(static fn (Equipement $item): ?int => $item->getId(), $results);

            self::assertContains($equipement->getId(), $ids);
        } finally {
            $managedEquipement = $equipement->getId() !== null ? $entityManager->find(Equipement::class, $equipement->getId()) : null;
            if ($managedEquipement instanceof Equipement) {
                $entityManager->remove($managedEquipement);
                $entityManager->flush();
            }
        }
    }
}
