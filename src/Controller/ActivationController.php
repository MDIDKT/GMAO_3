<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ActivationController extends AbstractController
{
    #[Route('/activation', name: 'app_activation')]
    public function index(): Response
    {
        return $this->render('activation/index.html.twig', [
            'controller_name' => 'ActivationController',
        ]);
    }
}
