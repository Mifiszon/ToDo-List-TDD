<?php
/**
 * Todo service.
 */

namespace App\Service;

use App\Entity\Todo;
use App\Entity\User;
use App\Repository\TodoRepository;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * Class TodoService.
 */
class TodoService implements TodoServiceInterface
{
    /**
     * Items per page.
     *
     * @constant int
     */
    private const PAGINATOR_ITEMS_PER_PAGE = 10;

    /**
     * Constructor.
     *
     * @param TodoRepository     $todoRepository Todo repository
     * @param PaginatorInterface $paginator      Paginator
     */
    public function __construct(private readonly TodoRepository $todoRepository, private readonly PaginatorInterface $paginator)
    {
    }

    /**
     * Get paginated list.
     *
     * @param int       $page   Page number
     * @param User|null $author Author filter (null for admins)
     *
     * @return PaginationInterface Paginated list
     */
    public function getPaginatedList(int $page, ?User $author = null): PaginationInterface
    {
        return $this->paginator->paginate(
            $this->todoRepository->queryAll($author),
            $page,
            self::PAGINATOR_ITEMS_PER_PAGE,
            [
                'sortFieldAllowList' => ['todo.id', 'todo.title', 'todo.isDone'],
                'defaultSortFieldName' => 'todo.id',
                'defaultSortDirection' => 'asc',
            ]
        );
    }

    /**
     * Save entity.
     *
     * @param Todo $todo Todo entity
     */
    public function save(Todo $todo): void
    {
        $this->todoRepository->save($todo);
    }

    /**
     * Delete entity.
     *
     * @param Todo $todo Todo entity
     */
    public function delete(Todo $todo): void
    {
        $this->todoRepository->delete($todo);
    }
}
