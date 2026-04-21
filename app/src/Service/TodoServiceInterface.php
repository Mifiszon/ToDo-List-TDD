<?php
/**
 * Todo service interface.
 */

namespace App\Service;

use App\Entity\Todo;
use App\Entity\User;

/**
 * Interface TodoServiceInterface.
 */
interface TodoServiceInterface
{
    /**
     * Get todos by author.
     *
     * @param User $user User entity
     *
     * @return array List of todos
     */
    public function getListByUser(User $user): array;

    /**
     * Find all todos (for Admin).
     *
     * @return array List of all todos
     */
    public function findAll(): array;

    /**
     * Save todo.
     *
     * @param Todo $todo Todo entity
     */
    public function save(Todo $todo): void;

    /**
     * Delete todo.
     *
     * @param Todo $todo Todo entity
     */
    public function delete(Todo $todo): void;
}
