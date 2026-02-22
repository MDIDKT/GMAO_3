<?php

    namespace App\Controller;

    use App\Entity\User;
    use App\Entity\Demande;
    use App\Enum\StatutDemande;
    use App\Form\DemandeType;
    use App\Repository\DemandeRepository;
    use App\Service\NumberingService;
    use Doctrine\ORM\EntityManagerInterface;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Attribute\Route;

    #[Route('/demande')]
    final class DemandeController extends AbstractController
    {
        public function __construct(private readonly NumberingService $numberingService)
        {

        }

        #[Route(name: 'app_demande_index', methods: ['GET'])]
        public function index(DemandeRepository $demandeRepository): Response
        {

            return $this->render('demande/index.html.twig', [
                'demandes' => $demandeRepository->findBy([], ['createdAt' => 'DESC'])
            ]);
        }

        #[Route('/new', name: 'app_demande_new', methods: ['GET', 'POST'])]
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

            $demande = new Demande();
            $form = $this->createForm(DemandeType::class, $demande);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $number = $this->numberingService->generateNumero('DEM');
                $demande->setNumero($number);
                $demande->setStatut(StatutDemande::A_QUALIFIER);
                $demande->setUser($currentUser);
                $demande->setOrganisation($organisation);
                $entityManager->persist($demande);
                $entityManager->flush();

                return $this->redirectToRoute('app_demande_index', [], Response::HTTP_SEE_OTHER);
            }

            return $this->render('demande/new.html.twig', [
                'demande' => $demande,
                'form' => $form,
            ]);
        }

        #[Route('/{id}', name: 'app_demande_show', methods: ['GET'])]
        public function show(Demande $demande): Response
        {
            return $this->render('demande/show.html.twig', [
                'demande' => $demande,
            ]);
        }

        #[Route('/{id}/edit', name: 'app_demande_edit', methods: ['GET', 'POST'])]
        public function edit(Request $request, Demande $demande, EntityManagerInterface $entityManager): Response
        {
            $form = $this->createForm(DemandeType::class, $demande);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager->flush();

                return $this->redirectToRoute('app_demande_index', [], Response::HTTP_SEE_OTHER);
            }

            return $this->render('demande/edit.html.twig', [
                'demande' => $demande,
                'form' => $form,
            ]);
        }

        #[Route('/{id}', name: 'app_demande_delete', methods: ['POST'])]
        public function delete(Request $request, Demande $demande, EntityManagerInterface $entityManager): Response
        {
            if ($this->isCsrfTokenValid('delete' . $demande->getId(), $request->getPayload()->getString('_token'))) {
                $entityManager->remove($demande);
                $entityManager->flush();
            }

            return $this->redirectToRoute('app_demande_index', [], Response::HTTP_SEE_OTHER);
        }
    }
