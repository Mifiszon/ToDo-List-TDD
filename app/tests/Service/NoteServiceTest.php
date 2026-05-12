<?php

/**
 * Note service tests.
 */

namespace App\Tests\Service;

use App\Dto\NoteListInputFiltersDto;
use App\Entity\Category;
use App\Entity\Enum\NoteStatus;
use App\Entity\Note;
use App\Entity\User;
use App\Service\NoteService;
use App\Service\NoteServiceInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class NoteServiceTest.
 */
class NoteServiceTest extends KernelTestCase
{
    /**
     * Entity manager.
     */
    private ?EntityManagerInterface $entityManager;

    /**
     * Note service.
     */
    private ?NoteServiceInterface $noteService;

    /**
     * Set up test.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function setUp(): void
    {
        $container = static::getContainer();
        $this->entityManager = $container->get('doctrine.orm.entity_manager');
        $this->noteService = $container->get(NoteService::class);
    }

    /**
     * Test save.
     *
     * @throws ORMException
     */
    public function testSave(): void
    {
        // given
        $user = $this->createUser('note_author@example.com');
        $category = $this->createCategory('Test Category');

        $expectedNote = new Note();
        $expectedNote->setTitle('Test Note Title');
        $expectedNote->setComment('Test Comment Content');
        $expectedNote->setAuthor($user);
        $expectedNote->setCategory($category);
        $expectedNote->setStatus(NoteStatus::ACTIVE);

        // when
        $this->noteService->save($expectedNote);

        // then
        $expectedNoteId = $expectedNote->getId();
        $resultNote = $this->entityManager->createQueryBuilder()
            ->select('note')
            ->from(Note::class, 'note')
            ->where('note.id = :id')
            ->setParameter(':id', $expectedNoteId, Types::INTEGER)
            ->getQuery()
            ->getSingleResult();

        $this->assertEquals($expectedNote, $resultNote);
        $this->assertEquals('Test Comment Content', $resultNote->getComment());
    }

    /**
     * Test delete.
     *
     * @throws OptimisticLockException|ORMException
     */
    public function testDelete(): void
    {
        // given
        $user = $this->createUser('delete_author@example.com');
        $category = $this->createCategory('Delete Category');

        $noteToDelete = new Note();
        $noteToDelete->setTitle('Note to Delete');
        $noteToDelete->setComment('Comment to be deleted');
        $noteToDelete->setAuthor($user);
        $noteToDelete->setCategory($category);
        $noteToDelete->setStatus(NoteStatus::ACTIVE);

        $this->entityManager->persist($noteToDelete);
        $this->entityManager->flush();
        $deletedNoteId = $noteToDelete->getId();

        // when
        $this->noteService->delete($noteToDelete);

        // then
        $resultNote = $this->entityManager->createQueryBuilder()
            ->select('note')
            ->from(Note::class, 'note')
            ->where('note.id = :id')
            ->setParameter(':id', $deletedNoteId, Types::INTEGER)
            ->getQuery()
            ->getOneOrNullResult();

        $this->assertNull($resultNote);
    }

    /**
     * Test get paginated list.
     */
    public function testGetPaginatedList(): void
    {
        // given
        $user = $this->createUser('list_author@example.com');
        $category = $this->createCategory('List Category');
        $page = 1;
        $dataSetSize = 3;

        $filters = new NoteListInputFiltersDto(null, null, NoteStatus::ACTIVE->value);

        $counter = 0;
        while ($counter < $dataSetSize) {
            $note = new Note();
            $note->setTitle('Test Note #'.$counter);
            $note->setComment('Comment #'.$counter);
            $note->setAuthor($user);
            $note->setCategory($category);
            $note->setStatus(NoteStatus::ACTIVE);
            $this->noteService->save($note);
            ++$counter;
        }

        // when
        $result = $this->noteService->getPaginatedList($page, $user, $filters);

        // then
        $this->assertEquals($dataSetSize, $result->count());
    }

    /**
     * Helper to create User.
     * @param string $email User Email
     *
     * @return User User entity
     */
    private function createUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword('password');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * Helper to create Category.
     * @param string $title Category Title
     *
     * @return Category Category entity
     */
    private function createCategory(string $title): Category
    {
        $category = new Category();
        $category->setTitle($title);
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }
}
