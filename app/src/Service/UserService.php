<?php

/**
 * User service.
 */

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Class UserService.
 */
class UserService implements UserServiceInterface
{
    public const PAGINATOR_ITEMS_PER_PAGE = 10;

    /**
     * Constructor.
     *
     * @param UserRepository              $userRepository User repository
     * @param UserPasswordHasherInterface $passwordHasher Password hasher
     * @param PaginatorInterface          $paginator      Paginator
     */
    public function __construct(private readonly UserRepository $userRepository, private readonly UserPasswordHasherInterface $passwordHasher, private readonly PaginatorInterface $paginator)
    {
    }

    /**
     * Get paginated list.
     *
     * @param int $page Page number
     *
     * @return PaginationInterface Paginated list
     */
    public function getPaginatedList(int $page): PaginationInterface
    {
        return $this->paginator->paginate(
            $this->userRepository->queryAll(),
            $page,
            self::PAGINATOR_ITEMS_PER_PAGE
        );
    }

    /**
     * Save user.
     *
     * @param User $user User entity
     *
     * @throws \LogicException
     */
    public function save(User $user): void
    {
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            $userInDb = $this->userRepository->find($user->getId());

            if ($userInDb && in_array('ROLE_ADMIN', $userInDb->getRoles())) {
                if ($this->userRepository->countAdmins() <= 1) {
                    throw new \LogicException('message.last_admin_error');
                }
            }
        }

        if (empty($user->getRoles())) {
            $user->setRoles(['ROLE_USER']);
        }

        $this->userRepository->save($user);
    }

    /**
     * @param User $user
     *
     * @return void
     */
    public function delete(User $user): void
    {
        $this->userRepository->delete($user);
    }

    /**
     * Change password.
     * @param User   $user
     * @param string $newPassword
     */
    public function changePassword(User $user, string $newPassword): void
    {
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $newPassword)
        );

        $this->userRepository->save($user);
    }

    /**
     * Register.
     *
     * @param User   $user
     * @param string $plainPassword
     *
     * @throws \Exception
     */
    public function register(User $user, string $plainPassword): void
    {
        if ($this->userRepository->findOneByEmail($user->getEmail())) {
            throw new \Exception('User already exists');
        }

        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $plainPassword)
        );

        $user->setRoles(['ROLE_USER']);

        $this->userRepository->save($user);
    }

    /**
     * Set user roles.
     *
     * @param User  $user  User entity
     * @param array $roles Roles array
     */
    public function setUserRoles(User $user, array $roles): void
    {
        $user->setRoles($roles);
        $this->userRepository->save($user);
    }
}
