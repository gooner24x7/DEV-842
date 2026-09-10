<?php
declare(strict_types=1);

namespace App\Models\Questionnaire;

use App\Models\User;
use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $text
 * @property string $type
 * @property int $score_yes
 * @property int $score_no
 * @property int $works_package_id
 * @property DateTime $deleted_at
 * @method static create(array $array)
 * @method static where($a)
 */
class Questionnaire extends Model
{
    protected $guarded = [];
    protected $appends = [];

    protected $table = 'questionnaires';

    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function worksPackage(): BelongsTo
    {
        return $this->belongsTo(WorksPackage::class, 'works_package_id', 'id');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getWorksPackageId(): int
    {
        return $this->works_package_id;
    }

    public function setText(string $text): void
    {
        $this->text = $text;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function setScoreYes(int $score): void
    {
        $this->score_yes = $score;
    }

    public function setScoreNo(int $score): void
    {
        $this->score_no = $score;
    }

    public function setWorksPackageId(int $worksPackageId): void
    {
        $this->works_package_id = $worksPackageId;
    }

    public function toTemplateArray(): array
    {
        return [
            'text' => $this->text,
            'type' => $this->type,
            'score_yes' => $this->score_yes,
            'score_no' => $this->score_no
        ];
    }
}
