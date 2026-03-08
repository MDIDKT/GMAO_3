<?php

declare(strict_types=1);

    namespace App\Security;

    use App\Entity\User;
    use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
    use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
    use Symfony\Component\Security\Core\User\UserCheckerInterface;
    use Symfony\Component\Security\Core\User\UserInterface;

    class UserChecker implements UserCheckerInterface
    {
        public function checkPreAuth(UserInterface $user, ?TokenInterface $token = null): void
        {
            // cette ligne permet de ne pas bloquer les utilisateurs inactifs en faisant un cast vers User
            // instanceof permet de savoir si l'utilisateur est bien un objet User'
            if (!$user instanceof User) {
                return;
            }
            // cette ligne permet de bloquer les utilisateurs inactifs car ils ne peuvent pas se connecter
            if (!$user->isActif()) {
                throw new CustomUserMessageAccountStatusException(
                    'Votre compte est desactive. Contactez votre administrateur.'
                );
            }
        }

        public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
        {
//rien
        }
    }
