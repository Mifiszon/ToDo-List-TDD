<?php
/**
 * Todo voter.
 */

namespace App\Security\Voter;

use App\Entity\Todo;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class TodoVoter.
 */
final class TodoVoter extends Voter
{
    /**
     * Delete permission.
     *
     * @var string
     */
    public const DELETE = 'TODO_DELETE';

    /**
     * Edit permission.
     *
     * @var string
     */
    public const EDIT = 'TODO_EDIT';

    /**
     * View permission.
     *
     * @var string
     */
    public const VIEW = 'TODO_VIEW';

    /**
     * Determines if this voter supports the attribute and subject.
     *
     * @param string $attribute An attribute
     * @param mixed  $subject   The subject to secure
     *
     * @return bool Result
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::DELETE, self::EDIT, self::VIEW])
            && $subject instanceof Todo;
    }

    /**
     * Vote on attribute.
     *
     * @param string         $attribute Permission name
     * @param mixed          $subject   Object
     * @param TokenInterface $token     Security token
     *
     * @return bool Vote result
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        if (in_array('ROLE_ADMIN', $token->getRoleNames())) {
            return true;
        }

        if (!$subject instanceof Todo) {
            return false;
        }

        return match ($attribute) {
            self::EDIT => $this->canEdit($subject, $user),
            self::DELETE => $this->canDelete($subject, $user),
            self::VIEW => $this->canView($subject, $user),
            default => false,
        };
    }

    /**
     * Checks if user can delete todo.
     *
     * @param Todo          $todo Todo entity
     * @param UserInterface $user User
     *
     * @return bool Result
     */
    private function canDelete(Todo $todo, UserInterface $user): bool
    {
        return $todo->getAuthor() === $user;
    }

    /**
     * Checks if user can edit todo.
     *
     * @param Todo          $todo Todo entity
     * @param UserInterface $user User
     *
     * @return bool Result
     */
    private function canEdit(Todo $todo, UserInterface $user): bool
    {
        return $todo->getAuthor() === $user;
    }

    /**
     * Checks if user can view todo.
     *
     * @param Todo          $todo Todo entity
     * @param UserInterface $user User
     *
     * @return bool Result
     */
    private function canView(Todo $todo, UserInterface $user): bool
    {
        return $todo->getAuthor() === $user;
    }
}
