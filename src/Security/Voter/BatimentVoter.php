<?php

namespace App\Security\Voter;

use App\Entity\Batiment;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class BatimentVoter extends Voter
{
    public const string VIEW = 'BATIMENT_VIEW';
    public const string EDIT = 'BATIMENT_EDIT';
    public const string DELETE = 'BATIMENT_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof Batiment;
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

        /** @var Batiment $subject */
        $site = $subject->getSite();
        if ($site === null || $site->getOrganisation() !== $user->getOrganisation()) {
            return false;
        }

        return true;
    }
}
