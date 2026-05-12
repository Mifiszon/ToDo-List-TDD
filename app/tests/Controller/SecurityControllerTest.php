<?php

/**
 * Security Controller test.
 */

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class SecurityControllerTest.
 */
class SecurityControllerTest extends WebTestCase
{
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
     * Test login route for anonymous user.
     */
    public function testLoginRouteAnonymousUser(): void
    {
        // when
        $this->httpClient->request('GET', '/login');

        // then
        $this->assertEquals(200, $this->httpClient->getResponse()->getStatusCode());
        $this->assertSelectorExists('input[name="_username"]');
    }

    /**
     * Test login route for already logged-in user.
     */
    public function testLoginRouteLoggedInUser(): void
    {
        // given
        $user = $this->createUser('logged_in_security@example.com');
        $this->httpClient->loginUser($user);

        // when
        $this->httpClient->request('GET', '/login');

        // then
        $this->assertResponseRedirects('/note');
    }

    /**
     * Test profile route for logged-in user.
     */
    public function testProfileRoute(): void
    {
        // given
        $user = $this->createUser('profile_test@example.com');
        $this->httpClient->loginUser($user);

        // when
        $this->httpClient->request('GET', '/profile');

        // then
        $this->assertEquals(200, $this->httpClient->getResponse()->getStatusCode());
        $this->assertSelectorTextContains('html', $user->getEmail());
    }

    /**
     * Test change password route.
     */
    public function testChangePasswordRoute(): void
    {
        // given
        $user = $this->createUser('change_pass@example.com');
        $this->httpClient->loginUser($user);

        // when
        $crawler = $this->httpClient->request('GET', '/change-password');

        // then
        $this->assertEquals(200, $this->httpClient->getResponse()->getStatusCode());
        $this->assertSelectorExists('form[name="change_password"]');

        // given
        $form = $crawler->filter('button, input[type="submit"]')->last()->form([
            'change_password' => [
                'password' => [
                    'first' => 'new_password123',
                    'second' => 'new_password123',
                ],
            ],
        ]);

        // when
        $this->httpClient->submit($form);

        // then
        $this->assertResponseRedirects('/note');
    }

    /**
     * Test logout route.
     */
    public function testLogoutRoute(): void
    {
        // given
        $user = $this->createUser('logout_test@example.com');
        $this->httpClient->loginUser($user);

        // when
        $this->httpClient->request('GET', '/logout');

        // then
        $this->assertTrue($this->httpClient->getResponse()->isRedirect());
    }

    /**
     * Create user helper.
     *
     * @param string $email User email
     *
     * @return User User entity
     */
    private function createUser(string $email): User
    {
        $passwordHasher = static::getContainer()->get('security.password_hasher');
        $user = new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($passwordHasher->hashPassword($user, 'p@55w0rd'));

        $userRepository = static::getContainer()->get(UserRepository::class);
        $userRepository->save($user);

        return $user;
    }
}
