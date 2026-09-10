<?php
declare(strict_types=1);

namespace App\Dto\ActionLog;

use Illuminate\Http\Request;

class ActionLogDto
{
    const string ACTION_LOGIN = 'action_login';

    private string $action;
    private string $page;
    private string $comment;
    private int $userId;

    public function __construct(string $action, string $page, string $comment, int $user_id)
    {
        $this->action = $action;
        $this->page = $page;
        $this->comment = $comment;
        $this->userId = $user_id;
    }

    /**
     * @param Request $request
     * @return ActionLogDto
     */
    public static function createFromRequest(Request $request): self
    {
        $data = $request->validate([
            'action' => 'required',
            'page' => '',
            'comment' => '',
            'user_id' => 'required',
        ]);

        return new self(
            $data['action'] ?? '',
            $data['page'] ?? '',
            $data['comment'] ?? '',
            $data['user_id'] ?? 0
        );
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getPage(): string
    {
        return $this->page;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
