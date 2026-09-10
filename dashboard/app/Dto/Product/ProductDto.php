<?php
declare(strict_types=1);

namespace App\Dto\Product;

use App\Models\Category;
use Illuminate\Http\Request;

class ProductDto
{
    private string $name;
    private int $categoryId;

    public function __construct(string $name, int $categoryId)
    {
        $this->name = $name;
        $this->categoryId = $categoryId;
    }

    /**
     * @param Request $request
     * @return ProductDto
     */
    public static function createFromRequest(Request $request): self
    {
        $data = $request->validate([
            'name' => 'required',
            'category_id' => 'required',
        ]);

        return new self($data['name'] ?? '', $data['category_id'] ?? Category::UNKNOWN_CATEGORY_ID);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }
}
