<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Organisation;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class OrganisationFixtures extends Fixture
{
    /**
     * @var list<array{nom: string, actif: bool}>
     */
    private const ORGANISATIONS = [
        ['nom' => 'GMAO Industries', 'actif' => true],
        ['nom' => 'Maintenance Sud', 'actif' => true],
        ['nom' => 'Patrimoine Services', 'actif' => true],
        ['nom' => 'Infra Support Ouest', 'actif' => false],
    ];

    public function load(ObjectManager $manager): void
    {
        $now = new DateTimeImmutable();

        foreach (self::ORGANISATIONS as $item) {
            $organisation = $manager->getRepository(Organisation::class)->findOneBy([
                'nom' => $item['nom'],
            ]);

            if (!$organisation instanceof Organisation) {
                $organisation = (new Organisation())
                    ->setNom($item['nom']);
            }

            $organisation->setActif($item['actif']);

            if ($organisation->getCreatedAt() === null) {
                $organisation->setCreatedAt($now);
            }
            if ($organisation->getUpdatedAt() === null) {
                $organisation->setUpdatedAt($now);
            }

            $manager->persist($organisation);
        }

        $manager->flush();
    }
}
