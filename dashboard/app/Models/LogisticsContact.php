<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $enquiry_id
 * @property int $type
 * @property string $postcode
 * @property float $lat
 * @property float $long
 * @property string $city
 * @property string $address1
 * @property string $address2
 * @property string $contact_name
 * @property string $contact_phone
 * @method static create(array $array)
 * @method static where(array $array)
 */
class LogisticsContact extends Model
{
    protected $guarded = [];
    protected $appends = [];

    const array TYPES = [
        'collect',
        'delivery',
    ];

    public $timestamps = false;

    // getters
    public function getId(): int
    {
        return $this->id;
    }

    public function getEnquiryId(): int
    {
        return $this->enquiry_id;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getTypeStr(): string
    {
        return self::TYPES[$this->type] ?? '';
    }

    public function getPostcode(): string
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

    public function getCity(): string
    {
        return $this->postcode;
    }

    public function getAddress1(): string
    {
        return $this->address1;
    }

    public function getAddress2(): string
    {
        return $this->address2;
    }

    public function getContactName(): string
    {
        return $this->contact_name;
    }

    public function getContactPhone(): string
    {
        return $this->contact_phone;
    }

    // setters

    public function setEnquiryId(int $enquiry_id): self
    {
        $this->enquiry_id = $enquiry_id;

        return $this;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setPostcode(string $postcode): self
    {
        $this->postcode = $postcode;

        return $this;
    }

    public function setLat(float $lat): self
    {
        $this->lat = $lat;

        return $this;
    }

    public function setLong(float $long): self
    {
        $this->long = $long;

        return $this;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function setAddress1(string $address1): self
    {
        $this->address1 = $address1;

        return $this;
    }

    public function setAddress2(string $address2): self
    {
        $this->address2 = $address2;

        return $this;
    }

    public function setContactName(string $contact_name): self
    {
        $this->contact_name = $contact_name;

        return $this;
    }

    public function setContactPhone(string $contact_phone): self
    {
        $this->contact_phone = $contact_phone;

        return $this;
    }
}
