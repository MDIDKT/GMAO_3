<?php

    namespace App\Controller;

    use App\Entity\User;
    use App\Form\InvitationType;
    use App\Repository\UserRepository;
    use DateTimeImmutable;
    use Doctrine\ORM\EntityManagerInterface;
    use Symfony\Bridge\Twig\Mime\TemplatedEmail;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Mailer\MailerInterface;
    use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
    use Symfony\Component\Security\Http\Attribute\IsGranted;

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin/user')]
    final class AdminUserController extends AbstractController
    {
        #[Route('/', name: 'app_admin_user_index', methods: ['GET'])]
        public function index(UserRepository $userRepository): Response
        {
            $currentUser = $this->getUser();
            if (!$currentUser instanceof User || $currentUser->getOrganisation() === null) {
                throw $this->createAccessDeniedException('Utilisateur non rattaché à une organisation.');
            }

            return $this->render('adminUser/index.html.twig', [
                'users' => $userRepository->findBy(['organisation' => $currentUser->getOrganisation()]),
            ]);
        }

        /**
         * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
         * @throws \Random\RandomException
         */
        #[Route('/inviter', name: 'app_admin_user_inviter')]
        public function inviter(
            Request                $request,
            EntityManagerInterface $em,
            MailerInterface        $mailer,
        UserPasswordHasherInterface $passwordHasher,
        ): Response
        {
            $user = new User();
            $form = $this->createForm(InvitationType::class, $user);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                // 1. Générer le token
                $token = bin2hex(random_bytes(32));
                $user->setInvitationToken($token);
                $user->setTokenExpiresAt(new DateTimeImmutable('+48 hours'));
                $user->setActif(false);

                // 2. User inactif, sans mot de passe
                $randomPlain = bin2hex(random_bytes(24));
                $user->setPassword($passwordHasher->hashPassword($user, $randomPlain));

                // 3. Rattacher à l'organisation de l'admin connecté
                $admin = $this->getUser();
                if (!$admin instanceof User || $admin->getOrganisation() === null) {
                    throw $this->createAccessDeniedException('Admin account is not linked to an organisation.');
                }
                $user->setOrganisation($admin->getOrganisation());

                // 4. Sauvegarder en base
                $em->persist($user);
                $em->flush();

                // 5. Envoyer l'email d'invitation
                $email = new TemplatedEmail()
                    ->from($this->getParameter('mailer_from_address'))
                    ->to($user->getEmail())
                    ->subject('Invitation GMAO')
                    ->htmlTemplate('email/invitation.html.twig')
                    ->context([
                        'user' => $user,
                        'activationUrl' => $this->generateUrl('app_activation', [
                            'token' => $token,
                        ], UrlGeneratorInterface::ABSOLUTE_URL),
                    ]);
                $mailer->send($email);

                $this->addFlash('success', 'Invitation envoyée à ' . $user->getEmail());
                return $this->redirectToRoute('app_admin_user_inviter');
            }

            return $this->render('adminUser/inviter.html.twig', [
                'form' => $form,
            ]);
        }

        #[Route('/{id}/toggle', name: 'app_admin_user_toggle', methods: ['POST'])]
        public function toggleStatus(User $user, Request $request, EntityManagerInterface $em): Response
        {
            $currentUser = $this->getUser();
            if ($user === $currentUser) {
                $this->addFlash('danger', 'Vous ne pouvez pas désactiver votre propre compte.');
                return $this->redirectToRoute('app_admin_user_index');
            }

            if ($user->getOrganisation() !== $currentUser->getOrganisation()) {
                throw $this->createAccessDeniedException('Cet utilisateur ne fait pas partie de votre organisation.');
            }

            if (!$this->isCsrfTokenValid('toggle' . $user->getId(), $request->request->get('_token'))) {
                $this->addFlash('danger', 'Token CSRF invalide.');
                return $this->redirectToRoute('app_admin_user_index');
            }

            $user->setActif(!$user->isActif());
            $em->flush();

            $status = $user->isActif() ? 'activé' : 'désactivé';
            $this->addFlash('success', "L'utilisateur {$user->getEmail()} a été {$status}.");

            return $this->redirectToRoute('app_admin_user_index');
        }

        #[Route('/{id}/role', name: 'app_admin_user_role', methods: ['POST'])]
        public function changeRole(User $user, Request $request, EntityManagerInterface $em): Response
        {
            $currentUser = $this->getUser();
            if ($user === $currentUser) {
                $this->addFlash('danger', 'Vous ne pouvez pas modifier votre propre rôle.');
                return $this->redirectToRoute('app_admin_user_index');
            }

            if ($user->getOrganisation() !== $currentUser->getOrganisation()) {
                throw $this->createAccessDeniedException('Cet utilisateur ne fait pas partie de votre organisation.');
            }

            if (!$this->isCsrfTokenValid('role' . $user->getId(), $request->request->get('_token'))) {
                $this->addFlash('danger', 'Token CSRF invalide.');
                return $this->redirectToRoute('app_admin_user_index');
            }

            $newRole = $request->request->get('role');
            $validRoles = ['ROLE_USER', 'ROLE_PLANIFICATEUR', 'ROLE_ADMIN'];

            if (in_array($newRole, $validRoles)) {
                $user->setRoles([$newRole]);
                $em->flush();
                $this->addFlash('success', "Le rôle de {$user->getEmail()} a été mis à jour.");
            } else {
                $this->addFlash('danger', 'Rôle invalide.');
            }

            return $this->redirectToRoute('app_admin_user_index');
        }

        #[Route('/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
        public function delete(User $user, Request $request, EntityManagerInterface $em): Response
        {
            $currentUser = $this->getUser();
            if ($user === $currentUser) {
                $this->addFlash('danger', 'Vous ne pouvez pas supprimer votre propre compte.');
                return $this->redirectToRoute('app_admin_user_index');
            }

            if ($user->getOrganisation() !== $currentUser->getOrganisation()) {
                throw $this->createAccessDeniedException('Cet utilisateur ne fait pas partie de votre organisation.');
            }

            if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
                // Vérifier les liens (Demandes / Interventions)
                if ($user->getDemandes()->count() > 0 || $user->getInterventions()->count() > 0) {
                    $this->addFlash('danger', "Impossible de supprimer {$user->getEmail()} car il possède des données liées. Désactivez-le plutôt.");
                    return $this->redirectToRoute('app_admin_user_index');
                }

                $em->remove($user);
                $em->flush();
                $this->addFlash('success', "L'utilisateur a été supprimé.");
            }

            return $this->redirectToRoute('app_admin_user_index');
        }
    }
