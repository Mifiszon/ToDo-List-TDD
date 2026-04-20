<?php
/**
 * Note list filters DTO.
 */

namespace App\Dto;

use App\Entity\Category;
use App\Entity\Enum\NoteStatus;
use App\Entity\Tag;

/**
 * Class NoteListFiltersDto.
 */
class NoteListFiltersDto
{
    /**
     * Constructor.
     *
     * @param Category|null $category   Category entity
     * @param Tag|null      $tag        Tag entity
     * @param NoteStatus    $noteStatus Note status
     */
    public function __construct(public readonly ?Category $category, public readonly ?Tag $tag, public readonly NoteStatus $noteStatus)
    {
    }
}
