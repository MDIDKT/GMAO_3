<?php

    namespace App\Controller;

    use App\Entity\User;
    use App\Form\InvitationType;
    use DateTimeImmutable;
    use Doctrine\ORM\EntityManagerInterface;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Mailer\MailerInterface;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\Component\Security\Http\Attribute\IsGranted;
    use Symfony\Bridge\Twig\Mime\TemplatedEmail;
    use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

    #[IsGranted('ROLE_ADMIN')]
    final class AdminUserController extends AbstractController
    {
        #[Route('/admin/user/inviter', name: 'app_admin_user_inviter')]
        public function inviter(
            Request                $request,
            EntityManagerInterface $em,
            MailerInterface        $mailer
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

                // 2. User inactif, sans mot de passe
                $user->setActif(false);
                $user->setPassword(''); // Pas de mot de passe pour l'instant

                // 3. Rattacher à l'organisation de l'admin connecté
                $user->setOrganisation($this->getUser()->getOrganisation());

                // 4. Sauvegarder en base
                $em->persist($user);
                $em->flush();

                // 5. Envoyer l'email d'invitation
                $email = new TemplatedEmail()
                    ->from('noreply@gmao.dev')
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
    }
