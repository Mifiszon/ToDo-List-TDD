<?php
/**
 * Todo repository.
 */

namespace App\Repository;

use App\Entity\Todo;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class TodoRepository.
 *
 * @extends ServiceEntityRepository<Todo>
 */
class TodoRepository extends ServiceEntityRepository
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry Manager registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Todo::class);
    }

    /**
     * Query all records.
     *
     * @param User|null $author User entity (null for admins to see all)
     *
     * @return QueryBuilder Query builder
     */
    public function queryAll(?User $author): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('todo')
            ->select(
                'partial todo.{id, title, isDone}',
                'partial author.{id, email}'
            )
            ->join('todo.author', 'author');

        if (null !== $author) {
            $queryBuilder->andWhere('todo.author = :author')
                ->setParameter('author', $author);
        }

        $queryBuilder->orderBy('todo.isDone', 'ASC');

        return $queryBuilder;
    }

    /**
     * Save entity.
     *
     * @param Todo $todo Todo entity
     */
    public function save(Todo $todo): void
    {
        $this->getEntityManager()->persist($todo);
        $this->getEntityManager()->flush();
    }

    /**
     * Delete entity.
     *
     * @param Todo $todo Todo entity
     */
    public function delete(Todo $todo): void
    {
        $this->getEntityManager()->remove($todo);
        $this->getEntityManager()->flush();
    }
}
