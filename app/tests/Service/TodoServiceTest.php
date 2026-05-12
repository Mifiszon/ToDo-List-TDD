<?php
/**
 * Todo service tests.
 */

namespace App\Tests\Service;

use App\Entity\Todo;
use App\Entity\User;
use App\Service\TodoService;
use App\Service\TodoServiceInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class TodoServiceTest.
 */
class TodoServiceTest extends KernelTestCase
{
    /**
     * Entity manager.
     */
    private ?EntityManagerInterface $entityManager;

    /**
     * Todo service.
     */
    private ?TodoServiceInterface $todoService;

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
        $this->todoService = $container->get(TodoService::class);
    }

    /**
     * Test save.
     *
     * @throws ORMException
     */
    public function testSave(): void
    {
        // given
        $user = $this->createUser('todo_user_save@example.com');
        $expectedTodo = new Todo();
        $expectedTodo->setTitle('Test Todo');
        $expectedTodo->setIsDone(false);
        $expectedTodo->setAuthor($user);

        // when
        $this->todoService->save($expectedTodo);

        // then
        $expectedTodoId = $expectedTodo->getId();
        $resultTodo = $this->entityManager->createQueryBuilder()
            ->select('todo')
            ->from(Todo::class, 'todo')
            ->where('todo.id = :id')
            ->setParameter(':id', $expectedTodoId, Types::INTEGER)
            ->getQuery()
            ->getSingleResult();

        $this->assertEquals($expectedTodo, $resultTodo);
        $this->assertEquals($user, $resultTodo->getAuthor());
    }

    /**
     * Test delete.
     *
     * @throws OptimisticLockException|ORMException
     */
    public function testDelete(): void
    {
        // given
        $user = $this->createUser('todo_user_delete@example.com');
        $todoToDelete = new Todo();
        $todoToDelete->setTitle('Todo to Delete');
        $todoToDelete->setAuthor($user);
        $this->entityManager->persist($todoToDelete);
        $this->entityManager->flush();
        $deletedTodoId = $todoToDelete->getId();

        // when
        $this->todoService->delete($todoToDelete);

        // then
        $resultTodo = $this->entityManager->createQueryBuilder()
            ->select('todo')
            ->from(Todo::class, 'todo')
            ->where('todo.id = :id')
            ->setParameter(':id', $deletedTodoId, Types::INTEGER)
            ->getQuery()
            ->getOneOrNullResult();

        $this->assertNull($resultTodo);
    }

    /**
     * Test get paginated list.
     */
    public function testGetPaginatedList(): void
    {
        // given
        $user = $this->createUser('todo_user_list@example.com');
        $page = 1;
        $dataSetSize = 3;
        $expectedResultSize = 3;

        $counter = 0;
        while ($counter < $dataSetSize) {
            $todo = new Todo();
            $todo->setTitle('Test Todo #'.$counter);
            $todo->setAuthor($user);
            $this->todoService->save($todo);

            ++$counter;
        }

        // when
        $result = $this->todoService->getPaginatedList($page, $user);

        // then
        $this->assertEquals($expectedResultSize, $result->count());
    }

    /**
     * Create user helper.
     *
     * @param string $email Email
     *
     * @return User User entity
     */
    private function createUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword('password');
        $user->setRoles(['ROLE_USER']);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
