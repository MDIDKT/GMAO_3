<?php

    namespace App\Controller;

    use App\Entity\Batiment;
    use App\Entity\User;
    use App\Form\BatimentType;
    use App\Repository\BatimentRepository;
    use App\Security\Voter\BatimentVoter;
    use Doctrine\ORM\EntityManagerInterface;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\Component\Security\Http\Attribute\IsGranted;

    #[Route('/batiment')]
    #[IsGranted("ROLE_ADMIN")]
    final class BatimentController extends AbstractController
    {
        #[Route(name: 'app_batiment_index', methods: ['GET'])]
        public function index(Request $request, BatimentRepository $batimentRepository): Response
        {
            $user = $this->getUser();
            if (!$user instanceof User || $user->getOrganisation() === null) {
                throw $this->createAccessDeniedException('Utilisateur non rattache a une organisation.');
            }

            $organisation = $user->getOrganisation();
            $actifFilter = $request->query->get('actif');
            $actif = match ($actifFilter) {
                '1' => true,
                '0' => false,
                default => null,
            };

            $qb = $batimentRepository->getQueryBuilderByOrganisation($organisation, $actif);
            $pagination = $batimentRepository->paginateBatiments($qb, $request->query->getInt('page', 1), 5);

            return $this->render('batiment/index.html.twig', [
                'pagination' => $pagination,
                'activeCount' => $batimentRepository->countActive($organisation),
                'totalCount' => $batimentRepository->countTotal($organisation),
                'selectedActif' => $actifFilter === '0' || $actifFilter === '1' ? $actifFilter : '',
            ]);
        }

        #[Route('/new', name: 'app_batiment_new', methods: ['GET', 'POST'])]
        public function new(Request $request, EntityManagerInterface $entityManager): Response
        {
            $currentUser = $this->getUser();
            if (!$currentUser instanceof User || $currentUser->getOrganisation() === null) {
                throw $this->createAccessDeniedException('Utilisateur non rattache a une organisation.');
            }

            $organisation = $currentUser->getOrganisation();
            $batiment = new Batiment();
            $form = $this->createForm(BatimentType::class, $batiment, ['organisation' => $organisation]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager->persist($batiment);
                $entityManager->flush();
                $this->addFlash('success', 'Le batiment ' . $batiment->getNom() . ' a été créée avec succès.');
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
            $this->denyAccessUnlessGranted(BatimentVoter::VIEW, $batiment);

            return $this->render('batiment/show.html.twig', [
                'batiment' => $batiment,
            ]);
        }

        #[Route('/{id}/edit', name: 'app_batiment_edit', methods: ['GET', 'POST'])]
        public function edit(Request $request, Batiment $batiment, EntityManagerInterface $entityManager): Response
        {
            $this->denyAccessUnlessGranted(BatimentVoter::EDIT, $batiment);

            $currentUser = $this->getUser();
            if (!$currentUser instanceof User || $currentUser->getOrganisation() === null) {
                throw $this->createAccessDeniedException('Utilisateur non rattache a une organisation.');
            }

            $form = $this->createForm(BatimentType::class, $batiment, ['organisation' => $currentUser->getOrganisation()]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager->flush();
                $this->addFlash('success', 'Le batiment ' . $batiment->getNom() . ' a été modifié avec succès.');
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
            $this->denyAccessUnlessGranted(BatimentVoter::DELETE, $batiment);

            if ($this->isCsrfTokenValid('delete' . $batiment->getId(), $request->getPayload()->getString('_token'))) {
                // Verifier les dependances avant suppression
                if ($batiment->getEquipements()->count() > 0) {
                    $this->addFlash('danger', 'Impossible de supprimer ce batiment : il contient encore ' . $batiment->getEquipements()->count() . ' equipement(s).');
                    return $this->redirectToRoute('app_batiment_show', ['id' => $batiment->getId()]);
                }
                if ($batiment->getDemandes()->count() > 0) {
                    $this->addFlash('danger', 'Impossible de supprimer ce batiment : il est lie a ' . $batiment->getDemandes()->count() . ' demande(s).');
                    return $this->redirectToRoute('app_batiment_show', ['id' => $batiment->getId()]);
                }

                $entityManager->remove($batiment);
                $entityManager->flush();
                $this->addFlash('success', 'Le batiment ' . $batiment->getNom() . ' a ete supprime avec succes.');
            }
            return $this->redirectToRoute('app_batiment_index', [], Response::HTTP_SEE_OTHER);
        }
    }
