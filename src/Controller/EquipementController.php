<?php

    namespace App\Controller;

    use App\Entity\Equipement;
    use App\Entity\User;
    use App\Enum\StatutEquipement;
    use App\Form\EquipementType;
    use App\Repository\CategorieEquipementRepository;
    use App\Repository\EquipementRepository;
    use App\Repository\SiteRepository;
    use Doctrine\ORM\EntityManagerInterface;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\Component\Security\Http\Attribute\IsGranted;
    use function is_string;

    #[Route('/equipement')]
    #[IsGranted("ROLE_ADMIN")]
    final class EquipementController extends AbstractController
    {
        #[Route(name: 'app_equipement_index', methods: ['GET'])]
        public function index(
            Request $request,
            EquipementRepository $equipementRepository,
            SiteRepository $siteRepository,
            CategorieEquipementRepository $categorieEquipementRepository
        ): Response
        {
            $user = $this->getUser();
            if (!$user instanceof User || $user->getOrganisation() === null) {
                throw $this->createAccessDeniedException('Utilisateur non rattache a une organisation.');
            }

            $organisation = $user->getOrganisation();
            $siteFilter = $request->query->get('site');
            $categorieFilter = $request->query->get('categorie');
            $statutFilter = $request->query->get('statut');
            $actifFilter = $request->query->get('actif');

            $site = null;
            if (is_string($siteFilter) && ctype_digit($siteFilter)) {
                $site = $siteRepository->findOneBy([
                    'id' => (int) $siteFilter,
                    'organisation' => $organisation,
                ]);
            }

            $categorie = null;
            if (is_string($categorieFilter) && ctype_digit($categorieFilter)) {
                $categorie = $categorieEquipementRepository->findOneBy([
                    'id' => (int) $categorieFilter,
                    'organisation' => $organisation,
                ]);
            }

            $statut = is_string($statutFilter) ? StatutEquipement::tryFrom($statutFilter) : null;
            $actif = match ($actifFilter) {
                '1' => true,
                '0' => false,
                default => null,
            };

            return $this->render('equipement/index.html.twig', [
                'equipements' => $equipementRepository->findByFilters(
                    $organisation,
                    $site,
                    $categorie,
                    $statut,
                    $actif
                ),
                'sites' => $siteRepository->findOrganisation($organisation),
                'categories' => $categorieEquipementRepository->findByOrganisation($organisation),
                'statuts' => StatutEquipement::cases(),
                'selectedSite' => $site?->getId(),
                'selectedCategorie' => $categorie?->getId(),
                'selectedStatut' => $statut?->value ?? '',
                'selectedActif' => $actifFilter === '0' || $actifFilter === '1' ? $actifFilter : '',
            ]);
        }

        #[Route('/new', name: 'app_equipement_new', methods: ['GET', 'POST'])]
        public function new(Request $request, EntityManagerInterface $entityManager): Response
        {
            $currentUser = $this->getUser();
            if (!$currentUser instanceof User || $currentUser->getOrganisation() === null) {
                throw $this->createAccessDeniedException('Utilisateur non rattache a une organisation.');
            }

            $organisation = $currentUser->getOrganisation();
            $equipement = new Equipement();
            $equipement->setOrganisation($organisation);
            $form = $this->createForm(EquipementType::class, $equipement, ['organisation' => $organisation]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager->persist($equipement);
                $entityManager->flush();

                return $this->redirectToRoute('app_equipement_index', [], Response::HTTP_SEE_OTHER);
            }

            return $this->render('equipement/new.html.twig', [
                'equipement' => $equipement,
                'form' => $form,
            ]);
        }

        #[Route('/{id}', name: 'app_equipement_show', methods: ['GET'])]
        public function show(Equipement $equipement): Response
        {
            return $this->render('equipement/show.html.twig', [
                'equipement' => $equipement,
            ]);
        }

        #[Route('/{id}/edit', name: 'app_equipement_edit', methods: ['GET', 'POST'])]
        public function edit(Request $request, Equipement $equipement, EntityManagerInterface $entityManager): Response
        {
            $currentUser = $this->getUser();
            if (!$currentUser instanceof User || $currentUser->getOrganisation() === null) {
                throw $this->createAccessDeniedException('Utilisateur non rattache a une organisation.');
            }

            $form = $this->createForm(EquipementType::class, $equipement, ['organisation' => $currentUser->getOrganisation()]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager->flush();

                return $this->redirectToRoute('app_equipement_index', [], Response::HTTP_SEE_OTHER);
            }

            return $this->render('equipement/edit.html.twig', [
                'equipement' => $equipement,
                'form' => $form,
            ]);
        }

        #[Route('/{id}', name: 'app_equipement_delete', methods: ['POST'])]
        public function delete(Request $request, Equipement $equipement, EntityManagerInterface $entityManager): Response
        {
            if ($this->isCsrfTokenValid('delete' . $equipement->getId(), $request->getPayload()->getString('_token'))) {
                $entityManager->remove($equipement);
                $entityManager->flush();
            }

            return $this->redirectToRoute('app_equipement_index', [], Response::HTTP_SEE_OTHER);
        }
    }
