<?php
declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $type
 * @property string $title
 * @property string $organisation
 * @property string $notice_type
 * @property string $notice_id
 * @property string $procurement_id
 * @property string $link
 * @property Carbon $date_published
 * @property Carbon $date_closing
 * @property string $content
 * @method static create($a)
 * @method static select(...$a)
 * @method static selectRaw($a)
 * @method static where(array $array)
 * @method static whereNull(string $a)
 */
class TenderNotice extends Model
{
    protected $guarded = [];
    protected $appends = [];

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getOrganisation(): string
    {
        return $this->organisation;
    }

    public function getNoticeType(): string
    {
        return $this->notice_type;
    }

    public function getNoticeId(): string
    {
        return $this->notice_id;
    }

    public function getProcurementId(): string
    {
        return $this->procurement_id;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getDatePublished(): Carbon
    {
        return $this->date_published;
    }

    public function getDateClosing(): Carbon
    {
        return $this->date_closing;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
