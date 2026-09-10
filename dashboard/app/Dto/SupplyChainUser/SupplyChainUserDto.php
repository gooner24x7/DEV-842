<?php
declare(strict_types=1);

namespace App\Dto\SupplyChainUser;

use Illuminate\Http\Request;

class SupplyChainUserDto
{
    private ?int $roleId;
    private ?string $firstName;
    private ?string $lastName;
    private ?string $businessName;
    private string $email;
    private ?string $phone;
    private ?string $addressLine1;
    private ?string $addressLine2;
    private ?string $city;
    private ?string $country;
    private ?string $postcode;

    public function __construct(
        ?int $roleId,
        ?string $firstName,
        ?string $lastName,
        ?string $businessName,
        string $email,
        ?string $phone,
        ?string $addressLine1,
        ?string $addressLine2,
        ?string $city,
        ?string $country,
        ?string $postcode,
    ) {
        $this->roleId = $roleId;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->businessName = $businessName;
        $this->email = $email;
        $this->phone = $phone;
        $this->addressLine1 = $addressLine1;
        $this->addressLine2 = $addressLine2;
        $this->city = $city;
        $this->country = $country;
        $this->postcode = $postcode;
    }

    public static function createFromRequest(Request $request): self
    {
        $data = $request->validate([
            'role_id' => '',
            'first_name' => '',
            'last_name' => '',
            'business_name' => '',
            'email' => 'required|email',
            'phone' => '',
            'addr_line_1' => '',
            'addr_line_2' => '',
            'city' => '',
            'country' => '',
            'postcode' => '',
        ]);

        return new self(
            !empty($data['role_id']) ? $data['role_id'] : null,
            !empty($data['first_name']) ? $data['first_name'] : null,
            !empty($data['last_name']) ? $data['last_name'] : null,
            !empty($data['business_name']) ? $data['business_name'] : null,
            $data['email'],
            !empty($data['phone']) ? $data['phone'] : null,
            !empty($data['addr_line_1']) ? $data['addr_line_1'] : null,
            !empty($data['addr_line_2']) ? $data['addr_line_2'] : null,
            !empty($data['city']) ? $data['city'] : null,
            !empty($data['country']) ? $data['country'] : null,
            !empty($data['postcode']) ? $data['postcode'] : null,
        );
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            !empty($data['role_id']) ? $data['role_id'] : null,
            !empty($data['first_name']) ? $data['first_name'] : null,
            !empty($data['last_name']) ? $data['last_name'] : null,
            !empty($data['business_name']) ? $data['business_name'] : null,
            $data['email'],
            !empty($data['phone']) ? $data['phone'] : null,
            !empty($data['addr_line_1']) ? $data['addr_line_1'] : null,
            !empty($data['addr_line_2']) ? $data['addr_line_2'] : null,
            !empty($data['city']) ? $data['city'] : null,
            !empty($data['country']) ? $data['country'] : null,
            !empty($data['postcode']) ? $data['postcode'] : null,
        );
    }

    public function getRoleId(): ?int
    {
        return $this->roleId;
    }

    public function getFirstname(): ?string
    {
        return $this->firstName;
    }

    public function getLastname(): ?string
    {
        return $this->lastName;
    }

    public function getBusinessName(): ?string
    {
        return $this->businessName;
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
        return $this->addressLine1;
    }

    public function getAddressLine2(): ?string
    {
        return $this->addressLine2;
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
}
