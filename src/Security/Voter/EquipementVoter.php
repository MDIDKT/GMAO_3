<?php

namespace App\Security\Voter;

use App\Entity\Equipement;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class EquipementVoter extends Voter
{
    public const string VIEW = 'EQUIPEMENT_VIEW';
    public const string EDIT = 'EQUIPEMENT_EDIT';
    public const string DELETE = 'EQUIPEMENT_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof Equipement;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($user->getOrganisation() === null) {
            return false;
        }

        /** @var Equipement $subject */
        if ($subject->getOrganisation() !== $user->getOrganisation()) {
            return false;
        }

        return true;
    }
}
