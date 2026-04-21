<?php
/**
 * Todo service interface.
 */

namespace App\Service;

use App\Entity\Todo;
use App\Entity\User;
use Knp\Component\Pager\Pagination\PaginationInterface;

/**
 * Interface TodoServiceInterface.
 */
interface TodoServiceInterface
{
    /**
     * Get paginated list.
     *
     * @param int       $page   Page number
     * @param User|null $author Author filter
     *
     * @return PaginationInterface Paginated list
     */
    public function getPaginatedList(int $page, ?User $author = null): PaginationInterface;

    /**
     * Save entity.
     *
     * @param Todo $todo Todo entity
     */
    public function save(Todo $todo): void;

    /**
     * Delete entity.
     *
     * @param Todo $todo Todo entity
     */
    public function delete(Todo $todo): void;
}
