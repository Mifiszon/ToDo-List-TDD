<?php

/**
 * Category Controller test.
 */

namespace App\Tests\Controller;

use App\Entity\Note;
use App\Entity\Enum\NoteStatus;
use App\Entity\Category;
use App\Entity\Enum\UserRole;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class CategoryControllerTest.
 */
class CategoryControllerTest extends WebTestCase
{
    /**
     * Test route.
     *
     * @var string
     */
    public const TEST_ROUTE = '/category';

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
     * Test create category.
     */
    public function testCreateCategory(): void
    {
        // given
        $adminUser = $this->createUser([UserRole::ROLE_USER->value, UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($adminUser);
        $categoryTitle = 'New Category '.uniqid();

        // when
        $crawler = $this->httpClient->request('GET', self::TEST_ROUTE.'/create');
        $form = $crawler->filter('button[type="submit"], input[type="submit"]')->first()->form([
            'category' => [
                'title' => $categoryTitle,
            ],
        ]);
        $this->httpClient->submit($form);

        // then
        $this->assertResponseRedirects(self::TEST_ROUTE);
        $this->httpClient->followRedirect();
        $this->assertSelectorExists('.alert-success');

        $categoryRepository = static::getContainer()->get(CategoryRepository::class);
        $createdCategory = $categoryRepository->findOneBy(['title' => $categoryTitle]);
        $this->assertNotNull($createdCategory);
    }

    /**
     * Test show single category.
     */
    public function testShowCategory(): void
    {
        // given
        $adminUser = $this->createUser([UserRole::ROLE_ADMIN->value, UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($adminUser);

        $expectedCategory = new Category();
        $expectedCategory->setTitle('Test category');
        $categoryRepository = static::getContainer()->get(CategoryRepository::class);
        $categoryRepository->save($expectedCategory);

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$expectedCategory->getId());
        $result = $this->httpClient->getResponse();

        // then
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertSelectorTextContains('html h1', $expectedCategory->getTitle());
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
     * Test edit category.
     */
    public function testEditCategory(): void
    {
        // given
        $adminUser = $this->createUser([UserRole::ROLE_USER->value, UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($adminUser);

        $category = new Category();
        $category->setTitle('Old Title');
        $categoryRepository = static::getContainer()->get(CategoryRepository::class);
        $categoryRepository->save($category);
        $newTitle = 'Updated Title '.uniqid();

        // when
        $crawler = $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$category->getId().'/edit');

        $form = $crawler->filter('button[type="submit"], input[type="submit"]')->first()->form([
            'category' => [
                'title' => $newTitle,
            ],
        ]);
        $this->httpClient->submit($form);

        // then
        $this->assertResponseRedirects(self::TEST_ROUTE);
        $updatedCategory = $categoryRepository->find($category->getId());
        $this->assertEquals($newTitle, $updatedCategory->getTitle());
    }

    /**
     * Test edit route for non-authorized user.
     */
    public function testEditRouteNonAuthorizedUser(): void
    {
        // given
        $expectedStatusCode = 403;

        $category = new Category();
        $category->setTitle('Test Category');
        $this->getContainer()->get('doctrine.orm.entity_manager')->persist($category);
        $this->getContainer()->get('doctrine.orm.entity_manager')->flush();

        $user = $this->createUser([UserRole::ROLE_USER->value]);
        $this->httpClient->loginUser($user);

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$category->getId().'/edit');
        $resultStatusCode = $this->httpClient->getResponse()->getStatusCode();

        // then
        $this->assertEquals($expectedStatusCode, $resultStatusCode);
    }

    /**
     * Test delete category.
     */
    public function testDeleteCategory(): void
    {
        // given
        $adminUser = $this->createUser([UserRole::ROLE_USER->value, UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($adminUser);

        $category = new Category();
        $category->setTitle('To Delete');
        $categoryRepository = static::getContainer()->get(CategoryRepository::class);
        $categoryRepository->save($category);
        $categoryId = $category->getId();

        // when
        $crawler = $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$categoryId.'/delete');
        $form = $crawler->filter('button[type="submit"], input[type="submit"]')->first()->form();
        $this->httpClient->submit($form);

        // then
        $this->assertResponseRedirects(self::TEST_ROUTE);
        $deletedCategory = $categoryRepository->find($categoryId);
        $this->assertNull($deletedCategory);
    }

    /**
     * Test delete category with notes.
     */
    public function testDeleteCategoryWithNotes(): void
    {
        // given
        $adminUser = $this->createUser([UserRole::ROLE_USER->value, UserRole::ROLE_ADMIN->value]);
        $this->httpClient->loginUser($adminUser);

        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');

        $category = new Category();
        $category->setTitle('Busy Category');
        $entityManager->persist($category);

        $note = new Note();
        $note->setTitle('Test Note');
        $note->setAuthor($adminUser);
        $note->setCategory($category);
        $note->setStatus(NoteStatus::ACTIVE);
        $entityManager->persist($note);
        $entityManager->flush();

        // when
        $this->httpClient->request('GET', self::TEST_ROUTE.'/'.$category->getId().'/delete');

        // then
        $this->assertResponseRedirects(self::TEST_ROUTE);
        $this->httpClient->followRedirect();
        $this->assertSelectorExists('.alert-warning');
    }

    /**
     * Create user.
     *
     * @param array $roles User roles
     *
     * @return User User entity
     */
    private function createUser(array $roles): User
    {
        $passwordHasher = static::getContainer()->get('security.password_hasher');
        $user = new User();
        $user->setEmail('user_'.uniqid().'@example.com');
        $user->setRoles($roles);
        $user->setPassword(
            $passwordHasher->hashPassword($user, 'p@55w0rd')
        );
        $userRepository = static::getContainer()->get(UserRepository::class);
        $userRepository->save($user);

        return $user;
    }
}
