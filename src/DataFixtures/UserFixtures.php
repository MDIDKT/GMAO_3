<?php

declare(strict_types=1);

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
            ['email' => 'admin@gmao.fr',          'nom' => 'Dupont',   'prenom' => 'Jean',     'roles' => ['ROLE_ADMIN']],
            ['email' => 'planificateur@gmao.fr',   'nom' => 'Martin',   'prenom' => 'Sophie',   'roles' => ['ROLE_PLANIFICATEUR']],
            ['email' => 'tech1@gmao.fr',           'nom' => 'Bernard',  'prenom' => 'Lucas',    'roles' => ['ROLE_TECHNICIEN']],
            ['email' => 'tech2@gmao.fr',           'nom' => 'Petit',    'prenom' => 'Thomas',   'roles' => ['ROLE_TECHNICIEN']],
            ['email' => 'demandeur@gmao.fr',       'nom' => 'Dubois',   'prenom' => 'Marie',    'roles' => ['ROLE_DEMANDEUR']],
        ],
        'Maintenance Sud' => [
            ['email' => 'admin@maintenance-sud.fr', 'nom' => 'Moreau',  'prenom' => 'Pierre',   'roles' => ['ROLE_ADMIN']],
            ['email' => 'planif@maintenance-sud.fr', 'nom' => 'Leroy',  'prenom' => 'Julie',    'roles' => ['ROLE_PLANIFICATEUR']],
            ['email' => 'tech@maintenance-sud.fr',   'nom' => 'Roux',   'prenom' => 'Antoine',  'roles' => ['ROLE_TECHNICIEN']],
            ['email' => 'demandeur@maintenance-sud.fr', 'nom' => 'Simon', 'prenom' => 'Claire', 'roles' => ['ROLE_DEMANDEUR']],
        ],
        'Patrimoine Services' => [
            ['email' => 'admin@patrimoine.fr',      'nom' => 'Laurent',  'prenom' => 'Marc',    'roles' => ['ROLE_ADMIN']],
            ['email' => 'planif@patrimoine.fr',      'nom' => 'Michel',   'prenom' => 'Isabelle', 'roles' => ['ROLE_PLANIFICATEUR']],
            ['email' => 'tech@patrimoine.fr',        'nom' => 'Garcia',   'prenom' => 'David',   'roles' => ['ROLE_TECHNICIEN']],
            ['email' => 'demandeur@patrimoine.fr',   'nom' => 'Fournier', 'prenom' => 'Nathalie', 'roles' => ['ROLE_DEMANDEUR']],
        ],
        'Infra Support Ouest' => [
            ['email' => 'admin@infra-ouest.fr',     'nom' => 'Girard',   'prenom' => 'Philippe', 'roles' => ['ROLE_ADMIN']],
            ['email' => 'tech@infra-ouest.fr',       'nom' => 'Andre',    'prenom' => 'Clement',  'roles' => ['ROLE_TECHNICIEN']],
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
