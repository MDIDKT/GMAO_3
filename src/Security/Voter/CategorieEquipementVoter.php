<?php

namespace App\Security\Voter;

use App\Entity\CategorieEquipement;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class CategorieEquipementVoter extends Voter
{
    public const string VIEW = 'CATEGORIE_EQUIPEMENT_VIEW';
    public const string EDIT = 'CATEGORIE_EQUIPEMENT_EDIT';
    public const string DELETE = 'CATEGORIE_EQUIPEMENT_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof CategorieEquipement;
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

        /** @var CategorieEquipement $subject */
        if ($subject->getOrganisation() !== $user->getOrganisation()) {
            return false;
        }

        return true;
    }
}
