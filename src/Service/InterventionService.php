<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Demande;
use App\Entity\Intervention;
use App\Enum\StatutDemande;
use App\Enum\StatutIntervention;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Psr\Log\LoggerInterface;

readonly class InterventionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NumberingService $numberingService,
        private NotificationService $notificationService,
        private LoggerInterface $logger,
    ) {
    }

    public function createIntervention(Intervention $intervention): void
    {
        if ($intervention->getNumero() === null) {
            $number = $this->numberingService->generateNumero('INT');
            $intervention->setNumero($number);
        }

        if ($intervention->getTechnicien() && $intervention->getDatePlanifiee()) {
            $intervention->setStatut(StatutIntervention::PLANIFIE);
        } else {
            $intervention->setStatut(StatutIntervention::A_PLANIFIER);
        }

        $demande = $intervention->getDemande();
        if ($demande instanceof Demande) {
            $demande->setStatut(StatutDemande::PLANIFIE);
            $demande->addIntervention($intervention);
        }

        $this->entityManager->persist($intervention);

        try {
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors de la création de l\'intervention : ' . $e->getMessage());
            throw new \RuntimeException('Impossible de créer l\'intervention. Veuillez réessayer.', 0, $e);
        }

        $this->notificationService->notifyTechnicienAssigne($intervention);
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
            $intervention->setDateDebut(new DateTimeImmutable());
        }

        $this->entityManager->persist($intervention);
        $this->entityManager->persist($demande);

        try {
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors du démarrage de l\'intervention : ' . $e->getMessage());
            throw new \RuntimeException('Impossible de démarrer l\'intervention. Veuillez réessayer.', 0, $e);
        }

        $this->notificationService->notifyInterventionDemarree($intervention);
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
        $intervention->setDateFin(new DateTimeImmutable());
        $debut = $intervention->getDateDebut()->getTimestamp();
        $fin = $intervention->getDateFin()->getTimestamp();
        $intervention->setDureeMinutes((int) (($fin - $debut) / 60));

        if (!$demande->getInterventions()->contains($intervention)) {
            $demande->addIntervention($intervention);
        }

        $toutesTerminees = true;
        foreach ($demande->getInterventions() as $inter) {
            if (!in_array($inter->getStatut(), [StatutIntervention::TERMINEE, StatutIntervention::VALIDEE], true)) {
                $toutesTerminees = false;
                break;
            }
        }

        if ($toutesTerminees) {
            $demande->setStatut(StatutDemande::CLOTURE);
        }

        $this->entityManager->persist($intervention);
        $this->entityManager->persist($demande);

        try {
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors de la clôture de l\'intervention : ' . $e->getMessage());
            throw new \RuntimeException('Impossible de terminer l\'intervention. Veuillez réessayer.', 0, $e);
        }

        $this->notificationService->notifyInterventionTerminee($intervention);
        if ($demande->getStatut() === StatutDemande::CLOTURE) {
            $this->notificationService->notifyDemandeCloturee($demande);
        }
    }
}
