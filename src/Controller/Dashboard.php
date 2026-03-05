<?php

    namespace App\Controller;

    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Attribute\Route;

    final class Dashboard extends AbstractController
    {
        #[Route('/dashboard', name: 'app_Dashboard')]
        public function index(): Response
        {
            return $this->render('dashboard/dashborad.html.twig', [
                'controller_name' => 'Dashboard',
            ]);
        }

    }
