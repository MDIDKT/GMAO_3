<?php

namespace App\DataFixtures;

use App\Entity\Batiment;
use App\Entity\Organisation;
use App\Entity\Site;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class Jour6Fixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $organisations = $manager->getRepository(Organisation::class)->findAll();

        if ($organisations === []) {
            $organisation = (new Organisation())
                ->setNom('Organisation Demo')
                ->setActif(true);
            $manager->persist($organisation);
            $organisations = [$organisation];
        }

        foreach ($organisations as $organisation) {
            $sites = $this->ensureSites($manager, $organisation);
            $this->ensureBatiments($manager, $organisation, $sites);
        }

        $manager->flush();
    }

    /**
     * @return list<Site>
     */
    private function ensureSites(ObjectManager $manager, Organisation $organisation): array
    {
        $existingSites = $manager->getRepository(Site::class)->findBy(
            ['organisation' => $organisation],
            ['id' => 'ASC']
        );

        if (\count($existingSites) >= 3) {
            return \array_values($existingSites);
        }

        $needed = 3 - \count($existingSites);
        $startingIndex = \count($existingSites) + 1;

        for ($i = 0; $i < $needed; $i++) {
            $siteNumber = $startingIndex + $i;
            $site = (new Site())
                ->setNom(sprintf('J6 Site %d', $siteNumber))
                ->setCodePostal(sprintf('7500%d', $siteNumber))
                ->setVille('Paris')
                ->setActif($siteNumber % 2 !== 0)
                ->setOrganisation($organisation);

            $manager->persist($site);
            $existingSites[] = $site;
        }

        return \array_values($existingSites);
    }

    /**
     * @param list<Site> $sites
     */
    private function ensureBatiments(ObjectManager $manager, Organisation $organisation, array $sites): void
    {
        $countByOrganisation = (int) $manager->createQuery(
            'SELECT COUNT(b.id)
             FROM App\Entity\Batiment b
             INNER JOIN b.site s
             WHERE s.organisation = :organisation'
        )
            ->setParameter('organisation', $organisation)
            ->getSingleScalarResult();

        if ($countByOrganisation >= 6) {
            return;
        }

        $toCreate = 6 - $countByOrganisation;
        $startNumber = $countByOrganisation + 1;

        for ($i = 0; $i < $toCreate; $i++) {
            $batimentNumber = $startNumber + $i;
            $site = $sites[$i % \count($sites)];

            $batiment = (new Batiment())
                ->setNom(sprintf('J6 Batiment %d', $batimentNumber))
                ->setEtage($batimentNumber % 2 === 0 ? 'R+1' : 'RDC')
                ->setActif($batimentNumber % 2 !== 0)
                ->setSite($site);

            $manager->persist($batiment);
        }
    }
}
