<?php

    namespace App\Controller;

    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Attribute\Route;

    final class HomeController extends AbstractController
    {
        #[Route('/', name: 'app_home')]
        public function index(): Response
        {
            if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_PLANIFICATEUR')) {
                return $this->redirectToRoute('app_Dashboard');
            } elseif ($this->isGranted('ROLE_TECHNICIEN')) {
                return $this->redirectToRoute('app_intervention_mes_interventions');
            } elseif ($this->isGranted('ROLE_DEMANDEUR')) {
                return $this->redirectToRoute('app_demande_mes_demandes');
            }
            return $this->render('home/index.html.twig', [
                'controller_name' => 'HomeController',
            ]);
        }
    }
