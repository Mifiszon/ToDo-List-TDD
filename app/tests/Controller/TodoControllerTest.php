<?php

/**
 * Todo Controller test.
 */

namespace App\Tests\Controller;

use App\Entity\Enum\UserRole;
use App\Entity\Todo;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class TodoControllerTest.
 */
class TodoControllerTest extends WebTestCase
{
    /**
     * Test route.
     *
     * @var string
     */
    public const TEST_ROUTE = '/todo';

    /**
     * Test client.
     */
    private KernelBrowser $httpClient;

    /**
     * Set up tests.
     */
    public function setUp(): void
    {
        $this->httpClient = static::createClient();
    }

    /**
     * Test index route for anonymous user.
     */
    public function testIndexRouteAnonymousUser(): void
    {
        // given
        $expectedStatusCode = 302;

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE);
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test index route for admin user.
     */
    public function testIndexRouteAdminUser(): void
    {
        // given
        $expectedStatusCode = 200;
        $adminUser = $this->createUser([UserRole::ROLE_USER->value, UserRole::ROLE_ADMIN->value], 'admin_todo@example.com');
        $this->httpClient->loginUser($adminUser);

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE);
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test show single todo.
     */
    public function testShowTodo(): void
    {
        // given
        $user = $this->createUser([UserRole::ROLE_USER->value], 'test_show_todo@example.com');
        $this->httpClient->loginUser($user);

        $todo = new Todo();
        $todo->setTitle('Unique Todo Title');
        $todo->setAuthor($user);
        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $entityManager->persist($todo);
        $entityManager->flush();

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$todo->getId());
        $result = $this->httpClient->getResponse();

        // then
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertSelectorTextContains('html h1', $todo->getTitle());
    }

    /**
     * Test edit route for non-authorized user (Accessing someone else's todo).
     */
    public function testEditRouteForbiddenForNonAuthor(): void
    {
        // given
        $expectedStatusCode = 403;
        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');

        $author = $this->createUser([UserRole::ROLE_USER->value], 'author_a@example.com');
        $todo = new Todo();
        $todo->setTitle('Author A Todo');
        $todo->setAuthor($author);
        $entityManager->persist($todo);
        $entityManager->flush();

        $otherUser = $this->createUser([UserRole::ROLE_USER->value], 'user_b@example.com');
        $this->httpClient->loginUser($otherUser);

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$todo->getId().'/edit');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test create todo via form.
     */
    public function testCreateTodo(): void
    {
        // given
        $user = $this->createUser([UserRole::ROLE_USER->value], 'todo_creator@example.com');
        $this->httpClient->loginUser($user);
        $todoTitle = 'New Task '.uniqid();
        $crawler = $this->httpClient->request('GET', self::TEST_ROUTE.'/create');

        $form = $crawler->filter('button[type="submit"], input[type="submit"]')->last()->form([
            'todo' => [
                'title' => $todoTitle,
            ],
        ]);

        // when
        $this->httpClient->submit($form);

        // then
        $this->assertResponseRedirects(self::TEST_ROUTE);
        $this->httpClient->followRedirect();
        $this->assertSelectorExists('.alert-success');

        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $createdTodo = $entityManager->getRepository(Todo::class)->findOneBy(['title' => $todoTitle]);

        $this->assertNotNull($createdTodo);
        $this->assertEquals($user->getId(), $createdTodo->getAuthor()->getId());
    }

    /**
     * Test edit todo by its author.
     */
    public function testEditTodoByAuthor(): void
    {
        // given
        $user = $this->createUser([UserRole::ROLE_USER->value], 'todo_author@example.com');
        $this->httpClient->loginUser($user);

        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $todo = new Todo();
        $todo->setTitle('Old Todo Title');
        $todo->setAuthor($user);
        $entityManager->persist($todo);
        $entityManager->flush();

        $updatedTitle = 'Updated Todo Title '.uniqid();

        // when
        $crawler = $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$todo->getId().'/edit');
        $form = $crawler->filter('button[type="submit"], input[type="submit"]')->last()->form([
            'todo' => [
                'title' => $updatedTitle,
            ],
        ]);
        $this->httpClient->submit($form);

        // then
        $this->assertResponseRedirects(self::TEST_ROUTE);

        $entityManager->clear();
        $refreshedTodo = $entityManager->getRepository(Todo::class)->find($todo->getId());

        $this->assertEquals($updatedTitle, $refreshedTodo->getTitle());
    }

    /**
     * Test delete todo.
     */
    public function testDeleteTodo(): void
    {
        // given
        $user = $this->createUser([UserRole::ROLE_USER->value], 'todo_deleter@example.com');
        $this->httpClient->loginUser($user);

        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $todo = new Todo();
        $todo->setTitle('To Be Deleted');
        $todo->setAuthor($user);
        $entityManager->persist($todo);
        $entityManager->flush();
        $todoId = $todo->getId();

        // when
        $crawler = $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$todoId.'/delete');
        $form = $crawler->filter('button[type="submit"], input[type="submit"]')->last()->form();
        $this->httpClient->submit($form);

        // then
        $this->assertResponseRedirects(self::TEST_ROUTE);

        $entityManager->clear();
        $deletedTodo = $entityManager->getRepository(Todo::class)->find($todoId);
        $this->assertNull($deletedTodo);
    }

    /**
     * Create user helper.
     *
     * @param array  $roles User roles
     * @param string $email User email
     *
     * @return User User entity
     */
    private function createUser(array $roles, string $email): User
    {
        $passwordHasher = static::getContainer()->get('security.password_hasher');
        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setPassword($passwordHasher->hashPassword($user, 'p@55w0rd'));

        $userRepository = static::getContainer()->get(UserRepository::class);
        $userRepository->save($user);

        return $user;
    }
}
