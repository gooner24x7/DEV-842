<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $question_id
 * @property $user_id
 * @property $action
 * @property $comment
 * @method static create(array $params)
 * @method static where(array $a)
 */
class QuestionActionLog extends Model
{
    protected $table = 'questions_action_log';
    protected $fillable = [
        'question_id',
        'user_id',
        'action',
        'comment'
    ];
}
