<?php

    namespace App\Security\Voter;

    use Symfony\Bundle\SecurityBundle\Security;
    use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
    use Symfony\Component\Security\Core\Authorization\Voter\Voter;
    use Symfony\Component\Security\Core\User\UserInterface;

    final class InterventionVoter extends Voter
    {
        public function __construct (private readonly Security $security)
        {

        }
        public const EDIT = 'INTERVENTION_EDIT';
        public const VIEW = 'INTERVENTION_VIEW';

        public const DEMARRER = 'INTERVENTION_DEMARRER';

        public const TERMINER = 'INTERVENTION_TERMINER';

        public const AJOUTER_PHOTO = 'INTERVENTION_AJOUTER_PHOTO';

        public const DELETE = 'INTERVENTION_DELETE';

        protected function supports(string $attribute, mixed $subject): bool
        {
            return in_array($attribute, [self::EDIT, self::VIEW, self::DEMARRER, self::TERMINER, self::AJOUTER_PHOTO, self::DELETE])
                && $subject instanceof \App\Entity\Intervention;
        }

        protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
        {
            $user = $token->getUser();
            $intervention = $subject;

            if (!$user instanceof UserInterface) {
                return false;
            }

            if ($intervention->getOrganisation() !== $user->getOrganisation()) {
                return false;
            }

            if ($this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_PLANIFICATEUR')) {
                return true;
            }

            return $intervention->getTechnicien() === $user;

        }
    }
