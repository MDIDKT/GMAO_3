<?php

namespace App\DataFixtures;

use App\Entity\Batiment;
use App\Entity\CategorieEquipement;
use App\Entity\Equipement;
use App\Entity\Organisation;
use App\Entity\Site;
use App\Enum\StatutEquipement;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class EquipementFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $organisations = $manager->getRepository(Organisation::class)->findBy([], ['id' => 'ASC']);

        foreach ($organisations as $organisation) {
            $this->ensureEquipements($manager, $organisation);
        }

        $manager->flush();
    }

    /**
     * @return list<class-string>
     */
    public function getDependencies(): array
    {
        return [
            BatimentFixtures::class,
            CategorieEquipementFixtures::class,
        ];
    }

    private function ensureEquipements(ObjectManager $manager, Organisation $organisation): void
    {
        $batiments = $manager->createQuery(
            'SELECT b
             FROM App\Entity\Batiment b
             INNER JOIN b.site s
             WHERE s.organisation = :organisation
             ORDER BY b.id ASC'
        )
            ->setParameter('organisation', $organisation)
            ->getResult();

        $categories = $manager->getRepository(CategorieEquipement::class)->findBy(
            ['organisation' => $organisation],
            ['id' => 'ASC']
        );

        if ($batiments === [] || $categories === []) {
            return;
        }

        $sites = $manager->getRepository(Site::class)->findBy(
            ['organisation' => $organisation],
            ['id' => 'ASC']
        );

        $existingEquipements = $manager->getRepository(Equipement::class)->findBy(
            ['organisation' => $organisation],
            ['id' => 'ASC']
        );

        $statuts = StatutEquipement::cases();
        $marques = ['Daikin', 'Siemens', 'Schneider', 'Mitsubishi'];

        foreach ($existingEquipements as $index => $equipement) {
            $label = $index + 1;
            $batiment = $this->resolveBatimentForEquipement($equipement, $batiments, $index);
            $site = $this->resolveSiteForEquipement($equipement, $batiment, $sites, $index);
            $categorie = $equipement->getCategorie() ?? $categories[$index % count($categories)];
            $statut = $equipement->getStatut() ?? $statuts[$index % count($statuts)];
            $marque = $equipement->getMarque() ?? $marques[$index % count($marques)];

            if ($equipement->getNom() === null || trim($equipement->getNom()) === '') {
                $equipement->setNom(sprintf('Equipement %02d', $label));
            }

            if ($equipement->getMarque() === null || trim($equipement->getMarque()) === '') {
                $equipement->setMarque($marque);
            }

            if ($equipement->getModele() === null || trim($equipement->getModele()) === '') {
                $equipement->setModele(sprintf('%s-%03d', strtoupper(substr($marque, 0, 3)), $label));
            }

            if ($equipement->getNumeroDeSerie() === null || trim($equipement->getNumeroDeSerie()) === '') {
                $equipement->setNumeroDeSerie(sprintf('SN-%06d', $label));
            }

            if ($equipement->getStatut() === null) {
                $equipement->setStatut($statut);
            }

            if ($equipement->isActif() === null) {
                $equipement->setActif(true);
            }

            if ($equipement->getBatiment() === null) {
                $equipement->setBatiment($batiment);
            }

            if ($equipement->getSite() === null && $site !== null) {
                $equipement->setSite($site);
            }

            if ($equipement->getCategorie() === null) {
                $equipement->setCategorie($categorie);
            }

            if ($equipement->getOrganisation() === null) {
                $equipement->setOrganisation($organisation);
            }

            $manager->persist($equipement);
        }

        if (count($existingEquipements) >= 12) {
            return;
        }

        $toCreate = 12 - count($existingEquipements);

        for ($i = 0; $i < $toCreate; $i++) {
            $index = count($existingEquipements) + $i;
            $batiment = $batiments[$index % count($batiments)];
            $site = $batiment->getSite();
            $categorie = $categories[$index % count($categories)];
            $statut = $statuts[$index % count($statuts)];
            $marque = $marques[$index % count($marques)];

            $equipement = (new Equipement())
                ->setNom(sprintf('Equipement %02d', $index + 1))
                ->setMarque($marque)
                ->setModele(sprintf('%s-%03d', strtoupper(substr($marque, 0, 3)), $index + 1))
                ->setNumeroDeSerie(sprintf('SN-%06d', $index + 1))
                ->setStatut($statut)
                ->setActif($index % 2 === 0)
                ->setSite($site)
                ->setBatiment($batiment)
                ->setCategorie($categorie)
                ->setOrganisation($organisation);

            $manager->persist($equipement);
        }
    }

    /**
     * @param list<Batiment> $batiments
     */
    private function resolveBatimentForEquipement(Equipement $equipement, array $batiments, int $index): Batiment
    {
        return $equipement->getBatiment() ?? $batiments[$index % count($batiments)];
    }

    /**
     * @param list<Site> $sites
     */
    private function resolveSiteForEquipement(
        Equipement $equipement,
        Batiment $batiment,
        array $sites,
        int $index
    ): ?Site {
        if ($equipement->getSite() !== null) {
            return $equipement->getSite();
        }

        if ($batiment->getSite() !== null) {
            return $batiment->getSite();
        }

        if ($sites === []) {
            return null;
        }

        return $sites[$index % count($sites)];
    }
}
