<?php

/**
 * Task fixtures.
 */

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Tag;
use App\Entity\Task;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;

/**
 * Class TaskFixtures.
 */
class TaskFixtures extends AbstractBaseFixtures implements DependentFixtureInterface
{
    /**
     * Load data.
     */
    public function loadData(): void
    {
        if (!$this->manager instanceof ObjectManager || !$this->faker instanceof Generator) {
            return;
        }

        $this->createMany(100, 'task', function (int $i) {
            $task = new Task();
            $task->setTitle($this->faker->sentence);
            $task->setComment($this->faker->paragraph(3));
            $task->setCreatedAt(
                \DateTimeImmutable::createFromMutable(
                    $this->faker->dateTimeBetween('-100 days', '-1 days')
                )
            );
            $task->setUpdatedAt(
                \DateTimeImmutable::createFromMutable(
                    $this->faker->dateTimeBetween('-100 days', '-1 days')
                )
            );

            /** @var Category $category */
            $category = $this->getRandomReference('category', Category::class);
            $task->setCategory($category);

            for ($j = 0; $j < $this->faker->numberBetween(0, 3); ++$j) {
                /** @var Tag $tag */
                $tag = $this->getRandomReference('tag', Tag::class);
                $task->addTag($tag);
            }

            return $task;
        });
    }

    /**
     * Get dependencies.
     *
     * @return string[] of dependencies
     */
    public function getDependencies(): array
    {
        return [CategoryFixtures::class, TagFixtures::class];
    }
}
