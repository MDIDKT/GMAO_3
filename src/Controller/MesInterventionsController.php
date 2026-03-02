<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\StatutIntervention;
use App\Repository\InterventionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MesInterventionsController extends AbstractController
{
    #[Route('/mes-interventions', name: 'app_intervention_mes_interventions', methods: ['GET'])]
    public function mesInterventions(InterventionRepository $interventionRepository, Request $request): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User || $currentUser->getOrganisation() === null) {
            throw $this->createAccessDeniedException('Utilisateur non rattache a une organisation.');
        }

        $organisation = $currentUser->getOrganisation();
        $statutInter = $request->query->get('statut');
        $statut = is_string($statutInter) ? StatutIntervention::tryFrom($statutInter) : null;

        $qb = $interventionRepository->getQueryBuilderByFilters($organisation, $currentUser, $statut);
        $pagination = $interventionRepository->paginateInterventions($qb, $request->query->getInt('page', 1), 3);
        return $this->render('mes_interventions/index.html.twig', [
            'interventions' => $interventionRepository->findByFilters($organisation, $currentUser, $statut),
            'pagination' => $pagination,
            'statut' => $statut,
            'statuts' => StatutIntervention::cases(),
        ]);

    }

}


