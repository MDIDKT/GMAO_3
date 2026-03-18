<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\StatutDemande;
use App\Repository\DemandeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted("ROLE_DEMANDEUR")]
final class MesDemandesController extends AbstractController
{
    #[Route('/mes-demandes', name: 'app_demande_mes_demandes', methods: ['GET'])]
    public function mesdemandes(demandeRepository $demandeRepository, Request $request): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User || $currentUser->getOrganisation() === null) {
            throw $this->createAccessDeniedException('Utilisateur non rattache a une organisation.');
        }

        $organisation = $currentUser->getOrganisation();
        $statutInter = $request->query->get('statut');
        $priorite = $request->query->get('priorite');
        $search = $request->query->get('search');
        $statut = is_string($statutInter) ? StatutDemande::tryFrom($statutInter) : null;

        $qb = $demandeRepository->getQueryBuilderByFilters($organisation, null, $statut, null, $search, $currentUser);
        $pagination = $demandeRepository->paginateDemandes($qb, $request->query->getInt('page', 1), 5);
        return $this->render('mes_demandes/index.html.twig', [
            'pagination' => $pagination,
            'statut' => $statut,
            'statuts' => [
                StatutDemande::PLANIFIE,
                StatutDemande::EN_COURS,
                StatutDemande::CLOTURE,
                StatutDemande::REJETEE,
            ],
        ]);
    }
}
