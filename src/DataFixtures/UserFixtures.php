<?php

namespace App\DataFixtures;

use App\Entity\Organisation;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    public const DEFAULT_PASSWORD = 'Test1234!';

    /**
     * Utilisateurs par nom d'organisation.
     * @var array<string, list<array{email: string, nom: string, prenom: string, roles: list<string>}>>
     */
    private const USERS = [
        'GMAO Industries' => [
            ['email' => 'admin@gmao.fr',          'nom' => 'Diallo',    'prenom' => 'Moussa',      'roles' => ['ROLE_ADMIN']],
            ['email' => 'planificateur@gmao.fr',   'nom' => 'Koné',      'prenom' => 'Fatoumata',   'roles' => ['ROLE_PLANIFICATEUR']],
            ['email' => 'tech1@gmao.fr',           'nom' => 'Traoré',    'prenom' => 'Ibrahima',    'roles' => ['ROLE_TECHNICIEN']],
            ['email' => 'tech2@gmao.fr',           'nom' => 'Coulibaly', 'prenom' => 'Seydou',      'roles' => ['ROLE_TECHNICIEN']],
            ['email' => 'demandeur@gmao.fr',       'nom' => 'Keita',     'prenom' => 'Aminata',     'roles' => ['ROLE_DEMANDEUR']],
        ],
        'Maintenance Sud' => [
            ['email' => 'admin@maintenance-sud.fr',    'nom' => 'Touré',   'prenom' => 'Amadou',    'roles' => ['ROLE_ADMIN']],
            ['email' => 'planif@maintenance-sud.fr',   'nom' => 'Camara',  'prenom' => 'Awa',       'roles' => ['ROLE_PLANIFICATEUR']],
            ['email' => 'tech@maintenance-sud.fr',     'nom' => 'Bah',     'prenom' => 'Oumar',     'roles' => ['ROLE_TECHNICIEN']],
            ['email' => 'demandeur@maintenance-sud.fr','nom' => 'Sidibé',  'prenom' => 'Mariama',   'roles' => ['ROLE_DEMANDEUR']],
        ],
        'Patrimoine Services' => [
            ['email' => 'admin@patrimoine.fr',      'nom' => 'Cissé',     'prenom' => 'Boubacar',   'roles' => ['ROLE_ADMIN']],
            ['email' => 'planif@patrimoine.fr',      'nom' => 'Dembélé',   'prenom' => 'Kadiatou',  'roles' => ['ROLE_PLANIFICATEUR']],
            ['email' => 'tech@patrimoine.fr',        'nom' => 'Kouyaté',   'prenom' => 'Lamine',    'roles' => ['ROLE_TECHNICIEN']],
            ['email' => 'demandeur@patrimoine.fr',   'nom' => 'Sow',       'prenom' => 'Hawa',      'roles' => ['ROLE_DEMANDEUR']],
        ],
        'Infra Support Ouest' => [
            ['email' => 'admin@infra-ouest.fr',     'nom' => 'Barry',     'prenom' => 'Cheikh',     'roles' => ['ROLE_ADMIN']],
            ['email' => 'tech@infra-ouest.fr',       'nom' => 'Diakité',   'prenom' => 'Ismaël',    'roles' => ['ROLE_TECHNICIEN']],
        ],
    ];

    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::USERS as $orgNom => $users) {
            $organisation = $manager->getRepository(Organisation::class)->findOneBy(['nom' => $orgNom]);

            if (!$organisation instanceof Organisation) {
                continue;
            }

            foreach ($users as $data) {
                // Eviter les doublons si --append
                $existing = $manager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
                if ($existing instanceof User) {
                    continue;
                }

                $user = (new User())
                    ->setEmail($data['email'])
                    ->setNom($data['nom'])
                    ->setPrenom($data['prenom'])
                    ->setRoles($data['roles'])
                    ->setActif(true)
                    ->setOrganisation($organisation);

                $user->setPassword($this->passwordHasher->hashPassword($user, self::DEFAULT_PASSWORD));

                $manager->persist($user);
            }
        }

        $manager->flush();
    }

    /** @return list<class-string> */
    public function getDependencies(): array
    {
        return [
            OrganisationFixtures::class,
        ];
    }
}
