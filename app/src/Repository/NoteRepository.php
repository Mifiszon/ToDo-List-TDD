<?php

/**
 * Note repository.
 */

namespace App\Repository;

use App\Dto\NoteListFiltersDto;
use App\Entity\Category;
use App\Entity\Enum\NoteStatus;
use App\Entity\Tag;
use App\Entity\Note;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class NoteRepository.
 *
 * @extends ServiceEntityRepository<Note>
 */
class NoteRepository extends ServiceEntityRepository
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry Manager registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Note::class);
    }

    /**
     * Query all records.
     *
     * @param User|null          $author  User entity (null for admins to see all)
     * @param NoteListFiltersDto $filters Filters
     *
     * @return QueryBuilder Query builder
     */
    public function queryAll(?User $author, NoteListFiltersDto $filters): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('note')
            ->select(
                'partial note.{id, createdAt, updatedAt, title, status}',
                'partial category.{id, title}',
                'partial tags.{id, title}',
                'partial author.{id, email}'
            )
            ->join('note.category', 'category')
            ->leftJoin('note.tags', 'tags')
            ->join('note.author', 'author');

        if (null !== $author) {
            $queryBuilder->andWhere('note.author = :author')
                ->setParameter('author', $author);
        }

        return $this->applyFiltersToList($queryBuilder, $filters);
    }

    /**
     * Apply filters to paginated list.
     *
     * @param QueryBuilder       $queryBuilder Query builder
     * @param NoteListFiltersDto $filters      Filters
     *
     * @return QueryBuilder Query builder
     */
    private function applyFiltersToList(QueryBuilder $queryBuilder, NoteListFiltersDto $filters): QueryBuilder
    {
        if ($filters->category instanceof Category) {
            $queryBuilder->andWhere('category = :category')
                ->setParameter('category', $filters->category);
        }

        if ($filters->tag instanceof Tag) {
            $queryBuilder->andWhere('tags IN (:tag)')
                ->setParameter('tag', $filters->tag);
        }

        if ($filters->noteStatus instanceof NoteStatus) {
            $queryBuilder->andWhere('note.status = :status')
                ->setParameter('status', $filters->noteStatus->value, Types::INTEGER);
        }

        return $queryBuilder;
    }

    /**
     * Count notes by category.
     *
     * @param Category $category Category
     *
     * @return int Number of notes in category
     */
    public function countByCategory(Category $category): int
    {
        $qb = $this->createQueryBuilder('note');

        return $qb->select($qb->expr()->countDistinct('note.id'))
            ->where('note.category = :category')
            ->setParameter(':category', $category)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count notes by tag.
     *
     * @param Tag $tag Tag
     *
     * @return int Number of notes in tag
     */
    public function countByTag(Tag $tag): int
    {
        $qb = $this->createQueryBuilder('note');

        return (int) $qb->select($qb->expr()->countDistinct('note.id'))
            ->join('note.tags', 'tag')
            ->where('tag = :tag')
            ->setParameter(':tag', $tag)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Save entity.
     *
     * @param Note $note Note entity
     */
    public function save(Note $note): void
    {
        $this->getEntityManager()->persist($note);
        $this->getEntityManager()->flush();
    }

    /**
     * Delete entity.
     *
     * @param Note $note Note entity
     */
    public function delete(Note $note): void
    {
        $this->getEntityManager()->remove($note);
        $this->getEntityManager()->flush();
    }
}
