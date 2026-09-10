<?php
declare(strict_types=1);

namespace App\Models;

use App\Models\Questionnaire\Project;
use App\Models\Questionnaire\WorksPackage;
use Bnb\Laravel\Attachments\HasAttachment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $user_id
 * @property int $batch_id
 * @property int $project_id
 * @property int $works_package_id
 * @property int $product_id
 * @property int $newQuotesQty
 * @property array $quotes
 * @property string $postcode
 * @property string $days
 * @property string $comment
 * @property int $newMsgQty
 * @property int $lat
 * @property int $long
 * @property int $ref_archived_enquiry_id
 * @property string $type
 * @property string $actual_starting_date
 * @property string $assumed_end_date
 * @property string $actual_end_date
 * @property array $attachments
 * @property int $status
 * @property int $scope
 * @property Carbon $published_at
 * @property Carbon $archived_at
 * @method static create(array $array)
 * @method static where(array $array)
 * @method static join(string $a, string $b, string $c, string $d)
 * @method static whereNull(string $a)
 * @method static whereNotNull(string $a)
 * @method static select(...$a)
 */
class SupplyFitEnquiry extends Model
{
    use HasAttachment;

    protected $fillable = [
        'user_id',
        'batch_id',
        'project_id',
        'works_package_id',
        'product_id',
        'comment',
        'postcode',
        'days',
        'actual_starting_date',
        'assumed_end_date',
        'actual_end_date',
        'type',
        'status',
        'scope',
        'published_at',
        'archived_at'
    ];

    const string NEW_ENQUIRIES_INDICATOR_CACHE = 'new_supply_fit_enquiries_%d';

    const array STATUSES = [
        1 => 'published',
        2 => 'draft',
    ];

    const array SCOPES = [
        1 => 'open',
        2 => 'closed',
        3 => 'selected users'
    ];

    protected $table = 'supply_fit_enquiries';
    protected $guarded = [];

    protected $appends = [
        'attachments',
        'total_quotes',
        'status_str',
        'scope_str',
        'product_name',
        'works_package_name'
    ];

    // relations

    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(SupplyFitEnquiryQuote::class, 'enquiry_id', 'id');
    }

    public function product(): HasOne
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class, 'id', 'project_id');
    }

    public function worksPackage(): HasOne
    {
        return $this->hasOne(WorksPackage::class, 'id', 'works_package_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(SupplyFitEnquiryBatch::class, 'batch_id', 'id');
    }

    // scopes

    public function scopeInRadius($query, float $lat, float $lon, float $radius, bool $selectDistance = true)
    {
        $haversine = "(0.62137 * (6371 * acos(cos(radians(" . $lat . "))
                      * cos(radians(`supply_fit_enquiries`.`lat`))
                      * cos(radians(`supply_fit_enquiries`.`long`)
                      - radians(" . $lon . "))
                      + sin(radians(" . $lat . "))
                      * sin(radians(`supply_fit_enquiries`.`lat`)))))";

        if ($selectDistance) {
            $query = $query->selectRaw("{$haversine} AS distance");
        }

        return $query->whereRaw("{$haversine} <= ?", [$radius]);
    }

    // getters

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getProductId(): ?int
    {
        return $this->product_id;
    }

    public function getProjectId(): ?int
    {
        return $this->project_id;
    }

    public function getWorksPackageId(): ?int
    {
        return $this->works_package_id;
    }

    public function getQuotes(): array
    {
        return $this->quotes;
    }

    public function getLat(): ?float
    {
        return $this->lat;
    }

    public function getLong(): ?float
    {
        return $this->long;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getScope(): int
    {
        return $this->scope;
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

    public function getTotalQuotesAttribute(): int
    {
        return $this->quotes()->count();
    }

    public function getDays(): string
    {
        return $this->days;
    }

    public function getActualStartingDate(): ?string
    {
        return $this->actual_starting_date;
    }

    public function getAssumedEndDate(): ?string
    {
        return $this->assumed_end_date;
    }

    public function getActualEndDate(): ?string
    {
        return $this->actual_end_date;
    }

    public function getPublishedAt(): ?Carbon
    {
        return $this->published_at;
    }

    public function getArchivedAt(): ?Carbon
    {
        return $this->archived_at;
    }

    public function getStatusStrAttribute(): string
    {
        return self::STATUSES[$this->status] ?? '';
    }

    public function getScopeStrAttribute(): string
    {
        return self::SCOPES[$this->scope] ?? '';
    }

    public function getProductNameAttribute(): string
    {
        return $this->product->name ?? '';
    }

    public function getWorksPackageNameAttribute(): string
    {
        return $this->worksPackage->name ?? '';
    }

    // setters

    public function setNewQuotesQty(int $newQuotesQty): self
    {
        $this->newQuotesQty = $newQuotesQty;

        return $this;
    }

    public function setArchivedAt(?Carbon $dateTime): self
    {
        $this->archived_at = $dateTime;

        return $this;
    }

    public function setPostcode(string $postcode): self
    {
        $this->postcode = $postcode;

        return $this;
    }

    public function setProductId(?int $productId): self
    {
        $this->product_id = $productId;

        return $this;
    }

    public function setDays(string $days): self
    {
        $this->days = $days;

        return $this;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function setProjectId(?int $projectId): self
    {
        $this->project_id = $projectId;

        return $this;
    }

    public function setWorksPackageId(?int $worksPackageId): self
    {
        $this->works_package_id = $worksPackageId;

        return $this;
    }

    public function setNewMsgQty(int $newMsgQty): self
    {
        $this->newMsgQty = $newMsgQty;

        return $this;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setActualStartingDate(?string $actualStartingDate): self
    {
        $this->actual_starting_date = $actualStartingDate;

        return $this;
    }

    public function setAssumedEndDate(?string $assumedEndDate): self
    {
        $this->assumed_end_date = $assumedEndDate;

        return $this;
    }

    public function setActualEndDate(?string $actualEndDate): self
    {
        $this->actual_end_date = $actualEndDate;

        return $this;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function setScope(int $scope): self
    {
        $this->scope = $scope;

        return $this;
    }

    public function setPublishedAt(?Carbon $publishedAt): self
    {
        $this->published_at = $publishedAt;

        return $this;
    }
}
