<?php
declare(strict_types=1);

namespace App\Models;

use Bnb\Laravel\Attachments\HasAttachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $user_id
 * @property int $enquiry_id
 * @property int $newMsgQty
 * @property float $price
 * @property float|null $local_material_spend
 * @property string $type
 * @property string $comment
 * @property string $offers
 * @property string $description
 * @property string $supplier_invoice_no
 * @property string $quote_accepted_at
 * @property string $creditApplicationFormUrl
 * @property int $questionnaireTotalScore
 * @property int|null $total_score
 * @property bool $resources_available
 * @property bool $is_competent
 * @property array $attachments
 * @method static where($a)
 * @method static create($a)
 */
class SupplyFitEnquiryQuote extends Model
{
    use HasAttachment;

    const string NEW_QUOTE_TO_ENQUIRY_INDICATOR_CACHE = 'new_supply_fit_enquiry_quote_%d_%d';

    protected $table = 'supply_fit_enquiry_quotes';

    protected $guarded = [];

    protected $appends = [
        'attachments',
        'esgPerc',
    ];
    /**
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'resources_available' => 'boolean',
        'is_competent' => 'boolean',
    ];

    public function enquiries(): BelongsTo
    {
        return $this->belongsTo(SupplyFitEnquiry::class, 'enquiry_id', 'id');
    }

    public function scopeInRadius($query, float $lat, float $lon, float $radius)
    {
        $haversine = "(0.62137 * (6371 * acos(cos(radians(" . $lat . "))
                      * cos(radians(`users`.`lat`))
                      * cos(radians(`users`.`long`)
                      - radians(" . $lon . "))
                      + sin(radians(" . $lat . "))
                      * sin(radians(`users`.`lat`)))))";

        return $query->selectRaw("{$haversine} AS distance")
            ->whereRaw("{$haversine} <= ?", [$radius]);
    }

    public function scopeSelectRadius($query, SupplyFitEnquiry $enquiry)
    {
        $lat = $enquiry->getLat() ?? 0;
        $lon = $enquiry->getLong() ?? 0;

        $haversine = "(0.62137 * (6371 * acos(cos(radians(" . $lat . ")) * cos(radians(`users`.`lat`)) * cos(radians(`users`.`long`) - radians(" . $lon . ")) + sin(radians(" . $lat . ")) * sin(radians(`users`.`lat`)))))";

        return $query->addSelect(DB::raw("coalesce({$haversine}, 0) as distance"))
            ->addSelect(DB::raw('(select max(coalesce(' . $haversine . ', 0)) from supply_fit_enquiry_quotes join users on users.id=supply_fit_enquiry_quotes.user_id and enquiry_id=' .
                $enquiry->getId() . ' and (coalesce(' . $haversine . ', 0)<=50 or users.is_global)) max_distance'));
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getLocalMaterialSpend(): float
    {
        return $this->local_material_spend;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function getOffers(): string
    {
        return $this->offers;
    }

    public function getSupplierInvoiceNo(): string
    {
        return $this->supplier_invoice_no;
    }

    public function getQuoteAcceptedAt(): string
    {
        return $this->quote_accepted_at;
    }

    public function getResourcesAvailable(): bool
    {
        return $this->resources_available;
    }

    public function getIsCompetent(): bool
    {
        return $this->is_competent;
    }

    public function getEsgPercAttribute(): float
    {
        $current = ($this->distance ?? 0) * 4206.8;
        $max = ($this->max_distance ?? 0) * 4206.8;

        if ($max == 0) {
            return 0;
        }

        return 100 - ($current * 100 / $max);
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

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setCreditApplicationFormUrl(string $url): self
    {
        $this->creditApplicationFormUrl = $url;

        return $this;
    }

    public function setQuestionnaireTotalScore(int $questionnaireTotalScore): self
    {
        $this->questionnaireTotalScore = $questionnaireTotalScore;

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

    public function setLocalMaterialSpend(?float $localMaterialSpend): self
    {
        $this->local_material_spend = $localMaterialSpend;

        return $this;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

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

    public function setResourcesAvailable(bool $resourcesAvailable): self
    {
        $this->resources_available = $resourcesAvailable;

        return $this;
    }

    public function setIsCompetent(bool $isCompetent): self
    {
        $this->is_competent = $isCompetent;

        return $this;
    }
}
