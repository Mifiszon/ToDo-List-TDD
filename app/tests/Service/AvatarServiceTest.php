<?php
/**
 * Avatar service tests.
 */

namespace App\Tests\Service;

use App\Entity\Avatar;
use App\Entity\User;
use App\Service\AvatarService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class AvatarServiceTest.
 */
class AvatarServiceTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?AvatarService $avatarService;

    /**
     * Set up test.
     */
    public function setUp(): void
    {
        $container = static::getContainer();
        $this->entityManager = $container->get('doctrine.orm.entity_manager');
        $this->avatarService = $container->get(AvatarService::class);
    }

    /**
     * Test create avatar.
     */
    public function testCreate(): void
    {
        // given
        $user = $this->createUser('avatar_user_'.uniqid().'@example.com');
        $avatar = new Avatar();
        $sourcePath = tempnam(sys_get_temp_dir(), 'test_avatar');
        file_put_contents($sourcePath, 'test content');

        $uploadedFile = new UploadedFile(
            $sourcePath,
            'avatar.png',
            'image/png',
            null,
            true
        );

        // when
        $this->avatarService->create($uploadedFile, $avatar, $user);

        // then
        $result = $this->entityManager->getRepository(Avatar::class)->findOneBy(['user' => $user]);

        $this->assertNotNull($result);
        $this->assertEquals($user->getId(), $result->getUser()->getId());
        $this->assertNotEmpty($result->getFilename());

        $path = $container = static::getContainer()->getParameter('avatars_directory').'/'.$result->getFilename();
        if (file_exists($path)) {
            unlink($path);
        }
    }
    /**
     * Test delete avatar.
     */
    public function testDelete(): void
    {
        // given
        $user = $this->createUser('avatar_delete@example.com');
        $avatar = new Avatar();
        $avatar->setUser($user);
        $avatar->setFilename('test_avatar.png');
        $this->entityManager->persist($avatar);
        $this->entityManager->flush();
        $avatarId = $avatar->getId();

        // when
        $this->avatarService->delete($avatar);

        // then
        $result = $this->entityManager->getRepository(Avatar::class)->find($avatarId);
        $this->assertNull($result);
    }

    /**
     * Helper to create User.
     * @param string $email User Email
     *
     * @return User User entity
     */
    private function createUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword('password');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
