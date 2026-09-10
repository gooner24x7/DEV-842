<?php
declare(strict_types=1);

namespace App\Dto\Note;

use Illuminate\Http\Request;

class NoteDto
{
    private int $parentId;
    private int $type;
    private string $message;

    public function __construct(int $parentId, int $type, string $message)
    {
        $this->parentId = $parentId;
        $this->type = $type;
        $this->message = $message;
    }

    /**
     * @param Request $request
     * @return self
     */
    public static function createFromRequest(Request $request): self
    {
        $data = $request->validate([
            'parentId'  => 'required|int',
            'type'      => 'required|int',
            'message'   => 'required'
        ]);

        return new self(
            (int) $data['parentId'],
            (int) $data['type'],
            $data['message'] ?? ''
        );
    }

    public function getParentId(): int
    {
        return $this->parentId;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setParentId(int $parentId): self
    {
        $this->parentId = $parentId;

        return $this;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }
}
