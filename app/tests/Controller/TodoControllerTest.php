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
        $this->httpClient->request('GET', self::TEST_ROUTE . '/' . $todo->getId());
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
        $this->httpClient->request('GET', self::TEST_ROUTE . '/' . $todo->getId() . '/edit');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Create user helper.
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
