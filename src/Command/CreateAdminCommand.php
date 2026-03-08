<?php

namespace App\Command;

use App\Entity\Organisation;
use App\Entity\User;
use App\Repository\OrganisationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée un utilisateur administrateur en production',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface      $em,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly OrganisationRepository      $orgRepo,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Création d\'un administrateur GMAO');

        // ── Organisation ──────────────────────────────────────────────────────
        $organisations = $this->orgRepo->findAll();

        if (!empty($organisations)) {
            $choix = array_map(fn(Organisation $o) => $o->getNom(), $organisations);
            $choix[] = '+ Créer une nouvelle organisation';

            $selected = $io->choice('Organisation', $choix, $choix[0]);

            if ($selected === '+ Créer une nouvelle organisation') {
                $organisation = $this->creerOrganisation($io);
            } else {
                $organisation = $this->orgRepo->findOneBy(['nom' => $selected]);
            }
        } else {
            $io->info('Aucune organisation trouvée. Création obligatoire.');
            $organisation = $this->creerOrganisation($io);
        }

        // ── Informations utilisateur ──────────────────────────────────────────
        $email = $io->ask('Email de l\'administrateur', null, function (?string $v): string {
            if (empty($v) || !filter_var($v, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('Email invalide.');
            }
            return $v;
        });

        $nom    = $io->ask('Nom', null, fn(?string $v) => $this->notEmpty($v, 'Le nom'));
        $prenom = $io->ask('Prénom', null, fn(?string $v) => $this->notEmpty($v, 'Le prénom'));

        $plainPassword = $io->askHidden('Mot de passe (masqué)', function (?string $v): string {
            if (empty($v) || strlen($v) < 8) {
                throw new \RuntimeException('Le mot de passe doit contenir au moins 8 caractères.');
            }
            return $v;
        });

        // ── Création de l'utilisateur ─────────────────────────────────────────
        $user = new User();
        $user->setEmail($email)
             ->setNom($nom)
             ->setPrenom($prenom)
             ->setRoles(['ROLE_ADMIN'])
             ->setActif(true)
             ->setOrganisation($organisation)
             ->setPassword($this->hasher->hashPassword($user, $plainPassword));

        $this->em->persist($user);
        $this->em->flush();

        $io->success(sprintf(
            'Administrateur "%s %s" (%s) créé avec succès dans l\'organisation "%s".',
            $prenom,
            $nom,
            $email,
            $organisation->getNom(),
        ));

        return Command::SUCCESS;
    }

    private function creerOrganisation(SymfonyStyle $io): Organisation
    {
        $nom = $io->ask('Nom de l\'organisation', null, fn(?string $v) => $this->notEmpty($v, 'Le nom'));

        $org = new Organisation();
        $org->setNom($nom)->setActif(true);

        $this->em->persist($org);
        $this->em->flush();

        $io->info(sprintf('Organisation "%s" créée.', $nom));

        return $org;
    }

    private function notEmpty(?string $value, string $label): string
    {
        if (empty(trim((string) $value))) {
            throw new \RuntimeException("$label ne peut pas être vide.");
        }
        return trim($value);
    }
}
