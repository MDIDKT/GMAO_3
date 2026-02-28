<?php

namespace App\Controller;

use App\Entity\Intervention;
use App\Entity\User;
use App\Enum\StatutIntervention;
use App\Form\InterventionType;
use App\Repository\InterventionRepository;
use App\Service\NumberingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/intervention')]
final class InterventionController extends AbstractController
{
    public function __construct(private readonly NumberingService $numberingService)
    {

    }
    #[Route(name: 'app_intervention_index', methods: ['GET'])]
    public function index(InterventionRepository $interventionRepository, Request $request): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User || $currentUser->getOrganisation() === null) {
            throw $this->createAccessDeniedException('Utilisateur non rattache a une organisation.');
        }
        $statut = $request->query->get('statut');
        $statut = is_string($statut) ? StatutIntervention::tryFrom($statut) : null;
        return $this->render('intervention/index.html.twig', [
            'interventions' => $interventionRepository->findByFilters($currentUser->getOrganisation(), $currentUser->getUserIdentifier()),
            'statut' => $statut,
        ]);
    }

    #[Route('/new', name: 'app_intervention_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            throw $this->createAccessDeniedException('Utilisateur non authentifie.');
        }

        $organisation = $currentUser->getOrganisation();
        if ($organisation === null) {
            throw $this->createAccessDeniedException('Aucune organisation associee a cet utilisateur.');
        }

        $intervention = new Intervention();
        $form = $this->createForm(InterventionType::class, $intervention, ['organisation' => $organisation]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $number = $this->numberingService->generateNumero('INT');
            $intervention->setNumero($number);
            $intervention->setStatut(StatutIntervention::A_PLANIFIER);
            $intervention->setTechnicien($currentUser);
            $intervention->setPlanificateur($currentUser);
            $intervention->setOrganisation($organisation);
            $entityManager->persist($intervention);
            $entityManager->flush();

            return $this->redirectToRoute('app_intervention_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('intervention/new.html.twig', [
            'intervention' => $intervention,
            'form' => $form,
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
        if ($this->isCsrfTokenValid('delete'.$intervention->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($intervention);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_intervention_index', [], Response::HTTP_SEE_OTHER);
    }
}
