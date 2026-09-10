<?php
declare(strict_types=1);

namespace App\Repository;

use App\Dto\Questionnaire\ReplyDto;
use App\Models\Questionnaire\Reply;

class QuestionnaireReplyRepository
{
    public function __construct()
    {
    }

    public function get(int $id): ?Reply
    {
        $item = Reply::where(['id' => $id])->first();
        if ($item) {
            return $item;
        }

        return null;
    }

    public function getRepliesBySessionId(int $sessionId): ?Reply
    {
        $item = Reply::where(['session_id' => $sessionId])->first();
        if ($item) {
            return $item;
        }

        return null;
    }

    public function delete(int $itemId): bool
    {
        $item = $this->get($itemId);
        if (!$item) {
            return false;
        }

        return $item->delete();
    }

    public function store(ReplyDto $itemDto): ?Reply
    {
        /** @var Reply $item */
        return Reply::create([
            'text' => $itemDto->getText(),
            'session_id' => $itemDto->getSessionId(),
            'item_id' => $itemDto->getItemId(),
            'score' => $itemDto->getScore(),
            'is_yes' => $itemDto->getIsYes(),
        ]);
    }

    public function update(ReplyDto $itemDto, int $id): ?Reply
    {
        $item = $this->get($id);

        $item->setText($itemDto->getText());
        $item->setIsYes($itemDto->getIsYes());
        $item->setScore($itemDto->getScore());

        if ($item->save()) {
            return $item;
        }

        return null;
    }
}
