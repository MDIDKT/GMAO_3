<?php

    namespace App\Controller;

    use App\Entity\Demande;
    use App\Entity\Photo;
    use App\Entity\User;
    use App\Enum\Priorite;
    use App\Enum\StatutDemande;
    use App\Enum\TypePhoto;
    use App\Form\DemandeType;
    use App\Repository\DemandeRepository;
    use App\Repository\SiteRepository;
    use App\Service\FileUploadService;
    use App\Service\NotificationService;
    use App\Service\NumberingService;
    use Doctrine\ORM\EntityManagerInterface;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\BinaryFileResponse;
    use Symfony\Component\HttpFoundation\File\UploadedFile;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\HttpFoundation\ResponseHeaderBag;
    use Symfony\Component\Routing\Attribute\Route;

    #[Route('/demande')]
    final class DemandeController extends AbstractController
    {
        public function __construct(
            private readonly NumberingService $numberingService,
            private readonly NotificationService $notificationService,
        ) {
        }

        #[Route(name: 'app_demande_index', methods: ['GET'])]
        public function index(DemandeRepository $demandeRepository, Request $request, SiteRepository $siteRepository): Response
        {
            $currentUser = $this->getUser();
            if (!$currentUser instanceof User || $currentUser->getOrganisation() === null) {
                throw $this->createAccessDeniedException('Utilisateur non rattache a une organisation.');
            }

            $organisation = $currentUser->getOrganisation();
            $prioriteParam = $request->query->get('priorite');
            $statutParam = $request->query->get('statut');
            $siteParam = $request->query->get('site');
            $search = $request->query->get('search');

            $statut = is_string($statutParam) ? StatutDemande::tryFrom($statutParam) : null;
            $priorite = is_string($prioriteParam) ? Priorite::tryFrom($prioriteParam) : null;
            $site = null;
            if (is_string($siteParam) && ctype_digit($siteParam)) {
                $site = $siteRepository->findOneBy([
                    'id' => (int)$siteParam,
                    'organisation' => $organisation,
                ]);
            }

            $page = $request->query->getInt('page', 1);
            $limit = 5;
            $qb = $demandeRepository->getQueryBuilderByFilters($organisation, $site, $statut, $priorite, $search);
            $pagination = $demandeRepository->paginateDemandes($qb, $page, $limit);

            return $this->render('demande/index.html.twig', [
                'pagination' => $pagination,
                'urgentCount' => $demandeRepository->countUrgent($organisation),
                'openCount' => $demandeRepository->countOpen($organisation),
                'closedCount' => $demandeRepository->countClosed($organisation),
                'totalCount' => $demandeRepository->countTotal($organisation),
                'priorite' => $priorite,
                'statut' => $statut,
                'site' => $site,
                'search' => $search,
                'selectedSite' => $site?->getId(),
                'selectedPriorite' => $priorite?->value ?? '',
                'selectedStatut' => $statut?->value ?? '',
                'selectedSearch' => $search ?? '',
                'sites' => $siteRepository->findBy(['organisation' => $organisation, 'actif' => true], ['nom' => 'ASC']),
                'priorites' => Priorite::cases(),
                'statuts' => StatutDemande::cases(),
            ]);
        }

        #[Route('/new', name: 'app_demande_new', methods: ['GET', 'POST'])]
        public function new(Request $request, EntityManagerInterface $entityManager, FileUploadService $fileUploadService): Response
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
            $form = $this->createForm(DemandeType::class, $demande, ['organisation' => $organisation]);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $photoFiles = $form->get('photos')->getData() ?? [];
                if (!is_array($photoFiles)) {
                    $photoFiles = [$photoFiles];
                }
                $number = $this->numberingService->generateNumero('DEM');
                $demande->setNumero($number);
                $demande->setStatut(StatutDemande::A_QUALIFIER);
                $demande->setUser($currentUser);
                $demande->setOrganisation($organisation);

                if ($photoFiles) {
                    foreach ($photoFiles as $photoFile) {
                        if (!$photoFile instanceof UploadedFile || !$photoFile->isValid()) {
                            continue;
                        }

                        $originalName = $photoFile->getClientOriginalName();
                        $mimeType = $photoFile->getMimeType() ?? $photoFile->getClientMimeType();
                        $size = (int)($photoFile->getSize() ?? 0);

                        $filename = $fileUploadService->upload($photoFile);
                        $photo = new Photo();
                        $photo->setFileName($filename);
                        $photo->setOriginalName($originalName);
                        $photo->setMimeType($mimeType);
                        $photo->setTaille($size);
                        $photo->setTypePhoto(TypePhoto::SIGNALEMENT);
                        $photo->setDemande($demande);
                        $photo->setUploadPar($currentUser);
                        $entityManager->persist($photo);
                    }
                }
                $entityManager->persist($demande);
                $entityManager->flush();
                $this->addFlash('success', 'Demande ' . $demande->getNumero() . ' créée avec succès.');
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
            $this->denyAccessUnlessGranted('DEMANDE_VIEW', $demande);
            return $this->render('demande/show.html.twig', [
                'demande' => $demande,
            ]);
        }

        #[Route('/photos/{id}', name: 'photo_show', methods: ['GET'])]
        public function showPhoto(Photo $photo): BinaryFileResponse
        {
            $filename = $photo->getFileName();
            if ($filename === null || $filename === '') {
                throw $this->createNotFoundException('Fichier photo introuvable.');
            }

            $filePath = rtrim((string)$this->getParameter('upload_directory'), '/') . '/' . $filename;
            if (!is_file($filePath) || !is_readable($filePath)) {
                throw $this->createNotFoundException('Fichier photo introuvable sur le serveur.');
            }

            $response = new BinaryFileResponse($filePath);
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_INLINE,
                $photo->getOriginalName() ?: basename($filePath)
            );

            $mimeType = $photo->getMimeType();
            if ($mimeType !== null && $mimeType !== '') {
                $response->headers->set('Content-Type', $mimeType);
            }

            return $response;
        }

        #[Route('/{id}/qualifier', name: 'app_demande_qualifier', methods: ['POST'])]
        public function qualifier(Request $request, Demande $demande, EntityManagerInterface $entityManager): Response
        {
            if (!$this->isGranted('ROLE_PLANIFICATEUR') && !$this->isGranted('ROLE_ADMIN')) {
                throw $this->createAccessDeniedException('Seul un planificateur ou admin peut qualifier une demande.');
            }

            if (!$this->isCsrfTokenValid('qualifier' . $demande->getId(), $request->getPayload()->getString('_token'))) {
                $this->addFlash('danger', 'Token CSRF invalide.');
                return $this->redirectToRoute('app_demande_show', ['id' => $demande->getId()]);
            }

            if ($demande->getStatut() !== StatutDemande::A_QUALIFIER) {
                $this->addFlash('danger', 'Cette demande ne peut pas etre qualifiee (statut actuel : ' . $demande->getStatut()->label() . ').');
                return $this->redirectToRoute('app_demande_show', ['id' => $demande->getId()]);
            }

            $demande->setStatut(StatutDemande::QUALIFIE);
            $entityManager->flush();
            $this->notificationService->notifyDemandeQualifiee($demande);

            $this->addFlash('success', 'Demande qualifiee avec succes.');
            return $this->redirectToRoute('app_demande_show', ['id' => $demande->getId()]);
        }

        #[Route('/{id}/rejeter', name: 'app_demande_rejeter', methods: ['POST'])]
        public function rejeter(Request $request, Demande $demande, EntityManagerInterface $entityManager): Response
        {
            if (!$this->isGranted('ROLE_PLANIFICATEUR') && !$this->isGranted('ROLE_ADMIN')) {
                throw $this->createAccessDeniedException('Seul un planificateur ou admin peut rejeter une demande.');
            }

            if (!$this->isCsrfTokenValid('rejeter' . $demande->getId(), $request->getPayload()->getString('_token'))) {
                $this->addFlash('danger', 'Token CSRF invalide.');
                return $this->redirectToRoute('app_demande_show', ['id' => $demande->getId()]);
            }

            if ($demande->getStatut() !== StatutDemande::A_QUALIFIER && $demande->getStatut() !== StatutDemande::QUALIFIE) {
                $this->addFlash('danger', 'Cette demande ne peut pas etre rejetee (statut actuel : ' . $demande->getStatut()->label() . ').');
                return $this->redirectToRoute('app_demande_show', ['id' => $demande->getId()]);
            }

            $motifRejet = trim((string) $request->request->get('motif_rejet', ''));
            if ($motifRejet === '') {
                $this->addFlash('danger', 'Le motif de rejet est obligatoire.');
                return $this->redirectToRoute('app_demande_show', ['id' => $demande->getId()]);
            }

            $demande->setMotifRejet($motifRejet);
            $demande->setStatut(StatutDemande::REJETEE);
            $entityManager->flush();
            $this->notificationService->notifyDemandeRejetee($demande);

            $this->addFlash('success', 'Demande rejetee.');
            return $this->redirectToRoute('app_demande_show', ['id' => $demande->getId()]);
        }

        #[Route('/{id}/edit', name: 'app_demande_edit', methods: ['GET', 'POST'])]
        public function edit(Request $request, Demande $demande, EntityManagerInterface $entityManager, FileUploadService $fileUploadService): Response
        {
            $this->denyAccessUnlessGranted('DEMANDE_EDIT', $demande);
            $currentUser = $this->getUser();
            if (!$currentUser instanceof User) {
                throw $this->createAccessDeniedException('Utilisateur non authentifie.');
            }

            $form = $this->createForm(DemandeType::class, $demande, ['organisation' => $currentUser->getOrganisation()]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $photoFiles = $form->get('photos')->getData() ?? [];
                if (!is_array($photoFiles)) {
                    $photoFiles = [$photoFiles];
                }

                foreach ($photoFiles as $photoFile) {
                    if (!$photoFile instanceof UploadedFile || !$photoFile->isValid()) {
                        continue;
                    }

                    $originalName = $photoFile->getClientOriginalName();
                    $mimeType = $photoFile->getMimeType() ?? $photoFile->getClientMimeType();
                    $size = (int)($photoFile->getSize() ?? 0);

                    $filename = $fileUploadService->upload($photoFile);
                    $photo = new Photo();
                    $photo->setFileName($filename);
                    $photo->setOriginalName($originalName);
                    $photo->setMimeType($mimeType);
                    $photo->setTaille($size);
                    $photo->setTypePhoto(TypePhoto::SIGNALEMENT);
                    $photo->setDemande($demande);
                    $photo->setUploadPar($currentUser);
                    $entityManager->persist($photo);
                }

                $entityManager->flush();
                $this->addFlash('success', 'Demande ' . $demande->getNumero() . ' modifiée avec succès.');

                return $this->redirectToRoute('app_demande_show', ['id' => $demande->getId()], Response::HTTP_SEE_OTHER);
            }

            return $this->render('demande/edit.html.twig', [
                'demande' => $demande,
                'form' => $form,
            ]);
        }

        #[Route('/{id}', name: 'app_demande_delete', methods: ['POST'])]
        public function delete(Request $request, Demande $demande, EntityManagerInterface $entityManager): Response
        {
            $this->denyAccessUnlessGranted('DEMANDE_DELETE', $demande);
            if ($this->isCsrfTokenValid('delete' . $demande->getId(), $request->getPayload()->getString('_token'))) {
                $entityManager->remove($demande);
                $entityManager->flush();
                $this->addFlash('success', 'Demande supprimée.');
            }

            return $this->redirectToRoute('app_demande_index', [], Response::HTTP_SEE_OTHER);
        }
    }
