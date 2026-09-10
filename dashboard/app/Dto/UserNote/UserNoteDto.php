<?php
declare(strict_types=1);

namespace App\Dto\UserNote;

use Illuminate\Http\Request;

class UserNoteDto
{
    private int $userId;
    private int $authorId;
    private string $message;

    public function __construct(string $message, int $userId = 0, int $authorId = 0)
    {
        $this->message = $message;
        $this->userId = $userId;
        $this->authorId = $authorId;
    }

    /**
     * @param Request $request
     * @return self
     */
    public static function createFromRequest(Request $request): self
    {
        $data = $request->validate([
            'message' => 'required'
        ]);

        return new self(
            $data['message'] ?? ''
        );
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getAuthorId(): int
    {
        return $this->authorId;
    }

    public function setAuthorId(int $authorId): self
    {
        $this->authorId = $authorId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }
}
