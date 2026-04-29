<?php
/**
 * Note status.
 */

namespace App\Entity\Enum;

/**
 * Enum NoteStatus.
 */
enum NoteStatus: int
{
    case ACTIVE = 1;
    case DONE = 2;

    /**
     * Get the status label.
     *
     * @return string Status label
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'label.status_active',
            self::DONE => 'label.status_done',
        };
    }
}
