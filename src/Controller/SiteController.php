<?php

namespace App\Controller;

use App\Entity\Site;
use App\Entity\User;
use App\Form\SiteType;
use App\Repository\SiteRepository;
use App\Security\Voter\SiteVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/site')]
#[IsGranted("ROLE_ADMIN")]
final class SiteController extends AbstractController
{
    #[Route(name: 'app_site_index', methods: ['GET'])]
    public function index(Request $request, SiteRepository $siteRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || $user->getOrganisation() === null) {
            throw $this->createAccessDeniedException('Utilisateur non rattaché à une organisation.');
        }

        $organisation = $user->getOrganisation();
        $actifFilter = $request->query->get('actif');
        $actif = match ($actifFilter) {
            '1' => true,
            '0' => false,
            default => null,
        };

        $qb = $siteRepository->getQueryBuilderByOrganisation($organisation, $actif);
        $pagination = $siteRepository->paginateSites($qb, $request->query->getInt('page', 1), 5);

        return $this->render('site/index.html.twig', [
            'pagination' => $pagination,
            'activeCount' => $siteRepository->countActive($organisation),
            'totalCount' => $siteRepository->countTotal($organisation),
            'selectedActif' => $actifFilter === '0' || $actifFilter === '1' ? $actifFilter : '',
        ]);
    }

    #[Route('/new', name: 'app_site_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || $user->getOrganisation() === null) {
            throw $this->createAccessDeniedException('Utilisateur non rattaché à une organisation.');
        }

        $site = new Site();
        $site->setOrganisation($user->getOrganisation());
        $form = $this->createForm(SiteType::class, $site);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($site);
            $entityManager->flush();
            $this->addFlash('success', 'Le site ' . $site->getNom() . ' a été créé avec succès.');
            return $this->redirectToRoute('app_site_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('site/new.html.twig', [
            'site' => $site,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_site_show', methods: ['GET'])]
    public function show(Site $site): Response
    {
        $this->denyAccessUnlessGranted(SiteVoter::VIEW, $site);

        return $this->render('site/show.html.twig', [
            'site' => $site,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_site_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Site $site, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(SiteVoter::EDIT, $site);

        $form = $this->createForm(SiteType::class, $site);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Le site ' . $site->getNom() . ' a été modifié avec succès.');
            return $this->redirectToRoute('app_site_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('site/edit.html.twig', [
            'site' => $site,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_site_delete', methods: ['POST'])]
    public function delete(Request $request, Site $site, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(SiteVoter::DELETE, $site);

        if ($this->isCsrfTokenValid('delete'.$site->getId(), $request->getPayload()->getString('_token'))) {
            // Vérifier les dépendances avant suppression
            if ($site->getBatiments()->count() > 0) {
                $this->addFlash('danger', 'Impossible de supprimer ce site : il contient encore ' . $site->getBatiments()->count() . ' bâtiment(s).');
                return $this->redirectToRoute('app_site_show', ['id' => $site->getId()]);
            }
            if ($site->getDemandes()->count() > 0) {
                $this->addFlash('danger', 'Impossible de supprimer ce site : il est lié à ' . $site->getDemandes()->count() . ' demande(s).');
                return $this->redirectToRoute('app_site_show', ['id' => $site->getId()]);
            }
            if ($site->getEquipements()->count() > 0) {
                $this->addFlash('danger', 'Impossible de supprimer ce site : il contient encore ' . $site->getEquipements()->count() . ' équipement(s).');
                return $this->redirectToRoute('app_site_show', ['id' => $site->getId()]);
            }

            $entityManager->remove($site);
            $entityManager->flush();
            $this->addFlash('success', 'Le site ' . $site->getNom() . ' a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_site_index', [], Response::HTTP_SEE_OTHER);
    }

}
