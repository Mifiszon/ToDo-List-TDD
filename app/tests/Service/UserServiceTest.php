<?php

/**
 * User service tests.
 */

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserService;
use App\Service\UserServiceInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class UserServiceTest.
 */
class UserServiceTest extends KernelTestCase
{
    /**
     * Entity manager.
     */
    private ?EntityManagerInterface $entityManager;

    /**
     * User service.
     */
    private ?UserServiceInterface $userService;

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
        $this->userService = $container->get(UserService::class);
    }

    /**
     * Test register.
     */
    public function testRegister(): void
    {
        // given
        $user = new User();
        $user->setEmail('new_user@example.com');
        $password = 'p@ssword123';

        // when
        $this->userService->register($user, $password);

        // then
        $resultUser = $this->entityManager->createQueryBuilder()
            ->select('user')
            ->from(User::class, 'user')
            ->where('user.email = :email')
            ->setParameter(':email', 'new_user@example.com', Types::STRING)
            ->getQuery()
            ->getSingleResult();

        $this->assertNotNull($resultUser);
        $this->assertEquals('new_user@example.com', $resultUser->getEmail());
        $this->assertContains('ROLE_USER', $resultUser->getRoles());
        $this->assertNotEquals($password, $resultUser->getPassword());
    }

    /**
     * Test save (update roles).
     */
    public function testSave(): void
    {
        // given
        $user = new User();
        $user->setEmail('roles_test@example.com');
        $user->setPassword('password');
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // when
        $user->setRoles(['ROLE_USER']);
        $this->userService->save($user);

        // then
        $resultUser = $this->entityManager->createQueryBuilder()
            ->select('user')
            ->from(User::class, 'user')
            ->where('user.id = :id')
            ->setParameter(':id', $user->getId(), Types::INTEGER)
            ->getQuery()
            ->getSingleResult();

        $this->assertContains('ROLE_USER', $resultUser->getRoles());
        $this->assertNotContains('ROLE_ADMIN', $resultUser->getRoles());
    }

    /**
     * Test change password.
     */
    public function testChangePassword(): void
    {
        // given
        $user = new User();
        $user->setEmail('pass_change@example.com');
        $user->setPassword('old_password');
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $newPassword = 'new_awesome_password';

        // when
        $this->userService->changePassword($user, $newPassword);

        // then
        $resultUser = $this->entityManager->createQueryBuilder()
            ->select('user')
            ->from(User::class, 'user')
            ->where('user.id = :id')
            ->setParameter(':id', $user->getId(), Types::INTEGER)
            ->getQuery()
            ->getSingleResult();

        $this->assertNotEquals('old_password', $resultUser->getPassword());
        $this->assertNotEquals($newPassword, $resultUser->getPassword());
    }

    /**
     * Test get paginated list.
     */
    public function testGetPaginatedList(): void
    {
        // given
        $page = 1;
        $dataSetSize = 3;

        for ($i = 0; $i < $dataSetSize; ++$i) {
            $user = new User();
            $user->setEmail('user_list_'.$i.'@example.com');
            $user->setPassword('password');
            $this->entityManager->persist($user);
        }
        $this->entityManager->flush();

        // when
        $result = $this->userService->getPaginatedList($page);

        // then
        $this->assertGreaterThanOrEqual($dataSetSize, $result->count());
    }

    /**
     * Test delete.
     */
    public function testDelete(): void
    {
        // given
        $user = new User();
        $user->setEmail('to_delete@example.com');
        $user->setPassword('password');
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $userId = $user->getId();

        // when
        $this->userService->delete($user);

        // then
        $resultUser = $this->entityManager->createQueryBuilder()
            ->select('user')
            ->from(User::class, 'user')
            ->where('user.id = :id')
            ->setParameter(':id', $userId, Types::INTEGER)
            ->getQuery()
            ->getOneOrNullResult();

        $this->assertNull($resultUser);
    }
}
