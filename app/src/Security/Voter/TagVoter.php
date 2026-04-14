<?php

namespace App\Security\Voter;

use App\Entity\Tag;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class TagVoter extends Voter
{
    public const EDIT = 'TAG_EDIT';
    public const VIEW = 'TAG_VIEW';
    public const DELETE = 'TAG_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW, self::DELETE])
            && $subject instanceof Tag;
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
