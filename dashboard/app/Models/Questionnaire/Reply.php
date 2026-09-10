<?php
declare(strict_types=1);

namespace App\Models\Questionnaire;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $text
 * @property int $item_id
 * @property int $session_id
 * @property boolean $is_yes
 * @property int $score
 * @method static create(array $array)
 * @method static where($a)
 * @method static select(...$a)
 */
class Reply extends Model
{
    protected $table = 'questionnaire_replies';

    protected $guarded = [];
    protected $appends = [];

    public function getId(): int
    {
        return $this->id;
    }

    public function setItemId(int $itemId): void
    {
        $this->item_id = $itemId;
    }

    public function setSessionId(int $sessionId): void
    {
        $this->session_id = $sessionId;
    }

    public function setText(string $text): void
    {
        $this->text = $text;
    }

    public function setIsYes(bool $isYes): void
    {
        $this->is_yes = $isYes;
    }

    public function setScore(int $score): void
    {
        $this->score = $score;
    }
}
