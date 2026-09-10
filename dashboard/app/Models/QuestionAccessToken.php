<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $question_id
 * @property $token
 * @property $expires_at
 * @property $deleted_at
 * @method static create(array $params)
 * @method static where(array $a)
 */
class QuestionAccessToken extends Model
{
    protected $table = 'questions_access';
    protected $fillable = [
        'question_id',
        'token',
        'expires_at',
        'deleted_at',
    ];

    public function isValid() : bool
    {
        return $this->expires_at <= now() && $this->deleted_at === null;
    }
}
