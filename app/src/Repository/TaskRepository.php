<?php

/**
 * Task repository.
 */

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Tag;
use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class TaskRepository.
 *
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry Manager registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    /**
     * Query all records.
     *
     * @param User|null $author
     *
     * @return QueryBuilder Query builder
     */
    public function queryAll(?User $author = null): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('task')
            ->select('task', 'category', 'author')
            ->leftJoin('task.category', 'category')
            ->leftJoin('task.author', 'author')
            ->orderBy('task.updatedAt', 'DESC');

        if ($author) {
            $queryBuilder->andWhere('task.author = :author')
                ->setParameter('author', $author);
        }

        return $queryBuilder;
    }

    /**
     * Count tasks by category.
     *
     * @param Category $category Category
     *
     * @return int Number of tasks in category
     */
    public function countByCategory(Category $category): int
    {
        $qb = $this->createQueryBuilder('task');

        return $qb->select($qb->expr()->countDistinct('task.id'))
            ->where('task.category = :category')
            ->setParameter(':category', $category)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count tasks by tag.
     *
     * @param Tag $tag Tag
     *
     * @return int Number of tasks in tag
     */
    public function countByTag(Tag $tag): int
    {
        $qb = $this->createQueryBuilder('task');

        return (int) $qb->select($qb->expr()->countDistinct('task.id'))
            ->join('task.tags', 'tag')
            ->where('tag = :tag')
            ->setParameter(':tag', $tag)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Save entity.
     *
     * @param Task $task Task entity
     */
    public function save(Task $task): void
    {
        $this->getEntityManager()->persist($task);
        $this->getEntityManager()->flush();
    }

    /**
     * Delete entity.
     *
     * @param Task $task Task entity
     */
    public function delete(Task $task): void
    {
        $this->getEntityManager()->remove($task);
        $this->getEntityManager()->flush();
    }
}
