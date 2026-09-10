<?php
declare(strict_types=1);

namespace App\Dto\User;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class UserDto
{
    private string $firstName;
    private string $lastName;
    private string $username;
    private string $email;
    private string $password;
    /** @var int[] */
    private array $roleIds;
    private string $postcode;
    private ?string $phone;
    private ?string $country;
    private ?string $city;
    private ?string $addressLine1;
    private ?string $addressLine2;
    /** @var int[]|null */
    private ?array $productIds;
    private ?int $billingUserId;
    /** @var int[]|null */
    private ?array $userPlans;
    private ?bool $isGlobal;
    private ?string $managerType;
    private ?int $branchId;
    private ?bool $can_assign_to_enquiries;
    private ?bool $can_check_competency;
    private ?string $trialEnds;
    private ?UploadedFile $creditApplicationForm;
    private ?bool $isGoldAccount;
    private ?bool $isTestAccount;
    private ?bool $isSme;
    private string $companyNumber;
    private ?string $pipelineOfWork;
    private ?int $potentialUsers;
    private ?int $turnover;
    private ?int $numberOfEmployees;
    private ?string $onboardingCall;
    private ?string $nextCall;
    private ?string $painPoints;
    private ?string $activityTrackerMapping;
    private ?string $jobTitle;
    private ?string $alias;
    private ?string $companyType;

    public function __construct(
        string        $firstName,
        string        $lastName,
        string        $username,
        string        $email,
        string        $password,
        array         $roleIds,
        string        $postcode,
        ?string       $phone,
        ?string       $country,
        ?string       $city,
        ?string       $addressLine1,
        ?string       $addressLine2,
        ?array        $productIds,
        ?int          $billingUserId,
        ?array        $userPlans,
        ?bool         $isGlobal,
        ?bool         $isGoldAccount = false,
        ?bool         $isTestAccount = false,
        ?bool         $isSme = false,
        ?string       $managerType = null,
        ?int          $branchId = null,
        ?bool         $can_assign_to_enquiries = null,
        ?bool         $can_check_competency = null,
        ?string       $trialEnds = null,
        ?UploadedFile $creditApplicationForm = null,
        string        $companyNumber = '',
        ?string       $pipelineOfWork = null,
        ?int          $potentialUsers = null,
        ?int          $turnover = null,
        ?int          $numberOfEmployees = null,
        ?string       $onboardingCall = null,
        ?string       $nextCall = null,
        ?string       $painPoints = null,
        ?string       $activityTrackerMapping = null,
        ?string       $jobTitle = null,
        ?string       $alias = null,
        ?string       $companyType = null
    ) {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->username = $username;
        $this->email = $email;
        $this->password = $password;
        $this->roleIds = $roleIds;
        $this->postcode = $postcode;
        $this->phone = $phone;
        $this->country = $country;
        $this->city = $city;
        $this->addressLine1 = $addressLine1;
        $this->addressLine2 = $addressLine2;
        $this->productIds = $productIds;
        $this->billingUserId = $billingUserId;
        $this->userPlans = $userPlans;
        $this->isGlobal = $isGlobal;
        $this->isGoldAccount = $isGoldAccount;
        $this->isTestAccount = $isTestAccount;
        $this->isSme = $isSme;
        $this->managerType = $managerType;
        $this->branchId = $branchId;
        $this->can_assign_to_enquiries = $can_assign_to_enquiries;
        $this->can_check_competency = $can_check_competency;
        $this->trialEnds = $trialEnds;
        $this->creditApplicationForm = $creditApplicationForm;
        $this->companyNumber = $companyNumber;
        $this->pipelineOfWork = $pipelineOfWork;
        $this->potentialUsers = $potentialUsers;
        $this->turnover = $turnover;
        $this->numberOfEmployees = $numberOfEmployees;
        $this->onboardingCall = $onboardingCall;
        $this->nextCall = $nextCall;
        $this->painPoints = $painPoints;
        $this->activityTrackerMapping = $activityTrackerMapping;
        $this->jobTitle = $jobTitle;
        $this->alias = $alias;
        $this->companyType = $companyType;
    }

