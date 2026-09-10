<?php
declare(strict_types=1);

namespace App\Models\Questionnaire;

use App\Models\Role;
use App\Models\SupplyFitEnquiry;
use App\Models\SupplyFitEnquiryQuote;
use App\Models\User;
use Bnb\Laravel\Attachments\HasAttachment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $user_id
 * @property int $group_id
 * @property int $stage
 * @property int $status
 * @property int $level
 * @property int $type
 * @property string $name
 * @property string $description
 * @property string $tender_notice_id
 * @property string $ocid
 * @property string $postcode
 * @property string $postcode_districts
 * @property string $sector
 * @property string $region
 * @property string $contractor_region
 * @property string $client_name
 * @property string $framework
 * @property string $contractor_name
 * @property string $consultant_name
 * @property string $created_by
 * @property string $boq_standard
 * @property string $frame_type
 * @property string $procurement_route
 * @property float $lat
 * @property float $long
 * @property float $project_value
 * @property float $area_sqm
 * @property float $target_miles_client
 * @property float $target_miles_framework
 * @property float $target_hours_ap
 * @property float $target_hours_se
 * @property float $budget_se
 * @property float $perc_services
 * @property float $perc_prelim
 * @property float $perc_labour
 * @property float $perc_materials
 * @property string $date_start
 * @property string $date_end
 * @property string $date_end_tender
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $archived_at
 * @property Carbon $completed_at
 * @method static orderBy($a, $b)
 * @method static create($a)
 */
class Project extends Model
{
    use HasAttachment;

    protected $fillable = [
        'user_id',
        'group_id',
        'tender_notice_id',
        'ocid',
        'stage',
        'status',
        'level',
        'name',
        'description',
        'postcode',
        'lat',
        'long',
        'postcode_districts',
        'sector',
        'region',
        'contractor_region',
        'client_name',
        'framework',
        'boq_standard',
        'frame_type',
        'procurement_route',
        'project_value',
        'area_sqm',
        'target_miles_client',
        'target_miles_framework',
        'target_hours_ap',
        'target_hours_se',
        'budget_se',
        'perc_services',
        'perc_prelim',
        'perc_labour',
        'perc_materials',
        'date_start',
        'date_end',
        'date_end_tender',
        'archived_at',
        'completed_at',
        'type',
    ];

    protected $appends = [
        'contractor_name',
        'consultant_name',
        'created_by',
        'stage_str',
        'status_str',
        'level_str',
        'total_packages',
        'total_quotes',
        'min_quote_value',
        'awarded_value',
        'cost_per_sqm',
    ];

    protected $casts = [
        'created_at' => 'datetime:d-m-Y H:i',
        'updated_at' => 'datetime:d-m-Y H:i',
        'archived_at' => 'datetime:d-m-Y H:i',
        'completed_at' => 'datetime:d-m-Y H:i',
    ];

    const array STAGES = [
        1 => 'Pre Tender',
        2 => 'Tender',
        3 => 'Live'
    ];

    const array STATUSES = [
        1 => 'published',
        2 => 'draft',
    ];

    const array LEVELS = [
        1 => 'Bronze',
        2 => 'Silver',
        3 => 'Gold'
    ];

    const array TYPES = [
        1 => 'Workspace',
        2 => 'Project'
    ];

    // relations

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function assignedUsers(): HasMany
    {
        return $this->hasMany(ProjectUserAccess::class, 'project_id', 'id');
    }

    public function enquiries(): HasMany
    {
        return $this->hasMany(SupplyFitEnquiry::class, 'project_id', 'id');
    }

