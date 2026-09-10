<?php
declare(strict_types=1);

namespace App\Dto\Tutorial;

use Illuminate\Http\Request;

class TutorialDto
{
    private string $title;
    private string $embedCode;

    public function __construct(string $title, string $embedCode)
    {
        $this->title = $title;
        $this->embedCode = $embedCode;
    }

    /**
     * @param Request $request
     * @return TutorialDto
     */
    public static function createFromRequest(Request $request): self
    {
        $data = $request->validate([
            'title' => 'required',
            'embedCode' => 'required',
        ]);

        return new self($data['title'] ?? '', $data['embedCode'] ?? '');
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getEmbedCode(): string
    {
        return $this->embedCode;
    }
}
