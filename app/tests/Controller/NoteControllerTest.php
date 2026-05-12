<?php
/**
 * Note Controller test.
 */

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\Enum\NoteStatus;
use App\Entity\Enum\UserRole;
use App\Entity\Note;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class NoteControllerTest.
 */
class NoteControllerTest extends WebTestCase
{
    /**
     * Test route.
     *
     * @var string
     */
    public const TEST_ROUTE = '/note';

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
        $adminUser = $this->createUser([UserRole::ROLE_USER->value, UserRole::ROLE_ADMIN->value], 'admin_note@example.com');
        $this->httpClient->loginUser($adminUser);

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE);
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test show single note.
     */
    public function testShowNote(): void
    {
        // given
        $user = $this->createUser([UserRole::ROLE_USER->value], 'note_viewer@example.com');
        $this->httpClient->loginUser($user);

        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');

        $category = new Category();
        $category->setTitle('Note Category');
        $entityManager->persist($category);

        $note = new Note();
        $note->setTitle('Note To Show');
        $note->setComment('Detailed content of the note');
        $note->setAuthor($user);
        $note->setCategory($category);
        $note->setStatus(NoteStatus::ACTIVE);

        $entityManager->persist($note);
        $entityManager->flush();

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$note->getId());
        $result = $this->httpClient->getResponse();

        // then
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertSelectorTextContains('html h1', '#'.$note->getId());
        $this->assertSelectorTextContains('html', 'Detailed content of the note');
    }

    /**
     * Test edit route for non-authorized user.
     */
    public function testEditRouteForbiddenForNonAuthor(): void
    {
        // given
        $expectedStatusCode = 403;
        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');

        $author = $this->createUser([UserRole::ROLE_USER->value], 'note_author_a@example.com');

        $category = new Category();
        $category->setTitle('Note Category Forbidden');
        $entityManager->persist($category);

        $note = new Note();
        $note->setTitle('Secret Note');
        $note->setAuthor($author);
        $note->setCategory($category);
        $note->setStatus(NoteStatus::ACTIVE);
        $entityManager->persist($note);
        $entityManager->flush();

        $otherUser = $this->createUser([UserRole::ROLE_USER->value], 'note_user_b@example.com');
        $this->httpClient->loginUser($otherUser);

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$note->getId().'/edit');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Create user helper.
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
