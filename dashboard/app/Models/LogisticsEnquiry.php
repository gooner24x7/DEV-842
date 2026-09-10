<?php
declare(strict_types=1);

namespace App\Models;

use App\Models\Questionnaire\Project;
use App\Models\Questionnaire\WorksPackage;
use Bnb\Laravel\Attachments\HasAttachment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $user_id
 * @property int $project_id
 * @property int $works_package_id
 * @property int $type
 * @property int $status
 * @property int $scope
 * @property string $vehicle_type
 * @property string $load_details
 * @property string $comments
 * @property string $notes
 * @property Carbon $collect_date
 * @property Carbon $delivery_date
 * @property Carbon $archived_at
 * @property array $attachments
 * @property Carbon $published_at
 * @method static create(array $array)
 * @method static where(array $array)
 * @method static whereNull(string $a)
 * @method static find(int $questionId)
 * @method static join($a, $b, $c, $d)
 * @method static select(...$a)
 * @method static selectRaw($a)
 */
class LogisticsEnquiry extends Model
{
    use HasAttachment;

    const array TYPES = [
        'Live',
        'Tender'
    ];

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
        'type_str',
        'contacts',
        'status_str',
        'scope_str'
    ];

    // relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(LogisticsQuote::class, 'enquiry_id', 'id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(LogisticsContact::class, 'enquiry_id', 'id');
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class, 'id', 'project_id');
    }

    public function worksPackage(): HasOne
    {
        return $this->hasOne(WorksPackage::class, 'id', 'works_package_id');
    }

    // scopes
    public function scopeInRadius($query, float $lat, float $lon, float $radius, bool $selectDistance = true)
    {
        $haversine1 = "(0.62137 * (6371 * acos(cos(radians(" . $lat . "))
                      * cos(radians((SELECT `lat` FROM logistics_contacts WHERE enquiry_id = logistics_enquiries.id AND type = 0)))
                      * cos(radians((SELECT `long` FROM logistics_contacts WHERE enquiry_id = logistics_enquiries.id AND type = 0))
                      - radians(" . $lon . "))
                      + sin(radians(" . $lat . "))
                      * sin(radians((SELECT `lat` FROM logistics_contacts WHERE enquiry_id = logistics_enquiries.id AND type = 0))))))";

        $haversine2 = "(0.62137 * (6371 * acos(cos(radians(" . $lat . "))
                      * cos(radians((SELECT `lat` FROM logistics_contacts WHERE enquiry_id = logistics_enquiries.id AND type = 1)))
                      * cos(radians((SELECT `long` FROM logistics_contacts WHERE enquiry_id = logistics_enquiries.id AND type = 1))
                      - radians(" . $lon . "))
                      + sin(radians(" . $lat . "))
                      * sin(radians((SELECT `lat` FROM logistics_contacts WHERE enquiry_id = logistics_enquiries.id AND type = 1))))))";

        if ($selectDistance) {
            $query = $query->addSelect(DB::raw("{$haversine1} AS collect_distance"));
            $query = $query->addSelect(DB::raw("{$haversine2} AS delivery_distance"));
        }

        $query->where(function ($query) use ($haversine1, $haversine2, $radius) {
            $query->whereRaw("{$haversine1} <= ?", [$radius]);
            $query->orWhereRaw("{$haversine2} <= ?", [$radius]);
        });

        return $query;
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

    public function getType(): int
    {
        return $this->type;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getScope(): int
    {
        return $this->scope;
    }

    public function getTypeStrAttribute(): string
    {
        return self::TYPES[$this->type] ?? '';
    }

    public function getVehicleType(): string
    {
        return $this->vehicle_type;
    }

    public function getLoadDetails(): string
    {
        return $this->load_details;
    }

    public function getComments(): string
    {
        return $this->comments;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function getCollectDate(): Carbon
    {
        return $this->collect_date;
    }

    public function getDeliveryDate(): Carbon
    {
        return $this->delivery_date;
    }

    public function getArchivedAt(): ?Carbon
    {
        return $this->archived_at;
    }

    public function getPublishedAt(): ?Carbon
    {
        return $this->published_at;
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

    public function getContactsAttribute(): Collection
    {
        return $this->contacts()->get();
    }

    public function getStatusStrAttribute(): string
    {
        return self::STATUSES[$this->status] ?? '';
    }

    public function getScopeStrAttribute(): string
    {
        return self::SCOPES[$this->scope] ?? '';
    }

    public static function getTypeOptions(): array
    {
        return self::TYPES;
    }

    // setters
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

    public function setType(int $type): self
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

    public function setVehicleType(string $vehicle_type): self
    {
        $this->vehicle_type = $vehicle_type;

        return $this;
    }

    public function setLoadDetails(string $load_details): self
    {
        $this->load_details = $load_details;

        return $this;
    }

    public function setComments(string $comments): self
    {
        $this->comments = $comments;

        return $this;
    }

    public function setNotes(string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    public function setCollectDate(?Carbon $collect_date): self
    {
        $this->collect_date = $collect_date;

        return $this;
    }

    public function setDeliveryDate(?Carbon $delivery_date): self
    {
        $this->delivery_date = $delivery_date;

        return $this;
    }

    public function setArchivedAt(?Carbon $dateTime): self
    {
        $this->archived_at = $dateTime;

        return $this;
    }

    public function setPublishedAt(?Carbon $publishedAt): self
    {
        $this->published_at = $publishedAt;

        return $this;
    }
}
