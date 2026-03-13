<?php

namespace App\Controller;

use App\Entity\CategorieEquipement;
use App\Entity\User;
use App\Form\CategorieEquipementType;
use App\Repository\CategorieEquipementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/categories-equipement')]
#[IsGranted('ROLE_ADMIN')]
final class CategorieEquipementController extends AbstractController
{
    #[Route(name: 'app_categorie_equipement_index', methods: ['GET'])]
    public function index(Request $request, CategorieEquipementRepository $categorieEquipementRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || $user->getOrganisation() === null) {
            throw $this->createAccessDeniedException('Utilisateur non rattache a une organisation.');
        }

        $organisation = $user->getOrganisation();
        $qb = $categorieEquipementRepository->getQueryBuilderByOrganisation($organisation);
        $pagination = $categorieEquipementRepository->paginateCategories($qb, $request->query->getInt('page', 1), 5);

        return $this->render('categorie_equipement/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_categorie_equipement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || $user->getOrganisation() === null) {
            throw $this->createAccessDeniedException('Utilisateur non rattache a une organisation.');
        }

        $categorie = new CategorieEquipement();
        $categorie->setOrganisation($user->getOrganisation());
        $form = $this->createForm(CategorieEquipementType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($categorie);
            $entityManager->flush();

            $this->addFlash('success', 'Categorie creee avec succes.');

            return $this->redirectToRoute('app_categorie_equipement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('categorie_equipement/new.html.twig', [
            'categorie' => $categorie,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_categorie_equipement_show', methods: ['GET'])]
    public function show(CategorieEquipement $categorie): Response
    {
        $this->denyAccessUnlessSameOrganisation($categorie);

        return $this->render('categorie_equipement/show.html.twig', [
            'categorie' => $categorie,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_categorie_equipement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CategorieEquipement $categorie, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessSameOrganisation($categorie);

        $form = $this->createForm(CategorieEquipementType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Categorie mise a jour.');

            return $this->redirectToRoute('app_categorie_equipement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('categorie_equipement/edit.html.twig', [
            'categorie' => $categorie,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_categorie_equipement_delete', methods: ['POST'])]
    public function delete(Request $request, CategorieEquipement $categorie, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessSameOrganisation($categorie);

        if ($this->isCsrfTokenValid('delete' . $categorie->getId(), $request->getPayload()->getString('_token'))) {
            // Verifier les dependances avant suppression
            if ($categorie->getEquipements()->count() > 0) {
                $this->addFlash('danger', 'Impossible de supprimer cette categorie : elle contient encore ' . $categorie->getEquipements()->count() . ' equipement(s).');
                return $this->redirectToRoute('app_categorie_equipement_show', ['id' => $categorie->getId()]);
            }

            $entityManager->remove($categorie);
            $entityManager->flush();
            $this->addFlash('success', 'Categorie supprimee.');
        }

        return $this->redirectToRoute('app_categorie_equipement_index', [], Response::HTTP_SEE_OTHER);
    }

    private function denyAccessUnlessSameOrganisation(CategorieEquipement $categorie): void
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User || $currentUser->getOrganisation() === null) {
            throw $this->createAccessDeniedException('Utilisateur non rattache a une organisation.');
        }

        if ($categorie->getOrganisation() !== $currentUser->getOrganisation()) {
            throw $this->createAccessDeniedException('Acces interdit a cette categorie.');
        }
    }
}
