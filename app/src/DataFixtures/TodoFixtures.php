<?php

/**
 * Todo fixtures.
 */

namespace App\DataFixtures;

use App\Entity\Todo;
use App\Entity\User;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;

/**
 * Class TodoFixtures.
 *
 * @psalm-suppress MissingConstructor
 */
class TodoFixtures extends AbstractBaseFixtures implements DependentFixtureInterface
{
    /**
     * Load data.
     *
     * @psalm-suppress PossiblyNullPropertyFetch
     * @psalm-suppress PossiblyNullReference
     * @psalm-suppress UnusedClosureParam
     */
    public function loadData(): void
    {
        if (!$this->manager instanceof ObjectManager || !$this->faker instanceof Generator) {
            return;
        }

        $this->createMany(50, 'todo', function (int $i) {
            $todo = new Todo();
            $todo->setTitle($this->faker->sentence(3));
            $todo->setIsDone($this->faker->boolean(40));

            /** @var User $author */
            $author = $this->getRandomReference('user', User::class);
            $todo->setAuthor($author);

            return $todo;
        });
    }

    /**
     * This method must return an array of fixtures classes
     * on which the implementing class depends on.
     *
     * @return string[] of dependencies
     *
     * @psalm-return array{0: UserFixtures::class}
     */
    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
