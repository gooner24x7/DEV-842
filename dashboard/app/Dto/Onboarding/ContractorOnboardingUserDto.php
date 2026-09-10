<?php
declare(strict_types=1);

namespace App\Dto\Onboarding;

use Illuminate\Http\Request;

class ContractorOnboardingUserDto
{
    private int $billing_user_id;
    private string $first_name;
    private string $last_name;
    private string $job_title;
    private string $email;
    private string $phone;
    private string $alias;

    private bool $can_check_competency;
    private array $tasks;

    public function __construct(
        int $billing_user_id,
        string $first_name,
        string $last_name,
        string $job_title,
        string $email,
        string $phone,
        string $alias,
        bool $can_check_competency,
        array $tasks
    ) {
        $this->billing_user_id = $billing_user_id;
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->job_title = $job_title;
        $this->email = $email;
        $this->phone = $phone;
        $this->alias = $alias;
        $this->can_check_competency = $can_check_competency;
        $this->tasks = $tasks;
    }

    public static function createFromRequest(Request $request): self
    {
        $data = $request->validate([
            'billing_user_id' => 'required|integer',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'job_title' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'alias' => '',
            'can_check_competency' => '',
            'tasks' => ''
        ]);

        return self::createFromArray($data);
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            $data['billing_user_id'],
            $data['first_name'],
            $data['last_name'],
            $data['job_title'],
            $data['email'],
            $data['phone'],
            $data['alias'] ?? '',
            isset($data['can_check_competency']) && (bool)$data['can_check_competency'],
            $data['tasks'] ?? []
        );
    }

    public function getBillingUserId(): int
    {
        return $this->billing_user_id;
    }

    public function getFirstName(): string
    {
        return $this->first_name;
    }

    public function getLastName(): string
    {
        return $this->last_name;
    }

    public function getJobTitle(): string
    {
        return $this->job_title;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getCanCheckCompetency(): bool
    {
        return $this->can_check_competency;
    }

    public function getTasks(): array
    {
        return $this->tasks;
    }
}
