<?php

    namespace App\Security\Voter;

    use App\Entity\Intervention;
    use App\Entity\User;
    use Symfony\Bundle\SecurityBundle\Security;
    use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
    use Symfony\Component\Security\Core\Authorization\Voter\Vote;
    use Symfony\Component\Security\Core\Authorization\Voter\Voter;

    final class InterventionVoter extends Voter
    {
        public function __construct (private readonly Security $security)
        {

        }

        public const string EDIT = 'INTERVENTION_EDIT';
        public const string VIEW = 'INTERVENTION_VIEW';

        public const string DEMARRER = 'INTERVENTION_DEMARRER';

        public const string TERMINER = 'INTERVENTION_TERMINER';

        public const string AJOUTER_PHOTO = 'INTERVENTION_AJOUTER_PHOTO';

        public const string DELETE = 'INTERVENTION_DELETE';

        public const string VALIDER = 'INTERVENTION_VALIDER';

        protected function supports(string $attribute, mixed $subject): bool
        {
            return in_array($attribute, [self::EDIT, self::VIEW, self::DEMARRER, self::TERMINER, self::AJOUTER_PHOTO, self::DELETE, self::VALIDER])
                && $subject instanceof Intervention;
        }

        protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
        {
            $user = $token->getUser();
            $intervention = $subject;

            if (!$user instanceof User) {
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
