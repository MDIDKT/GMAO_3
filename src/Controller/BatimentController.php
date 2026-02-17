<?php

namespace App\Controller;

use App\Entity\Batiment;
use App\Form\BatimentType;
use App\Repository\BatimentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/batiment')]
final class BatimentController extends AbstractController
{
    #[Route(name: 'app_batiment_index', methods: ['GET'])]
    public function index(BatimentRepository $batimentRepository): Response
    {
        return $this->render('batiment/index.html.twig', [
            'batiments' => $batimentRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_batiment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $batiment = new Batiment();
        $form = $this->createForm(BatimentType::class, $batiment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($batiment);
            $entityManager->flush();

            return $this->redirectToRoute('app_batiment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('batiment/new.html.twig', [
            'batiment' => $batiment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_batiment_show', methods: ['GET'])]
    public function show(Batiment $batiment): Response
    {
        return $this->render('batiment/show.html.twig', [
            'batiment' => $batiment,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_batiment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Batiment $batiment, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BatimentType::class, $batiment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_batiment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('batiment/edit.html.twig', [
            'batiment' => $batiment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_batiment_delete', methods: ['POST'])]
    public function delete(Request $request, Batiment $batiment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$batiment->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($batiment);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_batiment_index', [], Response::HTTP_SEE_OTHER);
    }
}
