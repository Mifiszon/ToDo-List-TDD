<?php

/**
 * User service interface.
 */

namespace App\Service;

use App\Entity\User;

/**
 * Interface UserServiceInterface.
 */
interface UserServiceInterface
{
    /**
     * Change user password.
     *
     * @param User   $user        User entity
     * @param string $newPassword Plain new password
     */
    public function changePassword(User $user, string $newPassword): void;

    /**
     * Register user.
     *
     * @param User   $user          User entity
     * @param string $plainPassword Plain new password
     */
    public function register(User $user, string $plainPassword): void;
}