    public static function createFromRequest(Request $request, int $id = null): self
    {
        $data = $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'username' => 'required|unique:users,username' . ($id ? (',' . $id) : ''),
            'email' => 'required|email',
            'password' => ($id === null) ? 'required' : '',
            'role_ids' => 'required',
            'postcode' => 'required',
            'phone' => 'nullable',
            'country' => 'nullable',
            'city' => 'nullable',
            'addr_line_1' => 'nullable',
            'addr_line_2' => 'nullable',
            'product_ids' => '',
            'billing_user_id' => '',
            'user_plans' => '',
            'is_global' => '',
            'manager_type' => '',
            'branch_id' => '',
            'can_assign_to_enquiries' => '',
            'can_check_competency' => '',
            'trial_ends' => '',
            'creditApplicationForm' => '',
            'is_gold_account' => '',
            'is_test_account' => '',
            'is_sme' => '',
            'company_number' => '',
            'pipeline_of_work' => '',
            'potential_users' => 'nullable',
            'turnover' => 'nullable',
            'number_of_employees' => 'nullable',
            'onboarding_call' => 'nullable',
            'next_call' => 'nullable',
            'pain_points' => 'nullable',
            'activity_tracker_mapping' => '',
            'job_title' => '',
            'alias' => '',
            'company_type' => '',
        ]);

        $data['image'] = $request->file('creditApplicationForm');

        return self::createFromArray($data);
    }

    public static function createFromArray(array $data): self
    {
        $userPlans = $data['user_plans'] ?? null;

        return new self(
            $data['first_name'] ?? '',
            $data['last_name'] ?? '',
            $data['username'] ?? '',
            $data['email'] ?? '',
            $data['password'] ?? '',
            $data['role_ids'] ? (array_map(function ($v) {
                return (int)$v;
            }, $data['role_ids'])) : [],
            $data['postcode'] ?? '',
            $data['phone'] ?? null,
            $data['country'] ?? 'UK',
            $data['city'] ?? null,
            $data['addr_line_1'] ?? null,
            $data['addr_line_2'] ?? null,
            isset($data['product_ids']) ? array_map(function ($v) {
                return (int)$v;
            }, $data['product_ids']) : [],
            isset($data['billing_user_id']) ? (int)$data['billing_user_id'] : null,
            (is_array($userPlans) || empty($userPlans)) ? $userPlans : [$userPlans],
            isset($data['is_global']) && $data['is_global'] === 'true',
            isset($data['is_gold_account']) && $data['is_gold_account'] === 'true',
            isset($data['is_test_account']) && $data['is_test_account'] === 'true',
            isset($data['is_sme']) && $data['is_sme'] === 'true',
            $data['manager_type'] ?? null,
            isset($data['branch_id']) ? (int)$data['branch_id'] : null,
            isset($data['can_assign_to_enquiries']) ? ($data['can_assign_to_enquiries'] === 'true') : null,
            isset($data['can_check_competency']) ? ($data['can_check_competency'] === 'true') : null,
            $data['trial_ends'] ?? null,
            $image ?? null,
            $data['company_number'] ?? '',
            $data['pipeline_of_work'] ?? null,
            isset($data['potential_users']) ? (int)$data['potential_users'] : null,
            isset($data['turnover']) ? (int)$data['turnover'] : null,
            isset($data['number_of_employees']) ? (int)$data['number_of_employees'] : null,
            $data['onboarding_call'] ?? null,
            $data['next_call'] ?? null,
            $data['pain_points'] ?? null,
            $data['activity_tracker_mapping'] ?? null,
            $data['job_title'] ?? null,
            $data['alias'] ?? null,
            $data['company_type'] ?? null
        );
    }

    public function getActivityTrackerMapping(): ?string
    {
        return $this->activityTrackerMapping;
    }

    public function getPainPoints(): ?string
    {
        return $this->painPoints;
    }

    public function getOnboardingCall(): ?string
    {
        return $this->onboardingCall;
    }

    public function getNextCall(): ?string
    {
        return $this->nextCall;
    }

    public function getOnboardingCallCarbon(): ?Carbon
    {
        if ($this->onboardingCall) {
            $date = Carbon::createFromFormat('Y-m-d H:i:s', $this->onboardingCall);
            if ($date) {
                return $date;
            }
        }

        return null;
    }

    public function getNextCallCarbon(): ?Carbon
    {
        if ($this->nextCall) {
            $date = Carbon::createFromFormat('Y-m-d H:i:s', $this->nextCall);
            if ($date) {
                return $date;
            }
        }

        return null;
    }

    public function getNumberOfEmployees(): ?int
    {
        return $this->numberOfEmployees;
    }

    public function getTurnover(): ?int
    {
        return $this->turnover;
    }

    public function getPotentialUsers(): ?int
    {
        return $this->potentialUsers;
    }

    /*public function getPipelineOfWorkCarbon(): ?Carbon
    {
        if ($this->pipelineOfWork) {
            $date = Carbon::createFromFormat('d-m-Y', $this->pipelineOfWork);
            if ($date) {
                return $date;
            }
        }

        return null;
    }*/

    public function getPipelineOfWorkCarbon(): ?string
    {
        return $this->pipelineOfWork;
    }

    public function getCompanyNumber(): string
    {
        return $this->companyNumber;
    }

    public function getFirstname(): string
    {
        return $this->firstName;
    }

    public function getLastname(): string
    {
        return $this->lastName;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getRoleIds(): array
    {
        return $this->roleIds;
    }

    public function getPostcode(): string
    {
        return $this->postcode;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getAddressLine1(): ?string
    {
        return $this->addressLine1;
    }

    public function getAddressLine2(): ?string
    {
        return $this->addressLine2;
    }

    public function getProductIds(): ?array
    {
        return $this->productIds;
    }

    public function getBillingUserId(): ?int
    {
        return $this->billingUserId;
    }

    public function getUserPlans(): ?array
    {
        return $this->userPlans;
    }

    public function getIsGlobal(): ?bool
    {
        return $this->isGlobal;
    }

    public function getManagerType(): ?string
    {
        return $this->managerType;
    }

    public function getBranchId(): ?int
    {
        return $this->branchId;
    }

    public function getCanAssignToEnquiries(): bool
    {
        return $this->can_assign_to_enquiries ?? false;
    }

    public function getCanCheckCompetency(): bool
    {
        return $this->can_check_competency ?? false;
    }

    public function getTrialEnds(): ?string
    {
        return $this->trialEnds;
    }

    public function getTrialEndsCarbon(): ?Carbon
    {
        if ($this->trialEnds) {
            $date = Carbon::createFromFormat('d-m-Y', $this->trialEnds);
            if ($date) {
                return $date;
            }
        }

        return null;
    }

    public function getIsGoldAccount(): ?bool
    {
        return $this->isGoldAccount;
    }

    public function getIsTestAccount(): ?bool
    {
        return $this->isTestAccount;
    }

    public function getIsSme(): ?bool
    {
        return $this->isSme;
    }

    public function getCreditApplicationForm(): ?UploadedFile
    {
        return $this->creditApplicationForm;
    }

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function getCompanyType(): ?string
    {
        return $this->companyType;
    }
}
