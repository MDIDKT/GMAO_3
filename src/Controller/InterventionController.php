<?php

namespace App\Controller;

use App\Entity\Demande;
use App\Entity\Intervention;
use App\Entity\User;
use App\Enum\StatutDemande;
use App\Enum\StatutIntervention;
use App\Form\InterventionType;
use App\Repository\InterventionRepository;
use App\Service\InterventionService;
use App\Service\NumberingService;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/intervention')]
final class InterventionController extends AbstractController
{
    public function __construct(
        private readonly NumberingService $numberingService,
        private readonly InterventionService $interventionService
    )
    {
    }
    #[Route(name: 'app_intervention_index', methods: ['GET'])]
    public function index(InterventionRepository $interventionRepository, Request $request): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User || $currentUser->getOrganisation() === null) {
            throw $this->createAccessDeniedException('Utilisateur non rattache a une organisation.');
        }

        $organisation = $currentUser->getOrganisation();
        $statutParam = $request->query->get('statut');
        $statut = is_string($statutParam) ? StatutIntervention::tryFrom($statutParam) : null;

        $page = $request->query->getInt('page', 1);
        $limit = 3;
        $qb = $interventionRepository->getQueryBuilderByFilters($organisation, null, $statut);
        $pagination = $interventionRepository->paginateInterventions($qb, $page, $limit);

        return $this->render('intervention/index.html.twig', [
            'interventions' => $interventionRepository->findByFilters($organisation, null, $statut),
            'pagination' => $pagination,
            'statut' => $statut,
            'statuts' => StatutIntervention::cases(),
        ]);
    }

    #[Route('/new/{demande}', name: 'app_intervention_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Demande $demande, EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            throw $this->createAccessDeniedException('Utilisateur non authentifie.');
        }

        $organisation = $currentUser->getOrganisation();
        if ($organisation === null) {
            throw $this->createAccessDeniedException('Aucune organisation associee a cet utilisateur.');
        }

        if ($demande->getOrganisation() !== $organisation) {
            throw $this->createAccessDeniedException('Cette demande ne vous appartient pas.');
        }

        if ($demande->getStatut() === StatutDemande::CLOTURE || $demande->getStatut() === StatutDemande::REJETEE) {
            $this->addFlash('danger', 'Impossible de planifier une intervention sur une demande clôturée ou rejetée.');
            return $this->redirectToRoute('app_demande_show', ['id' => $demande->getId()]);
        }

        $intervention = new Intervention();
        $intervention->setDemande($demande);
        $intervention->setOrganisation($organisation);

        $form = $this->createForm(InterventionType::class, $intervention, ['organisation' => $organisation]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->interventionService->createIntervention($intervention);

            return $this->redirectToRoute('app_demande_show', ['id' => $demande->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('intervention/new.html.twig', [
            'intervention' => $intervention,
            'form' => $form,
            'demande' => $demande,
        ]);
    }

    #[Route('/{id}', name: 'app_intervention_show', methods: ['GET'])]
    public function show(Intervention $intervention): Response
    {
        return $this->render('intervention/show.html.twig', [
            'intervention' => $intervention,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_intervention_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Intervention $intervention, EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User || $currentUser->getOrganisation() === null) {
            throw $this->createAccessDeniedException('Utilisateur non rattache a une organisation.');
        }

        $form = $this->createForm(InterventionType::class, $intervention, ['organisation' => $currentUser->getOrganisation()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_intervention_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('intervention/edit.html.twig', [
            'intervention' => $intervention,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_intervention_delete', methods: ['POST'])]
    public function delete(Request $request, Intervention $intervention, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $intervention->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($intervention);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_intervention_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/demarrer', name: 'app_intervention_demarrer', methods: ['POST'])]
    public function demarrer(Request $request, Intervention $intervention): Response
    {
        if ($this->isCsrfTokenValid('demarrer' . $intervention->getId(), $request->getPayload()->getString('_token'))) {
            $demande = $intervention->getDemande();
            try {
                $this->interventionService->demarrerIntervention($intervention, $demande);
            } catch (LogicException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }
        return $this->redirectToRoute('app_intervention_show', ['id' => $intervention->getId()]);
    }

    #[Route('/{id}/terminer', name: 'app_intervention_terminer', methods: ['POST'])]
    public function terminer(Request $request, Intervention $intervention): Response
    {
        if ($this->isCsrfTokenValid('terminer' . $intervention->getId(), $request->getPayload()->getString('_token'))) {
            $demande = $intervention->getDemande();
            try {
                $this->interventionService->terminerIntervention($intervention, $demande);
            } catch (LogicException $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }
        return $this->redirectToRoute('app_intervention_show', ['id' => $intervention->getId()]);
    }

}
