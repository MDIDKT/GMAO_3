<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Batiment;
use App\Entity\Organisation;
use App\Entity\Site;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class BatimentFixtures extends Fixture implements DependentFixtureInterface
{
    /** @var list<array{nom: string, etage: string}> */
    private const BATIMENTS_POOL = [
        ['nom' => 'Batiment A - Administration', 'etage' => 'R+2'],
        ['nom' => 'Batiment B - Production', 'etage' => 'RDC'],
        ['nom' => 'Batiment C - Logistique', 'etage' => 'R+1'],
        ['nom' => 'Hall Technique', 'etage' => 'RDC'],
        ['nom' => 'Aile Nord - Bureaux', 'etage' => 'R+3'],
        ['nom' => 'Aile Sud - Ateliers', 'etage' => 'RDC'],
        ['nom' => 'Entrepot Principal', 'etage' => 'RDC'],
        ['nom' => 'Annexe Maintenance', 'etage' => 'R+1'],
        ['nom' => 'Batiment D - Accueil', 'etage' => 'RDC'],
        ['nom' => 'Local Technique', 'etage' => 'Sous-sol'],
        ['nom' => 'Batiment E - R&D', 'etage' => 'R+2'],
        ['nom' => 'Parking Couvert', 'etage' => 'RDC'],
        ['nom' => 'Salle Serveurs', 'etage' => 'R+1'],
        ['nom' => 'Pavillon Formation', 'etage' => 'RDC'],
        ['nom' => 'Batiment F - Stockage', 'etage' => 'RDC'],
    ];

    public function load(ObjectManager $manager): void
    {
        $organisations = $manager->getRepository(Organisation::class)->findAll();

        foreach ($organisations as $organisation) {
            $sites = $manager->getRepository(Site::class)->findBy(
                ['organisation' => $organisation],
                ['id' => 'ASC']
            );

            if ($sites === []) {
                continue;
            }

            $poolIndex = 0;

            foreach ($sites as $site) {
                // 3 batiments par site
                for ($i = 0; $i < 3; $i++) {
                    $data = self::BATIMENTS_POOL[$poolIndex % count(self::BATIMENTS_POOL)];

                    $existing = $manager->getRepository(Batiment::class)->findOneBy([
                        'site' => $site,
                        'nom' => $data['nom'],
                    ]);

                    if ($existing instanceof Batiment) {
                        $poolIndex++;
                        continue;
                    }

                    $batiment = (new Batiment())
                        ->setNom($data['nom'])
                        ->setEtage($data['etage'])
                        ->setActif($poolIndex % 5 !== 4) // 1 inactif sur 5
                        ->setSite($site);

                    $manager->persist($batiment);
                    $poolIndex++;
                }
            }
        }

        $manager->flush();
    }

    /** @return list<class-string> */
    public function getDependencies(): array
    {
        return [SiteFixtures::class];
    }
}
