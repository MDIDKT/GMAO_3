<?php

namespace App\DataFixtures;

use App\Entity\CategorieEquipement;
use App\Entity\Organisation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CategorieEquipementFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $organisations = $manager->getRepository(Organisation::class)->findBy([], ['id' => 'ASC']);

        foreach ($organisations as $organisation) {
            $this->ensureCategories($manager, $organisation);
        }

        $manager->flush();
    }

    /**
     * @return list<class-string>
     */
    public function getDependencies(): array
    {
        return [OrganisationFixtures::class];
    }

    private function ensureCategories(ObjectManager $manager, Organisation $organisation): void
    {
        $existingCategories = $manager->getRepository(CategorieEquipement::class)->findBy(
            ['organisation' => $organisation],
            ['id' => 'ASC']
        );

        $names = ['Chauffage', 'Climatisation', 'Electricite', 'Securite'];

        foreach ($existingCategories as $index => $categorie) {
            $label = $index + 1;
            $name = $names[$index % count($names)];

            if ($categorie->getNom() === null || trim($categorie->getNom()) === '') {
                $categorie->setNom(sprintf('%s %d', $name, $label));
            }

            if ($categorie->getDescription() === null || trim($categorie->getDescription()) === '') {
                $categorie->setDescription(sprintf('Categorie %s', $categorie->getNom()));
            }

            if ($categorie->getOrganisation() === null) {
                $categorie->setOrganisation($organisation);
            }

            $manager->persist($categorie);
        }

        if (count($existingCategories) >= 4) {
            return;
        }

        $needed = 4 - count($existingCategories);

        for ($i = 0; $i < $needed; $i++) {
            $index = count($existingCategories);
            $name = $names[$index] ?? sprintf('Categorie %d', $index + 1);

            $categorie = (new CategorieEquipement())
                ->setNom($name)
                ->setDescription(sprintf('Categorie %s', $name))
                ->setOrganisation($organisation);

            $manager->persist($categorie);
            $existingCategories[] = $categorie;
        }
    }
}
