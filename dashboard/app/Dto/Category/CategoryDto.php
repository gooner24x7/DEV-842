<?php
declare(strict_types=1);

namespace App\Dto\Category;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryDto
{
    private string $name;
    private bool $active;
    private string $slug;
    private int $categoryId;
    private int $type;

    public function __construct(string $name, bool $active, int $categoryId, int $type)
    {
        $this->name = $name;
        $this->active = $active;
        $this->slug = Str::slug($name, '-');
        $this->categoryId = $categoryId;
        $this->type = $type;
    }

    /**
     * @param Request $request
     * @return CategoryDto
     */
    public static function createFromRequest(Request $request): self
    {
        $data = $request->validate([
            'name' => 'required',
            'category_id' => '',
            'active' => '',
            'type' => '',
        ]);

        return new self(
            $data['name'] ?? '',
            (bool)($data['active'] ?? false),
            (int)($data['category_id'] ?? 0),
            (int)($data['type'] ?? 0)
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function getType(): int
    {
        return $this->type;
    }
}
