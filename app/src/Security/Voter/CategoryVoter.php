<?php

namespace App\Security\Voter;

use App\Entity\Category;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class CategoryVoter extends Voter
{
    public const EDIT = 'CATEGORY_EDIT';
    public const VIEW = 'CATEGORY_VIEW';
    public const DELETE = 'CATEGORY_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW, self::DELETE])
            && $subject instanceof Category;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }
        if ($attribute === self::VIEW) {
            return true;
        }

        return in_array('ROLE_ADMIN', $token->getRoleNames());
    }
}
