<?php

/**
 * Tag Controller test.
 */

namespace App\Tests\Controller;

use App\Entity\Note;
use App\Entity\Enum\NoteStatus;
use App\Entity\Category;
use App\Entity\Enum\UserRole;
use App\Entity\Tag;
use App\Entity\User;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class TagControllerTest.
 */
class TagControllerTest extends WebTestCase
{
    /**
     * Test route.
     *
     * @var string
     */
    public const TEST_ROUTE = '/tag';

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
        $adminUser = $this->createUser([UserRole::ROLE_USER->value, UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($adminUser);

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE);
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test show single tag.
     */
    public function testShowTag(): void
    {
        // given
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($adminUser);

        $expectedTag = new Tag();
        $expectedTag->setTitle('Test tag');
        $tagRepository = static::getContainer()->get(TagRepository::class);
        $tagRepository->save($expectedTag);

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$expectedTag->getId());
        $result = $this->httpClient->getResponse();

        // then
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertSelectorTextContains('html h1', $expectedTag->getTitle());
    }

    /**
     * Test index route for non-authorized user.
     */
    public function testIndexRouteNonAuthorizedUser(): void
    {
        // given
        $expectedStatusCode = 200;
        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($user);

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE);
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test edit route for non-authorized user (Regular user should get 403).
     */
    public function testEditRouteNonAuthorizedUser(): void
    {
        // given
        $expectedStatusCode = 403;

        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $tag = new Tag();
        $tag->setTitle('Test Tag');
        $entityManager->persist($tag);
        $entityManager->flush();

        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($user);

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$tag->getId().'/edit');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test create tag.
     */
    public function testCreateTag(): void
    {
        // given
        $adminUser = $this->createUser([UserRole::ROLE_USER->value, UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($adminUser);
        $tagTitle = 'New Tag '.uniqid();

        // when
        $crawler = $this->httpClient->request('GET', self::TEST_ROUTE.'/create');
        $form = $crawler->filter('button[type="submit"], input[type="submit"]')->last()->form([
            'tag' => [
                'title' => $tagTitle,
            ],
        ]);
        $this->httpClient->submit($form);

        // then
        $this->assertResponseRedirects(self::TEST_ROUTE);
        $this->httpClient->followRedirect();
        $this->assertSelectorExists('.alert-success');

        $tagRepository = static::getContainer()->get(TagRepository::class);
        $createdTag = $tagRepository->findOneBy(['title' => $tagTitle]);
        $this->assertNotNull($createdTag);
    }

    /**
     * Test edit tag.
     */
    public function testEditTag(): void
    {
        // given
        $adminUser = $this->createUser([UserRole::ROLE_USER->value, UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($adminUser);

        $tag = new Tag();
        $tag->setTitle('Old Tag Title');
        $tagRepository = static::getContainer()->get(TagRepository::class);
        $tagRepository->save($tag);
        $newTitle = 'Updated Tag Title '.uniqid();

        // when
        $crawler = $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$tag->getId().'/edit');
        $form = $crawler->filter('button[type="submit"], input[type="submit"]')->last()->form([
            'tag' => [
                'title' => $newTitle,
            ],
        ]);
        $this->httpClient->submit($form);

        // then
        $this->assertResponseRedirects(self::TEST_ROUTE);
        $updatedTag = $tagRepository->find($tag->getId());
        $this->assertEquals($newTitle, $updatedTag->getTitle());
    }

    /**
     * Test delete tag.
     */
    public function testDeleteTag(): void
    {
        // given
        $adminUser = $this->createUser([UserRole::ROLE_USER->value, UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($adminUser);

        $tag = new Tag();
        $tag->setTitle('Tag To Delete');
        $tagRepository = static::getContainer()->get(TagRepository::class);
        $tagRepository->save($tag);
        $tagId = $tag->getId();

        // when
        $crawler = $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$tagId.'/delete');
        $form = $crawler->filter('button[type="submit"], input[type="submit"]')->last()->form();
        $this->httpClient->submit($form);

        // then
        $this->assertResponseRedirects(self::TEST_ROUTE);
        $deletedTag = $tagRepository->find($tagId);
        $this->assertNull($deletedTag);
    }

    /**
     * Test delete tag with notes.
     */
    public function testDeleteTagWithNotes(): void
    {
        // given
        $adminUser = $this->createUser([UserRole::ROLE_USER->value, UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($adminUser);

        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');

        $tag = new Tag();
        $tag->setTitle('Busy Tag');
        $entityManager->persist($tag);

        $note = new Note();
        $note->setTitle('Note for tag');
        $note->setAuthor($adminUser);
        $note->addTag($tag);
        $note->setStatus(NoteStatus::ACTIVE);

        $category = new Category();
        $category->setTitle('Cat for tag test');
        $entityManager->persist($category);
        $note->setCategory($category);

        $entityManager->persist($note);
        $entityManager->flush();

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$tag->getId().'/delete');

        // then
        $this->assertResponseRedirects(self::TEST_ROUTE);
        $this->httpClient->followRedirect();
        $this->assertSelectorExists('.alert-warning');
    }

    /**
     * Create user helper with unique email.
     *
     * @param array $roles User roles
     *
     * @return User User entity
     */
    private function createUser(array $roles): User
    {
        $passwordHasher = static::getContainer()->get('security.password_hasher');
        $user = new User();
        $user->setEmail('tag_test_'.uniqid().'@example.com');
        $user->setRoles($roles);
        $user->setPassword(
            $passwordHasher->hashPassword($user, 'p@55w0rd')
        );

        $userRepository = static::getContainer()->get(UserRepository::class);
        $userRepository->save($user);

        return $user;
    }
}
