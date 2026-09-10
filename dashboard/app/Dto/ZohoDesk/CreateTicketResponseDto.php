<?php

namespace App\Dto\ZohoDesk;

class CreateTicketResponseDto implements \JsonSerializable
{
    private string $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public static function createFromArray(array $data): self
    {
        return new self($data['id'] ?? '');
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
        ];
    }
}
