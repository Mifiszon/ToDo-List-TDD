<?php

/**
 * User service interface.
 */

namespace App\Service;

use App\Entity\User;
use Knp\Component\Pager\Pagination\PaginationInterface;

/**
 * Interface UserServiceInterface.
 */
interface UserServiceInterface
{
    /**
     * Get paginated list.
     *
     * @param int $page Page number
     *
     * @return PaginationInterface<string, mixed> Paginated list
     */
    public function getPaginatedList(int $page): PaginationInterface;

    /**
     * Delete user.
     *
     * @param User $user User entity
     */
    public function delete(User $user): void;

    /**
     * Change user password.
     *
     * @param User   $user        User entity
     * @param string $newPassword New password
     */
    public function changePassword(User $user, string $newPassword): void;

    /**
     * Register new user.
     *
     * @param User   $user          User entity
     * @param string $plainPassword Plain password
     *
     * @throws \Exception
     */
    public function register(User $user, string $plainPassword): void;

    /**
     * Save user.
     * * @param User $user User entity
     */
    public function save(User $user): void;
}
