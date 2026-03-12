<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Entity\Batiment;
use App\Entity\CategorieEquipement;
use App\Entity\Demande;
use App\Entity\Intervention;
use App\Entity\Photo;
use App\Entity\Site;
use App\Entity\User;
use App\Enum\TypePhoto;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TenantIsolationTest extends WebTestCase
{
    public function testAdminCannotViewAnotherOrganisationSite(): void
    {
        $client = static::createClient();
        $admin = $this->findUser('admin@patrimoine.fr');
        $targetSite = $this->findSiteForUserOrganisation($this->findUser('admin@gmao.fr'));

        $client->loginUser($admin);
        $client->request('GET', '/site/' . $targetSite->getId());

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCannotEditAnotherOrganisationBatiment(): void
    {
        $client = static::createClient();
        $admin = $this->findUser('admin@patrimoine.fr');
        $targetBatiment = $this->findBatimentForUserOrganisation($this->findUser('admin@gmao.fr'));

        $client->loginUser($admin);
        $client->request('GET', '/batiment/' . $targetBatiment->getId() . '/edit');

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCannotViewAnotherOrganisationEquipement(): void
    {
        $client = static::createClient();
        $admin = $this->findUser('admin@patrimoine.fr');
        $targetEquipement = $this->findEquipementForUserOrganisation($this->findUser('admin@gmao.fr'));

        $client->loginUser($admin);
        $client->request('GET', '/equipement/' . $targetEquipement->getId());

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCannotEditAnotherOrganisationCategorie(): void
    {
        $client = static::createClient();
        $admin = $this->findUser('admin@patrimoine.fr');
        $targetCategorie = $this->findCategorieForUserOrganisation($this->findUser('admin@gmao.fr'));

        $client->loginUser($admin);
        $client->request('GET', '/admin/categories-equipement/' . $targetCategorie->getId() . '/edit');

        self::assertResponseStatusCodeSame(403);
    }

    public function testCrossTenantAccessToDemandePhotoIsForbidden(): void
    {
        $client = static::createClient();
        $admin = $this->findUser('admin@patrimoine.fr');
        $owner = $this->findUser('admin@gmao.fr');
        $demande = $this->findDemandeForUserOrganisation($owner);
        $photo = $this->createTemporaryPhotoForDemande($demande, $owner);

        try {
            $client->loginUser($admin);
            $client->request('GET', '/demande/photos/' . $photo->getId());

            self::assertResponseStatusCodeSame(403);
        } finally {
            $this->removeTemporaryPhoto($photo);
        }
    }

    public function testCrossTenantAccessToInterventionPhotoIsForbidden(): void
    {
        $client = static::createClient();
        $admin = $this->findUser('admin@patrimoine.fr');
        $owner = $this->findUser('admin@gmao.fr');
        $intervention = $this->findInterventionForUserOrganisation($owner);
        $photo = $this->createTemporaryPhotoForIntervention($intervention, $owner);

        try {
            $client->loginUser($admin);
            $client->request('GET', '/intervention/photo/' . $photo->getId());

            self::assertResponseStatusCodeSame(403);
        } finally {
            $this->removeTemporaryPhoto($photo);
        }
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    private function findUser(string $email): User
    {
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => $email]);

        self::assertInstanceOf(User::class, $user, 'Utilisateur fixture introuvable: ' . $email);

        return $user;
    }

    private function findSiteForUserOrganisation(User $user): Site
    {
        $site = $this->getEntityManager()->getRepository(Site::class)->findOneBy([
            'organisation' => $user->getOrganisation(),
        ]);

        self::assertInstanceOf(Site::class, $site, 'Aucun site trouve pour l organisation cible.');

        return $site;
    }

    private function findBatimentForUserOrganisation(User $user): Batiment
    {
        $batiment = $this->getEntityManager()->getRepository(Batiment::class)
            ->createQueryBuilder('b')
            ->join('b.site', 's')
            ->andWhere('s.organisation = :organisation')
            ->setParameter('organisation', $user->getOrganisation())
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        self::assertInstanceOf(Batiment::class, $batiment, 'Aucun batiment trouve pour l organisation cible.');

        return $batiment;
    }

    private function findEquipementForUserOrganisation(User $user): \App\Entity\Equipement
    {
        $equipement = $this->getEntityManager()->getRepository(\App\Entity\Equipement::class)->findOneBy([
            'organisation' => $user->getOrganisation(),
        ]);

        self::assertInstanceOf(\App\Entity\Equipement::class, $equipement, 'Aucun equipement trouve pour l organisation cible.');

        return $equipement;
    }

    private function findCategorieForUserOrganisation(User $user): CategorieEquipement
    {
        $categorie = $this->getEntityManager()->getRepository(CategorieEquipement::class)->findOneBy([
            'organisation' => $user->getOrganisation(),
        ]);

        self::assertInstanceOf(CategorieEquipement::class, $categorie, 'Aucune categorie trouvee pour l organisation cible.');

        return $categorie;
    }

    private function findDemandeForUserOrganisation(User $user): Demande
    {
        $demande = $this->getEntityManager()->getRepository(Demande::class)->findOneBy([
            'organisation' => $user->getOrganisation(),
        ]);

        self::assertInstanceOf(Demande::class, $demande, 'Aucune demande trouvee pour l organisation cible.');

        return $demande;
    }

    private function findInterventionForUserOrganisation(User $user): Intervention
    {
        $intervention = $this->getEntityManager()->getRepository(Intervention::class)->findOneBy([
            'organisation' => $user->getOrganisation(),
        ]);

        self::assertInstanceOf(Intervention::class, $intervention, 'Aucune intervention trouvee pour l organisation cible.');

        return $intervention;
    }

    private function createTemporaryPhotoForDemande(Demande $demande, User $owner): Photo
    {
        $photo = $this->createTemporaryPhoto($owner);
        $photo->setDemande($demande);
        $photo->setTypePhoto(TypePhoto::SIGNALEMENT);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($photo);
        $entityManager->flush();

        return $photo;
    }

    private function createTemporaryPhotoForIntervention(Intervention $intervention, User $owner): Photo
    {
        $photo = $this->createTemporaryPhoto($owner);
        $photo->setIntervention($intervention);
        $photo->setTypePhoto(TypePhoto::AVANT);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($photo);
        $entityManager->flush();

        return $photo;
    }

    private function createTemporaryPhoto(User $owner): Photo
    {
        $uploadDirectory = (string) static::getContainer()->getParameter('upload_directory');
        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0777, true);
        }

        $filename = 'test-photo-' . bin2hex(random_bytes(8)) . '.jpg';
        file_put_contents($uploadDirectory . '/' . $filename, 'test');

        return (new Photo())
            ->setFileName($filename)
            ->setOriginalName('test.jpg')
            ->setMimeType('image/jpeg')
            ->setTaille(4)
            ->setUploadPar($owner);
    }

    private function removeTemporaryPhoto(Photo $photo): void
    {
        $entityManager = $this->getEntityManager();
        $managedPhoto = $photo->getId() !== null ? $entityManager->find(Photo::class, $photo->getId()) : null;
        if ($managedPhoto instanceof Photo) {
            $entityManager->remove($managedPhoto);
            $entityManager->flush();
        }

        $filename = $photo->getFileName();
        if ($filename === null) {
            return;
        }

        $path = rtrim((string) static::getContainer()->getParameter('upload_directory'), '/') . '/' . $filename;
        if (is_file($path)) {
            unlink($path);
        }
    }
}
