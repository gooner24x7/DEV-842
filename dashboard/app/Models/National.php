<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $question_id
 * @property int $supplier_id
 * @property string $email
 * @property string $contact_name
 * @property string $account_number
 * @method static create(array $array)
 * @method static where(array $array)
 * @method static whereNull(string $a)
 * @method static find(int $questionId)
 * @method static join($a, $b, $c, $d)
 * @method static select(...$a)
 * @method static selectRaw($a)
 */
class National extends Model
{
    protected $guarded = [];

    protected $appends = [
        'supplier_name'
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getQuestionId(): int
    {
        return $this->question_id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getContactName(): string
    {
        return $this->contact_name;
    }

    public function getAccountNumber(): string
    {
        return $this->account_number;
    }

    public function getSupplierNameAttribute(): string
    {
        return DB::table('national_suppliers')->where('id', $this->supplier_id)->first()->name ?? '';
    }

    public function setQuestionId(int $questionId): self
    {
        $this->question_id = $questionId;

        return $this;
    }

    public function setSupplierId(int $supplierId): self
    {
        $this->supplier_id = $supplierId;

        return $this;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function setContactName(string $contactName): self
    {
        $this->contact_name = $contactName;

        return $this;
    }

    public function setAccountNumber(string $accountNumber): self
    {
        $this->account_number = $accountNumber;

        return $this;
    }
}
