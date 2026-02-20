<?php

namespace App\DataFixtures;

use App\Entity\Batiment;
use App\Entity\Organisation;
use App\Entity\Site;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class BatimentFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $organisations = $manager->getRepository(Organisation::class)->findBy([], ['id' => 'ASC']);

        foreach ($organisations as $organisation) {
            $this->ensureBatiments($manager, $organisation);
        }

        $manager->flush();
    }

    /**
     * @return list<class-string>
     */
    public function getDependencies(): array
    {
        return [SiteFixtures::class];
    }

    private function ensureBatiments(ObjectManager $manager, Organisation $organisation): void
    {
        $sites = $manager->getRepository(Site::class)->findBy(
            ['organisation' => $organisation],
            ['id' => 'ASC']
        );

        if ($sites === []) {
            return;
        }

        $existingBatiments = $manager->createQuery(
            'SELECT b
             FROM App\Entity\Batiment b
             INNER JOIN b.site s
             WHERE s.organisation = :organisation
             ORDER BY b.id ASC'
        )
            ->setParameter('organisation', $organisation)
            ->getResult();

        foreach ($existingBatiments as $index => $batiment) {
            $label = $index + 1;
            $defaultSite = $sites[$index % count($sites)];

            if ($batiment->getNom() === null || trim($batiment->getNom()) === '') {
                $batiment->setNom(sprintf('Batiment %d', $label));
            }

            if ($batiment->getEtage() === null || trim($batiment->getEtage()) === '') {
                $batiment->setEtage($label % 2 === 0 ? 'R+1' : 'RDC');
            }

            if ($batiment->isActif() === null) {
                $batiment->setActif(true);
            }

            if ($batiment->getSite() === null) {
                $batiment->setSite($defaultSite);
            }

            $manager->persist($batiment);
        }

        if (count($existingBatiments) >= 6) {
            return;
        }

        $toCreate = 6 - count($existingBatiments);
        $startNumber = count($existingBatiments) + 1;

        for ($i = 0; $i < $toCreate; $i++) {
            $batimentNumber = $startNumber + $i;
            $site = $sites[$i % count($sites)];

            $batiment = (new Batiment())
                ->setNom(sprintf('Batiment %d', $batimentNumber))
                ->setEtage($batimentNumber % 2 === 0 ? 'R+1' : 'RDC')
                ->setActif($batimentNumber % 2 !== 0)
                ->setSite($site);

            $manager->persist($batiment);
        }
    }
}
