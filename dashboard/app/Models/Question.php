<?php
declare(strict_types=1);

namespace App\Models;

use App\Models\Questionnaire\Project;
use App\Models\Questionnaire\WorksPackage;
use Bnb\Laravel\Attachments\HasAttachment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $project_id
 * @property int $works_package_id
 * @property int $newQuotesQty
 * @property int $seenUnquotedQty
 * @property int $newMsgQty
 * @property Carbon $archived_at
 * @property array $answers
 * @property string $postcode
 * @property int $product_id
 * @property string $days
 * @property string $comment
 * @property int $user_id
 * @property string $type
 * @property int $ref_archived_enquiry_id
 * @property float $lat
 * @property float $long
 * @property Product $product
 * @property array $attachments
 * @property int $status
 * @property int $scope
 * @property Carbon $published_at
 * @method static create(array $array)
 * @method static where(array $array)
 * @method static whereNull(string $a)
 * @method static find(int $questionId)
 * @method static join($a, $b, $c, $d)
 * @method static select(...$a)
 * @method static selectRaw($a)
 */
class Question extends Model
{
    use HasAttachment;

    const string NEW_ENQUIRIES_INDICATOR_CACHE = 'new_enquiries_%d';

    const array STATUSES = [
        'published',
        'draft',
    ];

    const array SCOPES = [
        'open',
        'closed',
    ];

    protected $guarded = [];

    protected $appends = [
        'attachments',
        'total_quotes',
        'manufacturer_product_selected',
        'contractor_name',
        'reference_id',
        'status_str',
        'scope_str',
    ];

    // relations

    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'questions_assigned_users');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class, 'question_id', 'id');
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

    public function nationals(): HasMany
    {
        return $this->hasMany(National::class, 'question_id', 'id');
    }

    public function manufacturerProducts(): BelongsToMany
    {
        return $this->belongsToMany(
            ManufacturerImportedProduct::class,
            'inquiries_manufacturer_products',
            'inquiry_id',
            'manufacturer_product_id'
        );
    }

    // scopes

    public function scopeInRadius($query, float $lat, float $lon, float $radius, bool $selectDistance = true)
    {
        $haversine = "(0.62137 * (6371 * acos(cos(radians(" . $lat . "))
                      * cos(radians(`questions`.`lat`))
                      * cos(radians(`questions`.`long`)
                      - radians(" . $lon . "))
                      + sin(radians(" . $lat . "))
                      * sin(radians(`questions`.`lat`)))))";

        if ($selectDistance) {
            $query = $query->addSelect(DB::raw("{$haversine} AS distance"));
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

    public function getProjectId(): int
    {
        return $this->project_id;
    }

    public function getWorksPackageId(): int
    {
        return $this->works_package_id;
    }

    public function getAnswers(): array
    {
        return $this->answers;
    }

    public function getContractorName(): string
    {
        /** @var User $user */
        $user = $this->users()->first();
        if (!$user) {
            return '';
        }

        return $user->getFirstName();
    }

    public function getCategory(): string
    {
        return $this->product->name;
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

    public function getPublishedAt(): ?Carbon
    {
        return $this->published_at;
    }

    public function getManufacturerProductSelectedAttribute(): array
    {
        $items = $this->manufacturerProducts()->select([
            'inquiries_manufacturer_products.manufacturer_product_id',
            'inquiries_manufacturer_products.qty'
        ])->get();

        $result = [];
        foreach ($items as $item) {
            $result[$item->manufacturer_product_id] = [
                'qty' => $item->qty,
            ];
        }

        return $result;
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

    public function getContractorNameAttribute(): string
    {
        return $this->getContractorName();
    }

    public function getTotalQuotesAttribute(): int
    {
        return $this->answers()->count();
    }

    public function getReferenceIdAttribute(): ?string
    {
        $day = !empty($this->created_at) ? $this->created_at->format('d') : '00';
        return $this->user_id . $this->id . $day;
    }

    public function getStatusStrAttribute(): string
    {
        return self::STATUSES[$this->status] ?? '';
    }

    public function getScopeStrAttribute(): string
    {
        return self::SCOPES[$this->scope] ?? '';
    }

    // setters

    public function setNewMsgQty(int $newMsgQty): self
    {
        $this->newMsgQty = $newMsgQty;

        return $this;
    }

    public function setSeenUnquotedQty(int $seenUnquotedQty): self
    {
        $this->seenUnquotedQty = $seenUnquotedQty;

        return $this;
    }

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

    public function setProductId(int $productId): self
    {
        $this->product_id = $productId;

        return $this;
    }

    public function setProjectId(int $projectId): self
    {
        $this->project_id = $projectId;

        return $this;
    }

    public function setWorksPackageId(int $worksPackageId): self
    {
        $this->works_package_id = $worksPackageId;

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

    public function setType(string $type): self
    {
        $this->type = $type;

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
