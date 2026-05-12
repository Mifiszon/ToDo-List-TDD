<?php

/**
 * Registration Controller test.
 */

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class RegistrationControllerTest.
 */
class RegistrationControllerTest extends WebTestCase
{
    /**
     * Test route.
     *
     * @var string
     */
    public const TEST_ROUTE = '/register';

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
     * Test register route for anonymous user.
     */
    public function testRegisterRouteAnonymousUser(): void
    {
        // given
        // when
        $this->httpClient->request('GET', self::TEST_ROUTE);
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals(200, $resultStatusCode);
        $this->assertSelectorExists('form[name="registration_form"]');
    }

    /**
     * Test register route for already logged-in user.
     */
    public function testRegisterRouteLoggedInUser(): void
    {
        // given
        $user = new User();
        $user->setEmail('already_logged@example.com');
        $user->setPassword('password');
        $userRepository = static::getContainer()->get(UserRepository::class);
        $userRepository->save($user);

        $this->httpClient->loginUser($user);

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE);

        // then
        $this->assertResponseRedirects('/note');
    }

    /**
     * Test successful registration.
     */
    public function testSuccessfulRegistration(): void
    {
        // given
        $email = 'new_user_'.uniqid().'@example.com';
        $crawler = $this->httpClient->request('GET', self::TEST_ROUTE);

        $form = $crawler->filter('button[type="submit"], input[type="submit"]')->form([
            'registration_form' => [
                'email' => $email,
                'password' => [
                    'first' => 'p12345',
                    'second' => 'p12345',
                ],
            ],
        ]);

        // when
        $this->httpClient->submit($form);

        // then
        $this->assertResponseRedirects('/login');

        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => $email]);
        $this->assertNotNull($user);
    }
}
