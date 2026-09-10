<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property Carbon $processed_at
 * @method static create(array $array)
 * @method static where(array $array)
 */
class SupplyFitEnquiryBatch extends Model
{
    protected $fillable = [
        'user_id',
        'processed_at',
    ];

    public $timestamps = false;

    public function enquiries(): HasMany
    {
        return $this->hasMany(SupplyFitEnquiry::class, 'batch_id', 'id');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getProcessedAt(): Carbon
    {
        return $this->processed_at;
    }

    public function setProcessedAt(?Carbon $processedAt): self
    {
        $this->processed_at = $processedAt;

        return $this;
    }
}
