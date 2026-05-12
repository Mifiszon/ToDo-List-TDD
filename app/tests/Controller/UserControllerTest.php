<?php
/**
 * User Controller test.
 */

namespace App\Tests\Controller;

use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class UserControllerTest.
 */
class UserControllerTest extends WebTestCase
{
    /**
     * Test route.
     *
     * @var string
     */
    public const TEST_ROUTE = '/user';

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
     * Test index route for non-authorized user (Regular user).
     */
    public function testIndexRouteNonAuthorizedUser(): void
    {
        // given
        $user = $this->createUser([UserRole::ROLE_USER->value], 'regular_user@example.com');
        $this->httpClient->loginUser($user);

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE);

        // then
        $this->assertEquals(403, $this->httpClient->getResponse()->getStatusCode());
    }

    /**
     * Test index route for admin user.
     */
    public function testIndexRouteAdminUser(): void
    {
        // given
        $admin = $this->createUser([UserRole::ROLE_USER->value, UserRole::ROLE_ADMIN->value], 'admin_access@example.com');
        $this->httpClient->loginUser($admin);

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE);

        // then
        $this->assertEquals(200, $this->httpClient->getResponse()->getStatusCode());
    }

    /**
     * Test delete self (Should be forbidden by controller logic).
     */
    public function testDeleteSelf(): void
    {
        // given
        $admin = $this->createUser([UserRole::ROLE_USER->value, UserRole::ROLE_ADMIN->value], 'delete_self@example.com');
        $this->httpClient->loginUser($admin);

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE . '/' . $admin->getId() . '/delete');

        // then
        $this->assertResponseRedirects(self::TEST_ROUTE);
        $this->httpClient->followRedirect();
        $this->assertSelectorExists('.alert-warning');
    }

    /**
     * Test grant admin role.
     */
    public function testGrantAdmin(): void
    {
        // given
        $admin = $this->createUser([UserRole::ROLE_USER->value, UserRole::ROLE_ADMIN->value], 'master_admin@example.com');
        $userToUpgrade = $this->createUser([UserRole::ROLE_USER->value], 'to_upgrade@example.com');
        $this->httpClient->loginUser($admin);

        // when
        $this->httpClient->request('POST', self::TEST_ROUTE . '/' . $userToUpgrade->getId() . '/grant-admin');

        // then
        $this->assertResponseRedirects(self::TEST_ROUTE);
        $userRepository = static::getContainer()->get(UserRepository::class);
        $upgradedUser = $userRepository->find($userToUpgrade->getId());
        $this->assertContains(UserRole::ROLE_ADMIN->value, $upgradedUser->getRoles());
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
