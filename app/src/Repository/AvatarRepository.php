<?php

/**
 * Avatar repository.
 */

namespace App\Repository;

use App\Entity\Avatar;
use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class Avatar repository.
 *
 * @extends ServiceEntityRepository<Avatar>
 */
class AvatarRepository extends ServiceEntityRepository
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Avatar::class);
    }

    /**
     * Save entity.
     *
     * @param Avatar $avatar
     *
     * @return void
     */
    public function save(Avatar $avatar): void
    {
        $this->getEntityManager()->persist($avatar);
        $this->getEntityManager()->flush();
    }
}
