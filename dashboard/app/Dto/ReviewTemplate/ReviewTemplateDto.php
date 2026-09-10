<?php
declare(strict_types=1);

namespace App\Dto\ReviewTemplate;

use Illuminate\Http\Request;

class ReviewTemplateDto
{
    private string $name;
    private ?string $description;
    private array $sections;

    public function __construct(
        string $name,
        ?string $description,
        array $sections
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->sections = $sections;
    }

    public static function createFromRequest(Request $request): self
    {
        $data = $request->validate([
            'name' => 'required|string',
            'description' => '',
            'sections' => '',
        ]);

        return self::createFromArray($data);
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            $data['name'] ?? '',
            $data['description'] ?? '',
            $data['sections'] ?? [],
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getSections(): array
    {
        return $this->sections;
    }
}
