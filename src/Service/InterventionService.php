<?php

namespace App\Service;

use App\Entity\Intervention;
use App\Enum\StatutDemande;
use App\Enum\StatutIntervention;
use Doctrine\ORM\EntityManagerInterface;

class InterventionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly NumberingService $numberingService
    ) {
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
}
