<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Organisation;
use App\Entity\Site;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SiteFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * @var array<string, list<array{
     *     nom: string,
     *     adresse: string,
     *     codePostal: string,
     *     ville: string,
     *     telephone: string,
     *     email: string,
     *     actif: bool
     * }>>
     */
    private const SITES_BY_ORGANISATION = [
        'GMAO Industries' => [
            [
                'nom' => 'Siege Paris',
                'adresse' => '15 Rue de la Paix',
                'codePostal' => '75008',
                'ville' => 'Paris',
                'telephone' => '0140203040',
                'email' => 'siege.paris@gmao-industries.fr',
                'actif' => true,
            ],
            [
                'nom' => 'Site Nord Lille',
                'adresse' => '42 Avenue du Nord',
                'codePostal' => '59000',
                'ville' => 'Lille',
                'telephone' => '0320102030',
                'email' => 'site.nord@gmao-industries.fr',
                'actif' => true,
            ],
            [
                'nom' => 'Atelier Lyon',
                'adresse' => '8 Quai de Saone',
                'codePostal' => '69009',
                'ville' => 'Lyon',
                'telephone' => '0478102030',
                'email' => 'atelier.lyon@gmao-industries.fr',
                'actif' => true,
            ],
            [
                'nom' => 'Entrepot Strasbourg',
                'adresse' => '5 Rue du Rhin',
                'codePostal' => '67000',
                'ville' => 'Strasbourg',
                'telephone' => '0388304050',
                'email' => 'strasbourg@gmao-industries.fr',
                'actif' => true,
            ],
            [
                'nom' => 'Centre Technique Toulouse',
                'adresse' => '22 Boulevard Carnot',
                'codePostal' => '31000',
                'ville' => 'Toulouse',
                'telephone' => '0561405060',
                'email' => 'toulouse@gmao-industries.fr',
                'actif' => false,
            ],
        ],
        'Maintenance Sud' => [
            [
                'nom' => 'Agence Marseille',
                'adresse' => '3 Boulevard National',
                'codePostal' => '13001',
                'ville' => 'Marseille',
                'telephone' => '0491102030',
                'email' => 'marseille@maintenance-sud.fr',
                'actif' => true,
            ],
            [
                'nom' => 'Agence Nice',
                'adresse' => '25 Promenade des Arts',
                'codePostal' => '06000',
                'ville' => 'Nice',
                'telephone' => '0493102030',
                'email' => 'nice@maintenance-sud.fr',
                'actif' => true,
            ],
            [
                'nom' => 'Depot Toulon',
                'adresse' => '11 Rue des Docks',
                'codePostal' => '83000',
                'ville' => 'Toulon',
                'telephone' => '0494202030',
                'email' => 'depot.toulon@maintenance-sud.fr',
                'actif' => true,
            ],
            [
                'nom' => 'Agence Montpellier',
                'adresse' => '18 Place de la Comedie',
                'codePostal' => '34000',
                'ville' => 'Montpellier',
                'telephone' => '0467302040',
                'email' => 'montpellier@maintenance-sud.fr',
                'actif' => true,
            ],
            [
                'nom' => 'Centre Technique Aix',
                'adresse' => '9 Cours Mirabeau',
                'codePostal' => '13100',
                'ville' => 'Aix-en-Provence',
                'telephone' => '0442501020',
                'email' => 'aix@maintenance-sud.fr',
                'actif' => false,
            ],
        ],
        'Patrimoine Services' => [
            [
                'nom' => 'Campus Nantes',
                'adresse' => '18 Rue des Machines',
                'codePostal' => '44000',
                'ville' => 'Nantes',
                'telephone' => '0240102030',
                'email' => 'nantes@patrimoine-services.fr',
                'actif' => true,
            ],
            [
                'nom' => 'Campus Rennes',
                'adresse' => '7 Place de Bretagne',
                'codePostal' => '35000',
                'ville' => 'Rennes',
                'telephone' => '0299102030',
                'email' => 'rennes@patrimoine-services.fr',
                'actif' => true,
            ],
            [
                'nom' => 'Annexe Brest',
                'adresse' => '55 Rue du Port',
                'codePostal' => '29200',
                'ville' => 'Brest',
                'telephone' => '0298202030',
                'email' => 'brest@patrimoine-services.fr',
                'actif' => true,
            ],
            [
                'nom' => 'Site Caen',
                'adresse' => '14 Rue Saint-Pierre',
                'codePostal' => '14000',
                'ville' => 'Caen',
                'telephone' => '0231607080',
                'email' => 'caen@patrimoine-services.fr',
                'actif' => true,
            ],
            [
                'nom' => 'Depot Saint-Malo',
                'adresse' => '3 Quai Duguay-Trouin',
                'codePostal' => '35400',
                'ville' => 'Saint-Malo',
                'telephone' => '0299401020',
                'email' => 'saint-malo@patrimoine-services.fr',
                'actif' => false,
            ],
        ],
        'Infra Support Ouest' => [
            [
                'nom' => 'Base Bordeaux',
                'adresse' => '90 Cours de la Somme',
                'codePostal' => '33000',
                'ville' => 'Bordeaux',
                'telephone' => '0556102030',
                'email' => 'bordeaux@infra-support-ouest.fr',
                'actif' => true,
            ],
            [
                'nom' => 'Site Angers',
                'adresse' => '12 Rue Voltaire',
                'codePostal' => '49000',
                'ville' => 'Angers',
                'telephone' => '0241202030',
                'email' => 'angers@infra-support-ouest.fr',
                'actif' => true,
            ],
            [
                'nom' => 'Site Poitiers',
                'adresse' => '2 Rue de la Gare',
                'codePostal' => '86000',
                'ville' => 'Poitiers',
                'telephone' => '0549102030',
                'email' => 'poitiers@infra-support-ouest.fr',
                'actif' => true,
            ],
            [
                'nom' => 'Base La Rochelle',
                'adresse' => '8 Rue du Palais',
                'codePostal' => '17000',
                'ville' => 'La Rochelle',
                'telephone' => '0546301020',
                'email' => 'larochelle@infra-support-ouest.fr',
                'actif' => true,
            ],
            [
                'nom' => 'Depot Limoges',
                'adresse' => '15 Avenue de la Gare',
                'codePostal' => '87000',
                'ville' => 'Limoges',
                'telephone' => '0555102030',
                'email' => 'limoges@infra-support-ouest.fr',
                'actif' => false,
            ],
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::SITES_BY_ORGANISATION as $organisationName => $sitesData) {
            $organisation = $manager->getRepository(Organisation::class)->findOneBy([
                'nom' => $organisationName,
            ]);

            if (!$organisation instanceof Organisation) {
                continue;
            }

            foreach ($sitesData as $item) {
                $site = $manager->getRepository(Site::class)->findOneBy([
                    'organisation' => $organisation,
                    'nom' => $item['nom'],
                ]);

                if (!$site instanceof Site) {
                    $site = new Site();
                    $site->setOrganisation($organisation);
                    $site->setNom($item['nom']);
                }

                $site
                    ->setAdresse($item['adresse'])
                    ->setCodePostal($item['codePostal'])
                    ->setVille($item['ville'])
                    ->setTelephone($item['telephone'])
                    ->setEmail($item['email'])
                    ->setActif($item['actif']);

                $manager->persist($site);
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