    public function worksPackages(): HasMany
    {
        return $this->hasMany(WorksPackage::class, 'project_id', 'id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class, 'project_id', 'id');
    }

    public function timeTracking(): HasMany
    {
        return $this->hasMany(ProjectTimeTracking::class, 'project_id', 'id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ProjectGroup::class, 'group_id', 'id');
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

    public function getGroupId(): ?int
    {
        return $this->group_id;
    }

    public function getStage(): int
    {
        return $this->stage;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getTenderNoticeId(): ?string
    {
        return $this->tender_notice_id;
    }

    public function getOcId(): ?string
    {
        return $this->ocid;
    }

    public function getPostcode(): ?string
    {
        return $this->postcode;
    }

    public function getLat(): ?float
    {
        return $this->lat;
    }

    public function getLong(): ?float
    {
        return $this->long;
    }

    public function getPostcodeDistricts(): ?string
    {
        return $this->postcode_districts;
    }

    public function getSector(): ?string
    {
        return $this->sector;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function getContractorRegion(): ?string
    {
        return $this->contractor_region;
    }

    public function getFramework(): ?string
    {
        return $this->framework;
    }

    public function getClientName(): ?string
    {
        return $this->client_name;
    }

    public function getBoqStandard(): ?string
    {
        return $this->boq_standard;
    }

    public function getFrameType(): ?string
    {
        return $this->frame_type;
    }

    public function getProcurementRoute(): ?string
    {
        return $this->procurement_route;
    }

    public function getProjectValue(): ?float
    {
        return !empty($this->project_value) ? (float)$this->project_value : null;
    }

    public function getAreaSqm(): ?float
    {
        return $this->area_sqm;
    }

    public function getDateStart(): ?string
    {
        return $this->date_start;
    }

    public function getDateEnd(): ?string
    {
        return $this->date_end;
    }

    public function getDateEndTender(): ?string
    {
        return $this->date_end_tender;
    }

    public function getArchivedAt(): ?Carbon
    {
        return $this->archived_at;
    }

    public function getCompletedAt(): ?Carbon
    {
        return $this->completed_at;
    }

    public function getTargetMilesClient(): ?float
    {
        return $this->target_miles_client;
    }

    public function getTargetMilesFramework(): ?float
    {
        return $this->target_miles_framework;
    }

    public function getTargetHoursAp(): ?float
    {
        return $this->target_hours_ap;
    }

    public function getTargetHoursSe(): ?float
    {
        return $this->target_hours_se;
    }

    public function getBudgetSe(): ?float
    {
        return !empty($this->budget_se) ? (float)$this->budget_se : null;
    }

    public function getPercServices(): ?float
    {
        return $this->perc_services;
    }

    public function getPercPrelim(): ?float
    {
        return $this->perc_prelim;
    }

    public function getPercLabour(): ?float
    {
        return $this->perc_labour;
    }

    public function getPercMaterials(): ?float
    {
        return $this->perc_materials;
    }

    public function getContractorNameAttribute(): ?string
    {
        $users = $this->assignedUsers()->get();

        foreach($users as $user) {
            if (in_array(Role::ROLE_CONTRACTOR, $user->roles)) {
                return $user->user->first_name;
            }
        }

        return null;
    }

    public function getConsultantNameAttribute(): ?string
    {
        $users = $this->assignedUsers()->get();

        foreach($users as $user) {
            if (in_array(Role::ROLE_CONSULTANT, $user->roles)) {
                return $user->user->first_name;
            }
        }

        return null;
    }

    public function getCreatedByAttribute(): string
    {
        $user = $this->user()->first();

        return $user?->first_name ?? '';
    }

    public function getStageStrAttribute(): string
    {
        return self::STAGES[$this->stage] ?? '';
    }

    public function getStatusStrAttribute(): string
    {
        return self::STATUSES[$this->status] ?? '';
    }

    public function getLevelStrAttribute(): string
    {
        return self::LEVELS[$this->level] ?? '';
    }

    public function getTotalPackagesAttribute(): int
    {
        return $this->worksPackages()->count();
    }

    public function getTotalQuotesAttribute(): int
    {
        $query = SupplyFitEnquiryQuote::query()
            ->join('supply_fit_enquiries', 'supply_fit_enquiry_quotes.enquiry_id', '=', 'supply_fit_enquiries.id')
            ->where('supply_fit_enquiries.project_id', '=', $this->id);

        return $query->count();
    }

    public function getMinQuoteValueAttribute(): float
    {
        $query = SupplyFitEnquiryQuote::query()
            ->join('supply_fit_enquiries', 'supply_fit_enquiry_quotes.enquiry_id', '=', 'supply_fit_enquiries.id')
            ->where('supply_fit_enquiries.project_id', '=', $this->id);

        return $query->min('price') ?? 0;
    }

    public function getAwardedValueAttribute(): float
    {
        $query = SupplyFitEnquiryQuote::query()
            ->join('supply_fit_enquiries', 'supply_fit_enquiry_quotes.enquiry_id', '=', 'supply_fit_enquiries.id')
            ->where('supply_fit_enquiries.project_id', '=', $this->id)
            ->whereNotNull('supply_fit_enquiry_quotes.quote_accepted_at');

        return $query->sum('price') ?? 0;
    }

    public function getCostPerSqmAttribute(): float
    {
        if (empty($this->awarded_value) || empty($this->area_sqm)) {
            return 0;
        }

        return $this->awarded_value / $this->area_sqm;
    }

    // setters

    public function setArchivedAt(?Carbon $archivedAt): self
    {
        $this->archived_at = $archivedAt;

        return $this;
    }

    public function setCompletedAt(?Carbon $completedAt): self
    {
        $this->completed_at = $completedAt;

        return $this;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

//    public function toArray(): array
//    {
//        return [
//            'id' => $this->id,
//            'name' => $this->name,
//            'client_name' => $this->client_name,
//            'framework' => $this->framework,
//            'contractor_name' => $this->contractor_name,
//            'consultant_name' => $this->consultant_name,
//            'postcode' => $this->postcode,
//            'target_miles_client' => $this->target_miles_client,
//            'target_miles_framework' => $this->target_miles_framework,
//            'target_hours_ap' => $this->target_hours_ap,
//            'target_hours_se' => $this->target_hours_se,
//            'budget_se' => $this->budget_se,
//            'date_start' => $this->date_start,
//            'date_end' => $this->date_end,
//            'created_at' => $this->created_at,
//            'updated_at' => $this->updated_at
//        ];
//    }
}
