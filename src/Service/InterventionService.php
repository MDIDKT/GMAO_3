<?php

declare(strict_types=1);

    namespace App\Service;

    use App\Entity\Demande;
    use App\Entity\Intervention;
    use App\Enum\StatutDemande;
    use App\Enum\StatutIntervention;
    use DateTime;
    use Doctrine\ORM\EntityManagerInterface;
    use LogicException;

    readonly class InterventionService
    {
        public function __construct(
            private EntityManagerInterface $entityManager,
            private NumberingService       $numberingService
        )
        {
        }

        public function createIntervention(Intervention $intervention): void
        {
            if ($intervention->getNumero() === null) {
                $number = $this->numberingService->generateNumero('INT');
                $intervention->setNumero($number);
            }

            // Règle métier Jour 12 : statut initial
            if ($intervention->getTechnicien() && $intervention->getDatePlanifiee()) {
                $intervention->setStatut(StatutIntervention::PLANIFIE);
            } else {
                $intervention->setStatut(StatutIntervention::A_PLANIFIER);
            }

            // Cascade Jour 12 : la demande passe en PLANIFIE
            $demande = $intervention->getDemande();
            if ($demande) {
                $demande->setStatut(StatutDemande::PLANIFIE);
            }

            $this->entityManager->persist($intervention);
            $this->entityManager->flush();
        }

        /**
         * @param \App\Entity\Intervention $intervention
         * @param \App\Entity\Demande $demande
         * @return void
         */
        public function demarrerIntervention(Intervention $intervention, Demande $demande): void
        {
            if ($intervention->getStatut() !== StatutIntervention::PLANIFIE) {
                throw new LogicException('Impossible de démarrer : intervention non planifiée.');
            }

            $intervention->setStatut(StatutIntervention::EN_COURS);
            $demande->setStatut(StatutDemande::EN_COURS);
            if ($intervention->getDateDebut() === null) {
                $intervention->setDateDebut(new DateTime());
            }

            $this->entityManager->persist($intervention);
            $this->entityManager->persist($demande);
            $this->entityManager->flush();
        }

        /**
         * Cette fonction permet de cloturer une intervention en verifiant si elle est en cours. verification que le compte rendu n'est pas vide. passer le status à terminer avec date de fin et calcul de la duree
         * @param \App\Entity\Intervention $intervention
         * @return void/
         * @throws \Exception
         */

        public function terminerIntervention(Intervention $intervention, Demande $demande): void
        {

            if ($intervention->getStatut() !== StatutIntervention::EN_COURS) {
                throw new LogicException('Intervention non démarrée.');
            }
            if ($intervention->getCompteRendu() === null || $intervention->getCompteRendu() === '') {
                throw new LogicException('Compte-rendu obligatoire pour clôturer.');
            }

            $intervention->setStatut(StatutIntervention::TERMINEE);
            if ($intervention->getDateDebut() === null) {
                $intervention->setDateDebut(new DateTime());
            }
            $intervention->setDateFin(new DateTime());
            $debut = $intervention->getDateDebut()->getTimestamp();
            $fin = $intervention->getDateFin()->getTimestamp();
            $intervention->setDureeMinutes((int)(($fin - $debut) / 60));
            // Dette technique : si la relation interventions n'est pas chargée via JOIN FETCH en amont,
            // Doctrine déclenche une requête SQL séparée ici (lazy-loading). Acceptable avec peu d'interventions
            // par demande, mais à surveiller si le volume augmente (optimiser avec un JOIN FETCH dans le controller).
            $toutes = $demande->getInterventions();

            $toutesTerminees = true;
            foreach ($toutes as $inter) {
                if (!in_array($inter->getStatut(), [StatutIntervention::TERMINEE, StatutIntervention::VALIDEE])) {
                    $toutesTerminees = false;
                    break;
                }
            }

            if ($toutesTerminees) {
                $demande->setStatut(StatutDemande::CLOTURE);
            }

            $this->entityManager->persist($intervention);
            $this->entityManager->persist($demande);
            $this->entityManager->flush();
        }
    }
