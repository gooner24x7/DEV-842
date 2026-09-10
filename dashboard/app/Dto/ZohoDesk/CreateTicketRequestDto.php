<?php

namespace App\Dto\ZohoDesk;

use Illuminate\Http\Request;

class CreateTicketRequestDto
{
    private string $subject;
    private string $description;
    private string $departmentId;
    private string $email;
    private string $firstName;
    private string $category;

    public function __construct(
        string $subject,
        string $description,
        string $departmentId,
        string $email,
        string $firstName,
        string $category
    )
    {
        $this->subject = $subject;
        $this->description = $description;
        $this->departmentId = $departmentId;
        $this->email = $email;
        $this->firstName = $firstName;
        $this->category = $category;
    }

    public static function createFromRequest(Request $request): self
    {
        return new self(
            $request->get('subject') ?? '',
            $request->get('description') ?? '',
            $request->get('departmentId') ?? config('zoho-desk.department_id'),
            $request->get('email') ?? '',
            $request->get('firstName') ?? '',
            $request->get('category') ?? ''
        );
    }

    public function toArray(): array
    {
        return [
            'subject' => $this->getSubject(),
            'description' => $this->getDescription(),
            'departmentId' => $this->getDepartmentId(),
            'email' => $this->getEmail(),
            'category' => $this->getCategory(),
            'contact' => [
                'email' => $this->getEmail(),
                'firstName' => $this->getFirstName(),
            ],
        ];
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDepartmentId(): string
    {
        return $this->departmentId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }
}
