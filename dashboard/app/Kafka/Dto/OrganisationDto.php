<?php
declare(strict_types=1);

namespace App\Kafka\Dto;

class OrganisationDto
{
    private int $id;
    private string $email;
    private string $name;
    private string $phone;
    private string $postcode;
    private string $country;
    private string $city;
    private string $addressLine1;
    private string $addressLine2;
    private string $type;
    private bool $active;
    private array $branches = [];

    public function __construct(
        int    $id,
        string $email,
        string $name,
        string $phone,
        string $postcode,
        string $country,
        string $city,
        string $addressLine1,
        string $addressLine2,
        string $type,
        bool   $active
    )
    {
        $this->id = $id;
        $this->email = $email;
        $this->name = $name;
        $this->phone = $phone;
        $this->postcode = $postcode;
        $this->country = $country;
        $this->city = $city;
        $this->addressLine1 = $addressLine1;
        $this->addressLine2 = $addressLine2;
        $this->type = $type;
        $this->active = $active;
    }

    public static function createFromArray(array $data): self
    {
        $branches = array_map(static function (array $item) {
            return BranchDto::createFromArray($item);
        }, $data['branches'] ?? []);

        return (new self(
            $data['id'] ?? 0,
            $data['email'] ?? '',
            $data['name'] ?? '',
            $data['phone'] ?? '',
            $data['postcode'] ?? '',
            $data['country'] ?? '',
            $data['city'] ?? '',
            $data['address_line_1'] ?? '',
            $data['address_line_2'] ?? '',
            $data['type'] ?? '',
            $data['active'] ?? true
        ))->setBranches($branches);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getPostcode(): string
    {
        return $this->postcode;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getAddressLine1(): string
    {
        return $this->addressLine1;
    }

    public function getAddressLine2(): string
    {
        return $this->addressLine2;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    /** @return BranchDto[] */
    public function getBranches(): array
    {
        return $this->branches;
    }

    /**
     * @return OrganisationDto
     * @var array $branches
     */
    public function setBranches(array $branches): self
    {
        $this->branches = $branches;

        return $this;
    }
}
