<?php

/**
 * Avatar Controller test.
 */

namespace App\Tests\Controller;

use App\Entity\Avatar;
use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class AvatarControllerTest.
 */
class AvatarControllerTest extends WebTestCase
{
    /**
     * Test route.
     */
    public const TEST_ROUTE = '/avatar';

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
     * Test create avatar.
     */
    public function testCreateAvatar(): void
    {
        // given
        $user = $this->createUser([UserRole::ROLE_USER->value], 'avatar_fixture@example.com');
        $this->httpClient->loginUser($user);

        $fixturePath = __DIR__.'/../Fixtures/avatar.png';
        if (!file_exists($fixturePath)) {
            $this->fail('Not found: '.$fixturePath);
        }
        $tempPath = sys_get_temp_dir().'/avatar_test.png';
        copy($fixturePath, $tempPath);

        $uploadedFile = new UploadedFile(
            $tempPath,
            'avatar.png',
            'image/png',
            null,
            true
        );

        $crawler = $this->httpClient->request('GET', self::TEST_ROUTE.'/create');

        // when
        $form = $crawler->filter('button, input[type="submit"]')->first()->form();
        $form['avatar[file]']->upload($uploadedFile);
        $this->httpClient->submit($form);

        // then
        $this->assertResponseRedirects('/profile');
        $this->httpClient->followRedirect();
        $this->assertSelectorExists('.alert-success');

        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }
    }

    /**
     * Test edit forbidden avatar.
     */
    public function testEditForbiddenAvatar(): void
    {
        // given
        $owner = $this->createUser([UserRole::ROLE_USER->value], 'owner@example.com');
        $hacker = $this->createUser([UserRole::ROLE_USER->value], 'hacker@example.com');

        $avatar = new Avatar();
        $avatar->setUser($owner);
        $avatar->setFilename('owner_avatar.png');
        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $entityManager->persist($avatar);
        $entityManager->flush();

        $this->httpClient->loginUser($hacker);

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$avatar->getId().'/edit');

        // then
        $this->assertEquals(403, $this->httpClient->getResponse()->getStatusCode());
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
