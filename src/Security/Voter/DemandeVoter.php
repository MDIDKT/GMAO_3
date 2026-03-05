<?php

    namespace App\Security\Voter;

    use App\Entity\Demande;
    use Symfony\Bundle\SecurityBundle\Security;
    use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
    use Symfony\Component\Security\Core\Authorization\Voter\Vote;
    use Symfony\Component\Security\Core\Authorization\Voter\Voter;
    use Symfony\Component\Security\Core\User\UserInterface;

    final class DemandeVoter extends Voter
    {
        public function __construct(private readonly Security $security)
        {

        }

        public const string VIEW = 'DEMANDE_VIEW';
        public const string EDIT = 'DEMANDE_EDIT';
        public const string DELETE = 'DEMANDE_DELETE';

        protected function supports(string $attribute, mixed $subject): bool
        {
            return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
                && $subject instanceof Demande;
        }

        protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
        {
            $user = $token->getUser();
            $demande = $subject;

            if (!$user instanceof UserInterface) {
                return false;
            }

            if ($demande->getOrganisation() !== $user->getOrganisation()) {
                return false;
            }

            if ($this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_PLANIFICATEUR')) {
                return true;
            }

            return $demande->getUser() === $user;

        }
    }
