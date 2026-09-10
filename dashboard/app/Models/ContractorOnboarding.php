<?php
declare(strict_types=1);

namespace App\Models;

use Bnb\Laravel\Attachments\HasAttachment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $company_name
 * @property string $company_address1
 * @property string $company_address2
 * @property string $company_city
 * @property string $company_postcode
 * @property string $company_registration_number
 * @property string $company_vat_number
 * @property boolean $developer
 * @property boolean $framework
 * @property boolean $main_contractor
 * @property boolean $sub_contractor
 * @property boolean $merchant
 * @property boolean $manufacturer
 * @property boolean $cas_approved
 * @property boolean $cas_certifying_body
 * @property boolean $goods_supply
 * @property Carbon $cas_issue_date
 * @property Carbon $cas_expiry_date
 * @property array $attachments
 * @method static find(int $id)
 * @method static where(array $array)
 * @method static create(array $array)
 */

class ContractorOnboarding extends Model
{
    use HasAttachment;

    const array CAS_CERTIFYING_BODIES = [
        'CHAS',
        'ConstructionLine',
        'SMAS',
        'CQMS',
        'Compliance Chain',
        'Achilles',
        'SCCS'
    ];

    protected $table = 'contractor_onboarding';

    protected $fillable = [
        'user_id',
        'company_name',
        'company_address1',
        'company_address2',
        'company_city',
        'company_postcode',
        'company_registration_number',
        'company_vat_number',
        'developer',
        'framework',
        'main_contractor',
        'sub_contractor',
        'merchant',
        'manufacturer',
        'cas_approved',
        'cas_certifying_body',
        'goods_supply',
        'cas_issue_date',
        'cas_expiry_date',
    ];

    protected $casts = [
        'developer' => 'boolean',
        'framework' => 'boolean',
        'main_contractor' => 'boolean',
        'sub_contractor' => 'boolean',
        'merchant' => 'boolean',
        'manufacturer' => 'boolean',
        'cas_approved' => 'boolean',
        'goods_supply' => 'boolean'
    ];

    protected $appends = [
        'attachments',
        'cas_certifying_body_str',
        'is_sme'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
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

    public function getCasCertifyingBodyStrAttribute(): string
    {
        if (empty(self::CAS_CERTIFYING_BODIES[$this->cas_certifying_body])) {
            return '';
        }

        return self::CAS_CERTIFYING_BODIES[$this->cas_certifying_body];
    }

    public function getIsSmeAttribute(): bool
    {
        return $this->user->is_sme;
    }
}
