<?php

namespace App\DataFixtures;

use App\Entity\Demande;
use App\Entity\Equipement;
use App\Entity\Organisation;
use App\Entity\Site;
use App\Entity\User;
use App\Enum\Priorite;
use App\Enum\StatutDemande;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class DemandeFixtures extends Fixture implements DependentFixtureInterface
{
    /** @var list<array{titre: string, description: string, priorite: Priorite, statut: StatutDemande, motifRejet: ?string}> */
    private const DEMANDES = [
        [
            'titre' => 'Fuite d\'eau toilettes RDC',
            'description' => 'Une fuite d\'eau importante a ete detectee dans les toilettes du rez-de-chaussee, cote hommes. Le sol est inonde chaque matin.',
            'priorite' => Priorite::P1_URGENTE,
            'statut' => StatutDemande::PLANIFIE,
            'motifRejet' => null,
        ],
        [
            'titre' => 'Climatisation en panne Bureau 204',
            'description' => 'Le climatiseur du bureau 204 ne produit plus d\'air froid. Temperature mesuree a 32 degres.',
            'priorite' => Priorite::P1_URGENTE,
            'statut' => StatutDemande::EN_COURS,
            'motifRejet' => null,
        ],
        [
            'titre' => 'Eclairage defaillant Parking B2',
            'description' => 'Plusieurs neons du parking souterrain B2 clignotent ou sont eteints, rendant le parking tres sombre.',
            'priorite' => Priorite::P2_HAUTE,
            'statut' => StatutDemande::QUALIFIE,
            'motifRejet' => null,
        ],
        [
            'titre' => 'Porte coupe-feu bloquee 3eme etage',
            'description' => 'La porte coupe-feu du couloir principal au 3eme etage ne se ferme plus automatiquement. Probleme de securite.',
            'priorite' => Priorite::P1_URGENTE,
            'statut' => StatutDemande::A_QUALIFIER,
            'motifRejet' => null,
        ],
        [
            'titre' => 'Ascenseur bloque entre 2 etages',
            'description' => 'L\'ascenseur principal reste bloque regulierement entre le 1er et le 2eme etage. Dernier incident ce matin.',
            'priorite' => Priorite::P1_URGENTE,
            'statut' => StatutDemande::A_QUALIFIER,
            'motifRejet' => null,
        ],
        [
            'titre' => 'Chauffage insuffisant Open Space',
            'description' => 'L\'open space du batiment A est sous-chauffe depuis 3 jours. Les collaborateurs travaillent en manteau.',
            'priorite' => Priorite::P2_HAUTE,
            'statut' => StatutDemande::PLANIFIE,
            'motifRejet' => null,
        ],
        [
            'titre' => 'Camera parking HS depuis 1 semaine',
            'description' => 'La camera de surveillance du parking visiteurs ne fonctionne plus. Aucune image depuis le 15 du mois.',
            'priorite' => Priorite::P2_HAUTE,
            'statut' => StatutDemande::EN_COURS,
            'motifRejet' => null,
        ],
        [
            'titre' => 'Vitre fissuree salle de reunion',
            'description' => 'La grande baie vitree de la salle de reunion Olympe presente une fissure d\'environ 40cm.',
            'priorite' => Priorite::P3_NORMALE,
            'statut' => StatutDemande::QUALIFIE,
            'motifRejet' => null,
        ],
        [
            'titre' => 'Store motorise bloque position haute',
            'description' => 'Le store du bureau 105 est bloque en position haute. La telecommande ne repond plus.',
            'priorite' => Priorite::P4_BASSE,
            'statut' => StatutDemande::A_QUALIFIER,
            'motifRejet' => null,
        ],
        [
            'titre' => 'Alarme intrusion declenchement intempestif',
            'description' => 'L\'alarme intrusion du batiment C se declenche chaque nuit vers 3h sans raison apparente.',
            'priorite' => Priorite::P2_HAUTE,
            'statut' => StatutDemande::CLOTURE,
            'motifRejet' => null,
        ],
        [
            'titre' => 'Remplacement extincteurs perimes',
            'description' => '8 extincteurs du batiment B ont depasse leur date de validite et doivent etre remplaces.',
            'priorite' => Priorite::P2_HAUTE,
            'statut' => StatutDemande::CLOTURE,
            'motifRejet' => null,
        ],
        [
            'titre' => 'Prise electrique fondue Bureau 302',
            'description' => 'Une prise electrique du bureau 302 a fondu suite a une surchauffe. Odeur de brule persistante.',
            'priorite' => Priorite::P1_URGENTE,
            'statut' => StatutDemande::CLOTURE,
            'motifRejet' => null,
        ],
        [
            'titre' => 'Robinet qui goutte cuisine 2eme',
            'description' => 'Le robinet de la cuisine du 2eme etage goutte en permanence, meme en position fermee.',
            'priorite' => Priorite::P4_BASSE,
            'statut' => StatutDemande::A_QUALIFIER,
            'motifRejet' => null,
        ],
        [
            'titre' => 'Bruit anormal chaudiere Batiment A',
            'description' => 'La chaudiere du batiment A emet un bruit de claquement metallique toutes les 30 secondes.',
            'priorite' => Priorite::P3_NORMALE,
            'statut' => StatutDemande::A_QUALIFIER,
            'motifRejet' => null,
        ],
        [
            'titre' => 'Badge acces parking ne fonctionne plus',
            'description' => 'Depuis la mise a jour du systeme, les badges d\'acces au parking souterrain ne sont plus reconnus.',
            'priorite' => Priorite::P2_HAUTE,
            'statut' => StatutDemande::REJETEE,
            'motifRejet' => 'Probleme resolu par le service informatique. Pas du ressort de la maintenance.',
        ],
        [
            'titre' => 'Fissure mur exterieur Batiment B',
            'description' => 'Une fissure importante est apparue sur le mur est du batiment B, visible depuis le parking.',
            'priorite' => Priorite::P3_NORMALE,
            'statut' => StatutDemande::REJETEE,
            'motifRejet' => 'Releve du gros oeuvre, pas de la maintenance courante. Transfere au service patrimoine.',
        ],
        [
            'titre' => 'Ventilation insuffisante Salle serveurs',
            'description' => 'Temperature dans la salle serveurs montee a 28 degres. Le systeme de ventilation semble sous-dimensionne.',
            'priorite' => Priorite::P1_URGENTE,
            'statut' => StatutDemande::EN_COURS,
            'motifRejet' => null,
        ],
        [
            'titre' => 'Groupe electrogene a reviser',
            'description' => 'Le groupe electrogene de secours n\'a pas ete teste depuis 6 mois. Revision preventive necessaire.',
            'priorite' => Priorite::P3_NORMALE,
            'statut' => StatutDemande::PLANIFIE,
            'motifRejet' => null,
        ],
        [
            'titre' => 'Detecteurs fumee cuisine a remplacer',
            'description' => 'Les detecteurs de fumee de la cuisine collective emettent des bips reguliers signalant une pile faible.',
            'priorite' => Priorite::P2_HAUTE,
            'statut' => StatutDemande::QUALIFIE,
            'motifRejet' => null,
        ],
        [
            'titre' => 'Monte-charge en panne Entrepot',
            'description' => 'Le monte-charge de l\'entrepot principal est en panne depuis hier. Blocage des livraisons.',
            'priorite' => Priorite::P1_URGENTE,
            'statut' => StatutDemande::PLANIFIE,
            'motifRejet' => null,
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        $organisations = $manager->getRepository(Organisation::class)->findAll();
        $demandeNum = 1;

        foreach ($organisations as $organisation) {
            $users = $manager->getRepository(User::class)->findBy(
                ['organisation' => $organisation],
                ['id' => 'ASC']
            );

            if ($users === []) {
                continue;
            }

            $sites = $manager->getRepository(Site::class)->findBy(
                ['organisation' => $organisation, 'actif' => true],
                ['id' => 'ASC']
            );

            $equipements = $manager->getRepository(Equipement::class)->findBy(
                ['organisation' => $organisation],
                ['id' => 'ASC']
            );

            if ($sites === []) {
                continue;
            }

            foreach (self::DEMANDES as $index => $data) {
                $user = $users[$index % count($users)];
                $site = $sites[$index % count($sites)];
                $equipement = $equipements !== [] ? $equipements[$index % count($equipements)] : null;

                $demande = (new Demande())
                    ->setTitre($data['titre'])
                    ->setDescription($data['description'])
                    ->setPriorite($data['priorite'])
                    ->setStatut($data['statut'])
                    ->setNumero(sprintf('DEM-%04d', $demandeNum))
                    ->setUser($user)
                    ->setSite($site)
                    ->setOrganisation($organisation);

                if ($equipement !== null && $index % 3 !== 2) {
                    $demande->setEquipement($equipement);
                    if ($equipement->getBatiment() !== null) {
                        $demande->setBatiment($equipement->getBatiment());
                    }
                }

                if ($data['motifRejet'] !== null) {
                    $demande->setMotifRejet($data['motifRejet']);
                }

                $manager->persist($demande);
                $demandeNum++;
            }
        }

        $manager->flush();
    }

    /** @return list<class-string> */
    public function getDependencies(): array
    {
        return [
            EquipementFixtures::class,
            UserFixtures::class,
        ];
    }
}
