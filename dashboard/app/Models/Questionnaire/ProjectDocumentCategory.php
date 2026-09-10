<?php

namespace App\Models\Questionnaire;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $parent_id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property bool $active
 */
class ProjectDocumentCategory extends Model
{
    protected $table = 'project_document_categories';

    public function documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class, 'category_id', 'id');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getParentId(): int
    {
        return $this->parent_id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getActive(): bool
    {
        return $this->active;
    }
}
