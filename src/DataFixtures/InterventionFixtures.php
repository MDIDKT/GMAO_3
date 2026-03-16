<?php

namespace App\DataFixtures;

use App\Entity\Demande;
use App\Entity\Intervention;
use App\Entity\Organisation;
use App\Entity\User;
use App\Enum\StatutDemande;
use App\Enum\StatutIntervention;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class InterventionFixtures extends Fixture implements DependentFixtureInterface
{
    /** @var list<array{statut: StatutIntervention, dureeMinutes: ?int, compteRendu: ?string, notes: ?string}> */
    private const INTERVENTIONS = [
        [
            'statut' => StatutIntervention::A_PLANIFIER,
            'dureeMinutes' => null,
            'compteRendu' => null,
            'notes' => 'En attente de pieces de rechange.',
        ],
        [
            'statut' => StatutIntervention::PLANIFIE,
            'dureeMinutes' => null,
            'compteRendu' => null,
            'notes' => 'Intervention planifiee pour la semaine prochaine.',
        ],
        [
            'statut' => StatutIntervention::EN_COURS,
            'dureeMinutes' => null,
            'compteRendu' => null,
            'notes' => 'Technicien sur place, diagnostic en cours.',
        ],
        [
            'statut' => StatutIntervention::TERMINEE,
            'dureeMinutes' => 120,
            'compteRendu' => 'Remplacement du joint defectueux. Test de pression effectue, plus aucune fuite detectee.',
            'notes' => null,
        ],
        [
            'statut' => StatutIntervention::VALIDEE,
            'dureeMinutes' => 90,
            'compteRendu' => 'Nettoyage du filtre et recharge en gaz refrigerant. Climatisation fonctionnelle.',
            'notes' => 'Prevoir un contrat de maintenance annuel.',
        ],
        [
            'statut' => StatutIntervention::TERMINEE,
            'dureeMinutes' => 45,
            'compteRendu' => 'Remplacement de 6 tubes LED. Eclairage retabli dans tout le parking.',
            'notes' => 'Stock de tubes a reapprovisionner.',
        ],
        [
            'statut' => StatutIntervention::VALIDEE,
            'dureeMinutes' => 180,
            'compteRendu' => 'Remplacement du mecanisme de fermeture automatique. Porte conforme aux normes de securite.',
            'notes' => null,
        ],
        [
            'statut' => StatutIntervention::A_PLANIFIER,
            'dureeMinutes' => null,
            'compteRendu' => null,
            'notes' => 'Contacter le fournisseur Otis pour devis.',
        ],
        [
            'statut' => StatutIntervention::PLANIFIE,
            'dureeMinutes' => null,
            'compteRendu' => null,
            'notes' => 'Necessaire: cle de purge, manometre.',
        ],
        [
            'statut' => StatutIntervention::EN_COURS,
            'dureeMinutes' => null,
            'compteRendu' => null,
            'notes' => 'Camera de remplacement en cours d\'installation.',
        ],
        [
            'statut' => StatutIntervention::VALIDEE,
            'dureeMinutes' => 60,
            'compteRendu' => 'Remplacement de la prise electrique et verification du circuit. RAS.',
            'notes' => null,
        ],
        [
            'statut' => StatutIntervention::TERMINEE,
            'dureeMinutes' => 240,
            'compteRendu' => 'Revision complete du groupe electrogene. Remplacement huile, filtres et batterie.',
            'notes' => 'Prochain test prevu dans 3 mois.',
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        $organisations = $manager->getRepository(Organisation::class)->findAll();
        $interventionNum = 1;

        foreach ($organisations as $organisation) {
            // On recupère les demandes qui ont un statut eligible (PLANIFIE, EN_COURS, CLOTURE)
            $demandes = $manager->getRepository(Demande::class)->findBy(
                ['organisation' => $organisation],
                ['id' => 'ASC']
            );

            if ($demandes === []) {
                continue;
            }

            // Chercher des users techniciens ou planificateurs
            $allUsers = $manager->getRepository(User::class)->findBy(
                ['organisation' => $organisation],
                ['id' => 'ASC']
            );

            if ($allUsers === []) {
                continue;
            }

            // Filtrer demandes eligibles (PLANIFIE, EN_COURS, CLOTURE ou tout statut sauf A_QUALIFIER/REJETEE)
            $demandesEligibles = array_values(array_filter(
                $demandes,
                static fn (Demande $d) => !in_array($d->getStatut(), [
                    StatutDemande::A_QUALIFIER,
                    StatutDemande::REJETEE,
                ], true)
            ));

            if ($demandesEligibles === []) {
                $demandesEligibles = $demandes;
            }

            $now = new DateTimeImmutable();

            foreach (self::INTERVENTIONS as $index => $data) {
                $demande = $demandesEligibles[$index % count($demandesEligibles)];
                $technicien = $allUsers[$index % count($allUsers)];
                $planificateur = $allUsers[($index + 1) % count($allUsers)];

                $intervention = (new Intervention())
                    ->setNumero(sprintf('INT-%04d', $interventionNum))
                    ->setStatut($data['statut'])
                    ->setDemande($demande)
                    ->setTechnicien($technicien)
                    ->setPlanificateur($planificateur)
                    ->setOrganisation($organisation);

                if ($data['dureeMinutes'] !== null) {
                    $intervention->setDureeMinutes($data['dureeMinutes']);
                }

                if ($data['compteRendu'] !== null) {
                    $intervention->setCompteRendu($data['compteRendu']);
                }

                if ($data['notes'] !== null) {
                    $intervention->setNotes($data['notes']);
                }

                // Dates selon statut
                if ($data['statut'] !== StatutIntervention::A_PLANIFIER) {
                    $datePlanifiee = (clone $now)->modify(sprintf('+%d days', $index + 1));
                    $intervention->setDatePlanifiee($datePlanifiee);
                }

                if (in_array($data['statut'], [StatutIntervention::EN_COURS, StatutIntervention::TERMINEE, StatutIntervention::VALIDEE], true)) {
                    $dateDebut = (clone $now)->modify(sprintf('-%d days', 5 - ($index % 5)));
                    $intervention->setDateDebut($dateDebut);
                }

                if (in_array($data['statut'], [StatutIntervention::TERMINEE, StatutIntervention::VALIDEE], true)) {
                    $dateFin = (clone $now)->modify(sprintf('-%d days', 3 - ($index % 3)));
                    $intervention->setDateFin($dateFin);
                }

                $manager->persist($intervention);
                $interventionNum++;
            }
        }

        $manager->flush();
    }

    /** @return list<class-string> */
    public function getDependencies(): array
    {
        return [
            DemandeFixtures::class,
        ];
    }
}
