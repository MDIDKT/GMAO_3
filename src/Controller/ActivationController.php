<?php

    namespace App\Controller;

    use App\Entity\User;
    use App\Form\SetPasswordType;
    use DateTimeImmutable;
    use Doctrine\ORM\EntityManagerInterface;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
    use Symfony\Component\Routing\Attribute\Route;

    final class ActivationController extends AbstractController
    {
        #[Route('/activation/{token}', name: 'app_activation')]
        public function activate(
            string                      $token,
            Request                     $request,
            EntityManagerInterface      $em,
            UserPasswordHasherInterface $passwordHasher,
        ): Response
        {
            /** @var User|null $user */
            $user = $em->getRepository(User::class)->findOneBy(['invitationToken' => $token]);

            if (!$user) {
                throw $this->createNotFoundException('Lien invalide.');
            }

            if ($user->getTokenExpiresAt() < new DateTimeImmutable()) {
                $this->addFlash('danger', 'Lien expiré. Demande une nouvelle invitation.');
                return $this->redirectToRoute('app_login');
            }

            $form = $this->createForm(SetPasswordType::class);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $plainPassword = $form->get('plainPassword')->getData();

                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
                $user->setActif(true);
                $user->setInvitationToken(null);
                $user->setTokenExpiresAt(null);

                $em->flush();

                $this->addFlash('success', 'Compte activé. Tu peux te connecter.');
                return $this->redirectToRoute('app_login');
            }

            return $this->render('security/activation.html.twig', [
                'form' => $form,
            ]);
        }
    }
