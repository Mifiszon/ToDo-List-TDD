<?php

/**
 * Note voter.
 */

namespace App\Security\Voter;

use App\Entity\Note;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class NoteVoter.
 */
final class NoteVoter extends Voter
{
    /**
     * Delete permission.
     *
     * @var string
     */
    public const DELETE = 'NOTE_DELETE';

    /**
     * Edit permission.
     *
     * @var string
     */
    public const EDIT = 'NOTE_EDIT';

    /**
     * View permission.
     *
     * @var string
     */
    public const VIEW = 'NOTE_VIEW';

    /**
     * Determines if this voter supports the attribute and subject.
     *
     * @param string $attribute An attribute
     * @param mixed  $subject   The subject to secure, e.g. an object the user wants to access or any other PHP type
     *
     * @return bool Result
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::DELETE, self::EDIT, self::VIEW])
            && $subject instanceof Note;
    }

    /**
     * Perform a single access check operation on a given attribute, subject and token.
     * It is safe to assume that $attribute and $subject already passed the "supports()" method check.
     *
     * @param string         $attribute Permission name
     * @param mixed          $subject   Object
     * @param TokenInterface $token     Security token
     * @param Vote|null      $vote      Vote object
     *
     * @return bool Vote result
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }
        if (in_array('ROLE_ADMIN', $token->getRoleNames())) {
            return true;
        }
        if (!$subject instanceof Note) {
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
     * Checks if user can delete note.
     *
     * @param Note          $note Note entity
     * @param UserInterface $user User
     *
     * @return bool Result
     */
    private function canDelete(Note $note, UserInterface $user): bool
    {
        return $note->getAuthor() === $user;
    }

    /**
     * Checks if user can edit note.
     *
     * @param Note          $note Note entity
     * @param UserInterface $user User
     *
     * @return bool Result
     */
    private function canEdit(Note $note, UserInterface $user): bool
    {
        return $note->getAuthor() === $user;
    }

    /**
     * Checks if a user can view a note.
     *
     * @param Note          $note Note entity
     * @param UserInterface $user User
     *
     * @return bool Result
     */
    private function canView(Note $note, UserInterface $user): bool
    {
        return $note->getAuthor() === $user;
    }
}
