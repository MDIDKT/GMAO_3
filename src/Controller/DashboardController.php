<?php

    namespace App\Controller;

    use App\Repository\DemandeRepository;
    use App\Repository\InterventionRepository;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\Component\Security\Http\Attribute\IsGranted;

    #[IsGranted('ROLE_USER')]
    final class DashboardController extends AbstractController
    {
        #[Route('/dashboard', name: 'app_Dashboard')]
        public function index(DemandeRepository $demandeRepository, InterventionRepository $interventionRepository): Response
        {
//            $this->denyAccessUnlessGranted('ROLE_PLANIFICATEUR') || $this->denyAccessUnlessGranted('ROLE_ADMIN');

            $organisation = $this->getUser()->getOrganisation();

            return $this->render('dashboard/dashborad.html.twig', [
                'countP1Ouvertes' => $demandeRepository->countP1Ouvertes($organisation),
                'countAQualifier' => $demandeRepository->countAQualifier($organisation),
                'countInterventionsDuJour' => $interventionRepository->countInterventionsDuJour($organisation),
                'countInterventionsEnRetard' => $interventionRepository->countInterventionsEnRetard($organisation),
            ]);
        }

    }
