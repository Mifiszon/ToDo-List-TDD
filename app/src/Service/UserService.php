<?php

/**
 * User service.
 */

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Class UserService.
 */
class UserService implements UserServiceInterface
{

    /**
     * Constructor.
     *
     * @param UserRepository              $userRepository User repository
     * @param UserPasswordHasherInterface $passwordHasher Password hasher
     */
    public function __construct(private readonly UserRepository $userRepository, private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    /**
     * @param User   $user
     * @param string $newPassword
     *
     * @return void
     */
    public function changePassword(User $user, string $newPassword): void
    {
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $newPassword)
        );

        $this->userRepository->save($user);
    }
}
