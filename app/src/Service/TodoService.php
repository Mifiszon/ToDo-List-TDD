<?php
/**
 * Todo service.
 */

namespace App\Service;

use App\Entity\Todo;
use App\Entity\User;
use App\Repository\TodoRepository;

/**
 * Class TodoService.
 */
class TodoService implements TodoServiceInterface
{
    /**
     * Constructor.
     *
     * @param TodoRepository $todoRepository Todo repository
     */
    public function __construct(private readonly TodoRepository $todoRepository)
    {
    }

    /**
     * Get todos by author.
     *
     * @param User $user User entity
     *
     * @return array List of todos
     */
    public function getListByUser(User $user): array
    {
        return $this->todoRepository->findBy(['author' => $user]);
    }

    /**
     * Find all todos.
     *
     * @return array List of all todos
     */
    public function findAll(): array
    {
        return $this->todoRepository->findAll();
    }

    /**
     * Save todo.
     *
     * @param Todo $todo Todo entity
     */
    public function save(Todo $todo): void
    {
        $this->todoRepository->save($todo);
    }

    /**
     * Delete todo.
     *
     * @param Todo $todo Todo entity
     */
    public function delete(Todo $todo): void
    {
        $this->todoRepository->delete($todo);
    }
}
