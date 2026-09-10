<?php
declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $role_id
 * @property string $first_name
 * @property string $last_name
 * @property string $business_name
 * @property string $email
 * @property string $phone
 * @property string $addr_line_1
 * @property string $addr_line_2
 * @property string $city
 * @property string $country
 * @property string $postcode
 * @property float $lat
 * @property float $long
 * @property int $created_by
 * @property Carbon $converted_at
 * @method static whereNotNull(string $a)
 * @method static where(array $array)
 * @method static whereNull(string $a)
 * @method static create(array $params)
 * @method static select(...$select)
 * @method static whereIn($a, array $b)
 * @method static join($a, $b, $c, $d)
 */
class SupplyChainUser extends Model
{
    protected $fillable = [
        'role_id',
        'first_name',
        'last_name',
        'business_name',
        'email',
        'phone',
        'addr_line_1',
        'addr_line_2',
        'city',
        'country',
        'postcode',
        'lat',
        'long',
        'created_by',
        'converted_at',
    ];

    protected $appends = [
        'role_name'
    ];

    public function role(): HasOne
    {
        return $this->hasOne(Role::class, 'id', 'role_id');
    }

    public function getRoleNameAttribute(): string
    {
        return $this->role->name ?? '';
    }

    private function getFullName(): string
    {
        return trim(implode(' ', [$this->first_name, $this->last_name]));
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getRoleId(): int
    {
        return $this->role_id;
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function getBusinessName(): ?string
    {
        return $this->business_name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getAddressLine1(): ?string
    {
        return $this->addr_line_1;
    }

    public function getAddressLine2(): ?string
    {
        return $this->addr_line_2;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getCountry(): ?string
    {
        return $this->country;
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

    public function getCreatedBy(): int
    {
        return $this->created_by;
    }

    public function getConvertedAt(): ?Carbon
    {
        return $this->converted_at;
    }

    public function setRoleId(?int $roleId): SupplyChainUser
    {
        $this->role_id = $roleId;

        return $this;
    }

    public function setFirstName(?string $firstName): SupplyChainUser
    {
        $this->first_name = $firstName;

        return $this;
    }

    public function setLastName(?string $lastName): SupplyChainUser
    {
        $this->last_name = $lastName;

        return $this;
    }

    public function setBusinessName(?string $businessName): SupplyChainUser
    {
        $this->business_name = $businessName;

        return $this;
    }

    public function setEmail(?string $email): SupplyChainUser
    {
        $this->email = $email;

        return $this;
    }

    public function setPhone(?string $phone): SupplyChainUser
    {
        $this->phone = $phone;

        return $this;
    }

    public function setAddressLine1(?string $addressLine1): SupplyChainUser
    {
        $this->addr_line_1 = $addressLine1;

        return $this;
    }

    public function setAddressLine2(?string $addressLine2): SupplyChainUser
    {
        $this->addr_line_2 = $addressLine2;

        return $this;
    }

    public function setCity(?string $city): SupplyChainUser
    {
        $this->city = $city;

        return $this;
    }

    public function setCountry(?string $country): SupplyChainUser
    {
        $this->country = $country;

        return $this;
    }

    public function setPostcode(?string $postcode): SupplyChainUser
    {
        $this->postcode = $postcode;

        return $this;
    }

    public function setLat(?float $lat): SupplyChainUser
    {
        $this->lat = $lat;

        return $this;
    }

    public function setLong(?float $long): SupplyChainUser
    {
        $this->long = $long;

        return $this;
    }

    public function setConvertedAt(?Carbon $convertedAt): self
    {
        $this->converted_at = $convertedAt;

        return $this;
    }
}
