<?php

namespace App\DataFixtures;

use App\Entity\CategorieEquipement;
use App\Entity\Organisation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CategorieEquipementFixtures extends Fixture implements DependentFixtureInterface
{
    /** @var list<array{nom: string, description: string}> */
    private const CATEGORIES = [
        ['nom' => 'Chauffage', 'description' => 'Chaudieres, radiateurs, planchers chauffants'],
        ['nom' => 'Climatisation', 'description' => 'Climatiseurs, CTA, ventilo-convecteurs'],
        ['nom' => 'Electricite', 'description' => 'Tableaux electriques, eclairage, prises'],
        ['nom' => 'Securite', 'description' => 'Alarmes, cameras, controle d\'acces'],
        ['nom' => 'Plomberie', 'description' => 'Canalisations, robinetterie, sanitaires'],
        ['nom' => 'Ascenseurs', 'description' => 'Ascenseurs, monte-charges, escaliers mecaniques'],
        ['nom' => 'Incendie', 'description' => 'Extincteurs, RIA, detecteurs de fumee'],
        ['nom' => 'Menuiserie', 'description' => 'Portes, fenetres, volets, stores'],
    ];

    public function load(ObjectManager $manager): void
    {
        $organisations = $manager->getRepository(Organisation::class)->findAll();

        foreach ($organisations as $organisation) {
            foreach (self::CATEGORIES as $data) {
                $existing = $manager->getRepository(CategorieEquipement::class)->findOneBy([
                    'organisation' => $organisation,
                    'nom' => $data['nom'],
                ]);

                if ($existing instanceof CategorieEquipement) {
                    continue;
                }

                $categorie = (new CategorieEquipement())
                    ->setNom($data['nom'])
                    ->setDescription($data['description'])
                    ->setOrganisation($organisation);

                $manager->persist($categorie);
            }
        }

        $manager->flush();
    }

    /** @return list<class-string> */
    public function getDependencies(): array
    {
        return [OrganisationFixtures::class];
    }
}
