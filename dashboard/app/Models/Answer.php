<?php
declare(strict_types=1);

namespace App\Models;

use Bnb\Laravel\Attachments\HasAttachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $newMsgQty
 * @property float $esgPerc
 * @property float $price
 * @property float $local_material_spend
 * @property string $type
 * @property string $comment
 * @property int $question_id
 * @property string $offers
 * @property string $supplier_invoice_no
 * @property string $quote_accepted_at
 * @property string $viewed_at
 * @property string $checked_at
 * @property string $creditApplicationFormUrl
 * @property int $user_id
 * @property int $price_score
 * @property int $esg_score
 * @property int $time_score
 * @property int $time_difference
 * @property bool $has_substitution
 * @property string $description
 * @property array $attachments
 * @method static create(array $params)
 * @method static where(array $a)
 * @method static join($a, $b, $c, $d)
 * @method static whereNotNull(array $a)
 * @method static leftJoin($a)
 * @method static select(...$a)
 * @method static selectRaw($a)
 */
class Answer extends Model
{
    use HasAttachment;

    const string NEW_ANSWER_TO_ENQUIRY_INDICATOR_CACHE = 'new_answer_%d_%d';

    public int $newMsgQty;
    public string $creditApplicationFormUrl;
    public int $time_difference;

    protected $guarded = [];

    protected $fillable = [
        'user_id',
        'question_id',
        'comment',
        'price',
        'local_material_spend',
        'viewed_at',
        'offers',
        'supplier_invoice_no',
        'quote_accepted_at',
        'checked_at',
        'type',
        'description',
        'has_substitution',
    ];

    protected $appends = [
        'attachments',
        'esgPerc',
        'creditApplicationFormUrl',
        'price_score',
        'esg_score',
        'time_score',
        'time_difference'
    ];

    /**
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'has_substitution' => 'boolean',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id', 'id');
    }

    public function scopeSelectRadius($query, Question $question)
    {
        $lat = $question->getLat() ?? 0;
        $lon = $question->getLong() ?? 0;

        $haversine = "(0.62137 * (6371 * acos(cos(radians(" . $lat . ")) * cos(radians(`users`.`lat`)) * cos(radians(`users`.`long`) - radians(" . $lon . ")) + sin(radians(" . $lat . ")) * sin(radians(`users`.`lat`)))))";

        return $query->addSelect(DB::raw("coalesce({$haversine}, 0) as distance"))
            ->addSelect(DB::raw('(select max(coalesce(' . $haversine . ', 0)) from answers join users on users.id=answers.user_id and question_id=' .
                $question->getId() . ' and (coalesce(' . $haversine . ', 0)<=50 or users.is_global)) max_distance'));
    }

    public function scopeInRadius($query, float $lat, float $lon, float $radius)
    {
        $haversine = "(0.62137 * (6371 * acos(cos(radians(" . $lat . ")) * cos(radians(`users`.`lat`)) * cos(radians(`users`.`long`) - radians(" . $lon . ")) + sin(radians(" . $lat . ")) * sin(radians(`users`.`lat`)))))";

        return $query->whereRaw("{$haversine} <= ?", [$radius])->addSelect(DB::raw("coalesce({$haversine}, 0) as distance"));
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function getOffers(): string
    {
        return $this->offers;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getLocalMaterialSpend(): float
    {
        return $this->local_material_spend;
    }

    public function getHasSubstitution(): bool
    {
        return $this->has_substitution;
    }

    public function getAttachmentsAttribute(): array
    {
        $attachments = $this->attachments()->get();
        $attachmentsArray = [];

        foreach($attachments as $attachment) {
            $attachmentsArray[] = [
                'id' => $attachment->id,
                'name' => $attachment->filename,
                'url' => $attachment->url,
                'description' => $attachment->description
            ];
        }

        return $attachmentsArray;
    }

    public function getEsgPercAttribute() : float
    {
        $current = ($this->distance ?? 0) * 4206.8;
        $max = ($this->max_distance ?? 0) * 4206.8;

        if ($max == 0) {
            return 0;
        }

        return 100 - ($current * 100 / $max);
    }

    public function getCreditApplicationFormUrlAttribute(): string
    {
        return $this->creditApplicationFormUrl ?? '';
    }

    public function getPriceScoreAttribute(): int
    {
        return $this->price_score ?? 0;
    }

    public function getEsgScoreAttribute(): int
    {
        return $this->esg_score ?? 0;
    }

    public function getTimeScoreAttribute(): int
    {
        return $this->time_score ?? 0;
    }

    public function getTimeDifferenceAttribute(): int
    {
        return $this->time_difference ?? 0;
    }

    public function setTimeDifference(int $time_diff): self
    {
        $this->time_difference = $time_diff;

        return $this;
    }

    public function setCreditApplicationFormUrl(string $url): self
    {
        $this->creditApplicationFormUrl = $url;

        return $this;
    }

    public function setNewMsgQty(int $qty): self
    {
        $this->newMsgQty = $qty;

        return $this;
    }

    public function setSupplierInvoiceNo(string $supplierInvoiceNo): self
    {
        $this->supplier_invoice_no = $supplierInvoiceNo;

        return $this;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function setLocalMaterialSpend(?float $local_material_spend): self
    {
        $this->local_material_spend = $local_material_spend;

        return $this;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setOffers(string $offers): self
    {
        $this->offers = $offers;

        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function setHasSubstitution(bool $has_substitution): self
    {
        $this->has_substitution = $has_substitution;

        return $this;
    }
}
