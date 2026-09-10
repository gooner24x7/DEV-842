<?php
declare(strict_types=1);

namespace App\Models;

use Bnb\Laravel\Attachments\Attachment;
use Bnb\Laravel\Attachments\HasAttachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $company_name
 * @property string $description
 * @property string $date_start
 * @property string $date_end
 * @property array $videos
 * @property boolean $is_active
 * @property string $banner_image
 * @property string $banner_image_left
 * @property string $banner_url
 * @property string $banner_left_url
 * @property array $attachments
 * @property array $attachments_epd
 * @property array $roles
 * @property int $user_id
 * @method static orderBy($a, $b)
 * @method static select(...$a)
 * @method static where($a)
 * @method static create($a)
 * @method static find($a)
 * @property boolean $expo_live
 * @property boolean $product_categories
 * @property boolean $tech_support
 * @property boolean $pim_uploaded
 * @property boolean $epd_info
 */
class VirtualExpo extends Model
{
    use HasAttachment;

    protected $guarded = [];

    public function getId(): int
    {
        return $this->id;
    }

    public function getCompanyName(): string
    {
        return $this->company_name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDateStart(): string
    {
        return $this->date_start;
    }

    public function getDateEnd(): string
    {
        return $this->date_end;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function videos(): HasMany
    {
        return $this->hasMany(VirtualExpoVideo::class, 'virtual_expo_id', 'id');
    }

    public function sharedContacts(): HasMany
    {
        return $this->hasMany(VirtualExpoSharedContact::class, 'virtual_expo_id', 'id');
    }

    public function getBannerUrlAttribute(): string
    {
        return '/' . str_replace('public', 'storage', $this->getBannerImage());
    }

    public function getBannerImage(): string
    {
        return $this->banner_image;
    }

    public function getBannerLeftUrlAttribute(): string
    {
        return '/' . str_replace('public', 'storage', $this->getBannerImageLeft());
    }

    public function getBannerImageLeft(): string
    {
        return $this->banner_image_left;
    }

    public function getAttachmentUrlAttribute($epd = false): ?array
    {
        $attachments = $this->attachments()->get();

        if (!$attachments) {
            return null;
        }

        if ($epd) {
            $attachments = $attachments->filter(function (Attachment $value, int $key) {
                return $value->description === 'epd';
            });
        } else {
            $attachments = $attachments->filter(function (Attachment $value, int $key) {
                return $value->description !== 'epd';
            });
        }

        return $attachments->pluck('url')->toArray();
    }

    public function getAttachmentsAttribute($epd = false): ?array
    {
        $attachments = $this->attachments()->get();

        if (!$attachments) {
            return null;
        }

        if ($epd) {
            $attachments = $attachments->filter(function (Attachment $value, int $key) {
                return $value->description === 'epd';
            });
        } else {
            $attachments = $attachments->filter(function (Attachment $value, int $key) {
                return $value->description !== 'epd';
            });
        }

        return $attachments->values()->toArray();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'description' => $this->description,
            'banner_image' => $this->banner_image,
            'banner_image_left' => $this->banner_image_left,
            'banner_url' => $this->banner_url,
            'banner_left_url' => $this->banner_left_url,
            'date_start' => $this->date_start,
            'date_end' => $this->date_end,
            'is_active' => $this->is_active,
            'user_id' => $this->user_id,
            'roles' => $this->roles()->pluck('role_id'),
            'attachments_urls' => $this->getAttachmentUrlAttribute(),
            'attachments_epd_urls' => $this->getAttachmentUrlAttribute(true),
            'attachments' => $this->getAttachmentsAttribute(),
            'attachments_epd' => $this->getAttachmentsAttribute(true),
            'expo_live' => $this->expo_live,
            'product_categories' => $this->product_categories,
            'tech_support' => $this->tech_support,
            'pim_uploaded' => $this->pim_uploaded,
            'epd_info' => $this->epd_info,
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'virtual_expo_user_roles');
    }

    public function getExpoLive(): bool
    {
        return $this->expo_live;
    }

    public function getProductCategories(): bool
    {
        return $this->product_categories;
    }

    public function getTechSupport(): bool
    {
        return $this->tech_support;
    }

    public function getPimUploaded(): bool
    {
        return $this->pim_uploaded;
    }

    public function getEpdInfo(): bool
    {
        return $this->epd_info;
    }
}
