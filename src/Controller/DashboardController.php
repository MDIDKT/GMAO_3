<?php

    namespace App\Controller;

    use App\Entity\User;
    use App\Repository\DemandeRepository;
    use App\Repository\InterventionRepository;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\Component\Security\Http\Attribute\IsGranted;

    #[IsGranted('ROLE_PLANIFICATEUR')]
    final class DashboardController extends AbstractController
    {
        #[Route('/dashboard', name: 'app_dashboard_index')]
        public function index(DemandeRepository $demandeRepository, InterventionRepository $interventionRepository): Response
        {
            $user = $this->getUser();
            if (!$user instanceof User || $user->getOrganisation() === null) {
                throw $this->createAccessDeniedException('Utilisateur non rattaché à une organisation.');
            }

            $organisation = $user->getOrganisation();

            return $this->render('dashboard/dashboard.html.twig', [
                'countP1Ouvertes' => $demandeRepository->countP1Ouvertes($organisation),
                'countAQualifier' => $demandeRepository->countAQualifier($organisation),
                'countInterventionsDuJour' => $interventionRepository->countInterventionsDuJour($organisation),
                'countInterventionsEnRetard' => $interventionRepository->countInterventionsEnRetard($organisation),
                'priorityDemandes' => $demandeRepository->findDashboardPriorityDemandes($organisation),
                'interventionsDuJour' => $interventionRepository->findDashboardInterventionsDuJour($organisation),
            ]);
        }

    }
