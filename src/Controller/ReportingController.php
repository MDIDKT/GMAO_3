<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Site;
use App\Repository\DemandeRepository;
use App\Repository\InterventionRepository;
use App\Repository\SiteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/reporting')]
class ReportingController extends AbstractController
{
    #[Route('', name: 'app_reporting', methods: ['GET'])]
    public function index(
        Request $request,
        DemandeRepository $demandeRepository,
        InterventionRepository $interventionRepository,
        SiteRepository $siteRepository,
    ): Response {
        $user = $this->getUser();
        $organisation = $user->getOrganisation();

        // Filtres
        $siteId = $request->query->get('site');
        $dateDebutStr = $request->query->get('date_debut');
        $dateFinStr = $request->query->get('date_fin');

        $site = null;
        if ($siteId !== null && $siteId !== '') {
            $site = $siteRepository->find((int) $siteId);
            if ($site instanceof Site && $site->getOrganisation() !== $organisation) {
                $site = null;
            }
        }

        $dateDebut = null;
        if ($dateDebutStr !== null && $dateDebutStr !== '') {
            $dateDebut = \DateTimeImmutable::createFromFormat('Y-m-d', $dateDebutStr);
            if ($dateDebut === false) {
                $dateDebut = null;
            } else {
                $dateDebut = $dateDebut->setTime(0, 0);
            }
        }

        $dateFin = null;
        if ($dateFinStr !== null && $dateFinStr !== '') {
            $dateFin = \DateTimeImmutable::createFromFormat('Y-m-d', $dateFinStr);
            if ($dateFin === false) {
                $dateFin = null;
            } else {
                $dateFin = $dateFin->setTime(23, 59, 59);
            }
        }

        // Sites pour le select filtre
        $sites = $siteRepository->findBy(
            ['organisation' => $organisation, 'actif' => true],
            ['nom' => 'ASC']
        );

        // KPI 1 : Demandes par statut
        $demandesParStatut = $demandeRepository->countByStatut($organisation, $site, $dateDebut, $dateFin);

        // KPI 2 : Delai moyen de traitement (en heures)
        $delaiMoyen = $demandeRepository->delaiMoyenTraitement($organisation, $site, $dateDebut, $dateFin);

        // KPI 3 : Interventions par technicien
        $interventionsParTechnicien = $interventionRepository->countByTechnicien($organisation, $dateDebut, $dateFin);

        // KPI 4 : Demandes par site et priorite
        $demandesParSitePriorite = $demandeRepository->countBySiteAndPriorite($organisation, $dateDebut, $dateFin);

        return $this->render('reporting/index.html.twig', [
            'sites' => $sites,
            'selectedSite' => $siteId,
            'dateDebut' => $dateDebutStr,
            'dateFin' => $dateFinStr,
            'demandesParStatut' => $demandesParStatut,
            'delaiMoyen' => $delaiMoyen,
            'interventionsParTechnicien' => $interventionsParTechnicien,
            'demandesParSitePriorite' => $demandesParSitePriorite,
        ]);
    }
}
