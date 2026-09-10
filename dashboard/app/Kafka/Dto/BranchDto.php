<?php
declare(strict_types=1);

namespace App\Kafka\Dto;

class BranchDto
{
    private int $id;
    private string $email;
    private string $firstName;
    private string $lastName;
    private string $phone;
    private string $postcode;
    private string $country;
    private string $city;
    private string $addrLine1;
    private string $addrLine2;

    /** @var ContactDto[] */
    private array $contacts;

    public function __construct(
        int    $id,
        string $email,
        string $firstName,
        string $lastName,
        string $phone,
        string $postcode,
        string $country,
        string $city,
        string $addrLine1,
        string $addrLine2,
        array  $contacts
    )
    {
        $this->id = $id;
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->phone = $phone;
        $this->postcode = $postcode;
        $this->country = $country;
        $this->city = $city;
        $this->addrLine1 = $addrLine1;
        $this->addrLine2 = $addrLine2;
        $this->contacts = $contacts;
    }

    public static function createFromArray(array $data): self
    {
        $contacts = array_map(static function (array $item) {
            return ContactDto::createFromArray($item);
        }, $data['contacts'] ?? []);

        return new self(
            $data['id'] ?? 0,
            $data['email'] ?? '',
            $data['first_name'] ?? '',
            $data['last_name'] ?? '',
            $data['phone'] ?? '',
            $data['postcode'] ?? '',
            $data['country'] ?? '',
            $data['city'] ?? '',
            $data['address_line_1'] ?? '',
            $data['address_line_2'] ?? '',
            $contacts
        );
    }

    public function getContacts(): array
    {
        return $this->contacts;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
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
        return $this->addrLine1;
    }

    public function getAddressLine2(): string
    {
        return $this->addrLine2;
    }
}
