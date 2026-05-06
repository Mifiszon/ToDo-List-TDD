<?php

/**
 * Todo entity.
 */

namespace App\Entity;

use App\Repository\TodoRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Todo.
 */
#[ORM\Entity(repositoryClass: TodoRepository::class)]
class Todo
{
    /**
     * Primary key.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Title.
     */
    #[ORM\Column(length: 255)]
    private ?string $title = null;

    /**
     * is Done?.
     */
    #[ORM\Column]
    private ?bool $isDone = false;

    /**
     * Author.
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author = null;

    /**
     * Getter for ID.
     *
     * @return int|null Id
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Getter for title.
     *
     * @return string|null Title
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Setter for title.
     *
     * @param string $title Title
     *
     * @return Todo
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * is Done?.
     *
     * @return bool|null isDone
     */
    public function isDone(): ?bool
    {
        return $this->isDone;
    }

    /**
     * Setter isDone.
     *
     * @param bool|null $isDone isDone
     *
     * @return Todo Todo
     */
    public function setIsDone(bool $isDone): static
    {
        $this->isDone = $isDone;

        return $this;
    }

    /**
     * Getter for author.
     *
     * @return User|null User
     */
    public function getAuthor(): ?User
    {
        return $this->author;
    }

    /**
     * Setter for author.
     *
     * @param User|null $author Author
     *
     * @return Todo Todo
     */
    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }
}
