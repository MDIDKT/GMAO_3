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
    /** @var list<array{nom: string, marque: string, modele: string, categorie: string}> */
    private const EQUIPEMENTS_POOL = [
        ['nom' => 'Chaudiere gaz condensation', 'marque' => 'De Dietrich', 'modele' => 'MCR-34', 'categorie' => 'Chauffage'],
        ['nom' => 'Radiateur fonte Hall', 'marque' => 'Acova', 'modele' => 'VUELTA-2000', 'categorie' => 'Chauffage'],
        ['nom' => 'Plancher chauffant Bureaux', 'marque' => 'Rehau', 'modele' => 'RAUTHERM-S', 'categorie' => 'Chauffage'],
        ['nom' => 'Climatiseur split Bureau Direction', 'marque' => 'Daikin', 'modele' => 'FTXM-35R', 'categorie' => 'Climatisation'],
        ['nom' => 'CTA Double flux', 'marque' => 'France Air', 'modele' => 'COCOON-2', 'categorie' => 'Climatisation'],
        ['nom' => 'Ventilo-convecteur Salle reunion', 'marque' => 'Carrier', 'modele' => '42N-06', 'categorie' => 'Climatisation'],
        ['nom' => 'Tableau electrique TGBT', 'marque' => 'Schneider', 'modele' => 'PRISMA-P', 'categorie' => 'Electricite'],
        ['nom' => 'Eclairage LED Open Space', 'marque' => 'Philips', 'modele' => 'CoreLine-RC132V', 'categorie' => 'Electricite'],
        ['nom' => 'Onduleur Salle serveurs', 'marque' => 'APC', 'modele' => 'SRT-5000', 'categorie' => 'Electricite'],
        ['nom' => 'Camera IP Parking', 'marque' => 'Hikvision', 'modele' => 'DS-2CD2143', 'categorie' => 'Securite'],
        ['nom' => 'Controle d\'acces Entree', 'marque' => 'Honeywell', 'modele' => 'MB-SECURE', 'categorie' => 'Securite'],
        ['nom' => 'Alarme intrusion', 'marque' => 'Siemens', 'modele' => 'SPC-6350', 'categorie' => 'Securite'],
        ['nom' => 'Chauffe-eau thermodynamique', 'marque' => 'Atlantic', 'modele' => 'CALYPSO-250', 'categorie' => 'Plomberie'],
        ['nom' => 'Surpresseur eau', 'marque' => 'Grundfos', 'modele' => 'CME-3-5', 'categorie' => 'Plomberie'],
        ['nom' => 'Ascenseur passagers', 'marque' => 'Otis', 'modele' => 'GEN2-COMFORT', 'categorie' => 'Ascenseurs'],
        ['nom' => 'Monte-charge Entrepot', 'marque' => 'Schindler', 'modele' => '2600-MC', 'categorie' => 'Ascenseurs'],
        ['nom' => 'Extincteur CO2 5kg', 'marque' => 'Desautel', 'modele' => 'EC5-A', 'categorie' => 'Incendie'],
        ['nom' => 'Detecteur de fumee optique', 'marque' => 'Esser', 'modele' => 'OTG-HB', 'categorie' => 'Incendie'],
        ['nom' => 'RIA DN25 Hall', 'marque' => 'Desautel', 'modele' => 'RIA-30M', 'categorie' => 'Incendie'],
        ['nom' => 'Porte coupe-feu EI60', 'marque' => 'Malerba', 'modele' => 'RF-60', 'categorie' => 'Menuiserie'],
        ['nom' => 'Fenetre aluminium Bureaux', 'marque' => 'Technal', 'modele' => 'SOLEAL-65', 'categorie' => 'Menuiserie'],
        ['nom' => 'Store interieur motorise', 'marque' => 'Somfy', 'modele' => 'SONESSE-30', 'categorie' => 'Menuiserie'],
        ['nom' => 'Pompe a chaleur air/eau', 'marque' => 'Mitsubishi', 'modele' => 'ERSC-MEC', 'categorie' => 'Chauffage'],
        ['nom' => 'Groupe electrogene secours', 'marque' => 'SDMO', 'modele' => 'J130K', 'categorie' => 'Electricite'],
        ['nom' => 'Centrale detection incendie', 'marque' => 'Siemens', 'modele' => 'FC-2060', 'categorie' => 'Incendie'],
    ];

    public function load(ObjectManager $manager): void
    {
        $organisations = $manager->getRepository(Organisation::class)->findAll();
        $statuts = StatutEquipement::cases();

        foreach ($organisations as $organisation) {
            $batiments = $manager->createQuery(
                'SELECT b FROM App\Entity\Batiment b
                 INNER JOIN b.site s
                 WHERE s.organisation = :org
                 ORDER BY b.id ASC'
            )
                ->setParameter('org', $organisation)
                ->getResult();

            $categories = $manager->getRepository(CategorieEquipement::class)->findBy(
                ['organisation' => $organisation],
                ['id' => 'ASC']
            );

            if ($batiments === [] || $categories === []) {
                continue;
            }

            $sites = $manager->getRepository(Site::class)->findBy(
                ['organisation' => $organisation],
                ['id' => 'ASC']
            );

            // Index categories par nom pour le mapping
            $categoriesByName = [];
            foreach ($categories as $cat) {
                $categoriesByName[$cat->getNom()] = $cat;
            }

            $serialNum = 1;

            foreach (self::EQUIPEMENTS_POOL as $index => $data) {
                $batiment = $batiments[$index % count($batiments)];
                $site = $batiment->getSite() ?? $sites[$index % count($sites)];
                $categorie = $categoriesByName[$data['categorie']] ?? $categories[0];
                $statut = $statuts[$index % count($statuts)];

                $equipement = (new Equipement())
                    ->setNom($data['nom'])
                    ->setMarque($data['marque'])
                    ->setModele($data['modele'])
                    ->setNumeroDeSerie(sprintf('SN-%s-%04d', strtoupper(substr($organisation->getNom(), 0, 3)), $serialNum))
                    ->setStatut($statut)
                    ->setActif($index % 6 !== 5) // 1 inactif sur 6
                    ->setSite($site)
                    ->setBatiment($batiment)
                    ->setCategorie($categorie)
                    ->setOrganisation($organisation);

                $manager->persist($equipement);
                $serialNum++;
            }
        }

        $manager->flush();
    }

    /** @return list<class-string> */
    public function getDependencies(): array
    {
        return [
            BatimentFixtures::class,
            CategorieEquipementFixtures::class,
        ];
    }
}
