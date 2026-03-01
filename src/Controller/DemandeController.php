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
        public function __construct(private readonly NumberingService $numberingService)
        {

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
            $limit = 3;
            $qb = $demandeRepository->getQueryBuilderByFilters($organisation, $site, $statut, $priorite, $search);
            $pagination = $demandeRepository->paginateDemandes($qb, $page, $limit);

            // Pour les widgets, on récupère tous les résultats filtrés sans pagination
            $filteredDemandes = $demandeRepository->findByFilters($organisation, $site, $statut, $priorite, $search);

            return $this->render('demande/index.html.twig', [
                'demandes' => $filteredDemandes,
                'pagination' => $pagination,
                'priorite' => $priorite,
                'statut' => $statut,
                'site' => $site,
                'search' => $search,
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

        #[Route('/{id}/edit', name: 'app_demande_edit', methods: ['GET', 'POST'])]
        public function edit(Request $request, Demande $demande, EntityManagerInterface $entityManager, FileUploadService $fileUploadService): Response
        {
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
