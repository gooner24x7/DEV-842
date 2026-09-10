<?php
declare(strict_types=1);

namespace App\Models;

use App\Models\Questionnaire\Project;
use App\Models\Stripe\Subscription;
use App\Models\Stripe\SubscriptionBuilderCustom;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as BaseUser;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Cashier\Billable;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property float $lat
 * @property float $long
 * @property array $product_ids
 * @property boolean $is_global
 * @property ?int $branch_id
 * @property ?string $manager_type
 * @property ?string $preferred_suppliers_file
 * @property string $postcode
 * @property ?string $phone
 * @property ?string $city
 * @property ?string $country
 * @property ?string $addr_line_1
 * @property ?string $addr_line_2
 * @property string $username
 * @property string $email
 * @property ?User $billingUser
 * @property string $password
 * @property Collection $roles
 * @property int $billing_user_id
 * @property string $remember_api_token
 * @property boolean $can_assign_to_enquiries
 * @property boolean $can_check_competency
 * @property int $locations
 * @property string $head_office_address
 * @property string $customer_service
 * @property string $description
 * @property string $logo_url
 * @property ?string $trial_ends
 * @property boolean $is_free_account
 * @property boolean $is_gold_account
 * @property boolean $is_test_account
 * @property boolean $is_sme
 * @property ?string $credit_application_form
 * @property ?string $credit_application_form_url
 * @property boolean $is_quoted
 * @property string $company_number
 * @property ?string $pipeline_of_work
 * @property ?int $potential_users
 * @property ?int $turnover
 * @property ?int $number_of_employees
 * @property ?string $onboarding_call
 * @property ?string $next_call
 * @property ?string $pain_points
 * @property boolean $onboarding_active
 * @property string $activity_tracker_mapping
 * @property ?int $created_by
 * @property string $called_at
 * @property ?string $job_title
 * @property ?string $alias
 * @property ?string $company_type
 * @method static whereNotNull(string $a)
 * @method static where(array $array)
 * @method static whereNull(string $a)
 * @method static create(array $params)
 * @method static select(...$select)
 * @method static whereIn($a, array $b)
 * @method static join($a, $b, $c, $d)
 */
class User extends BaseUser implements JWTSubject
{
    use Notifiable, Billable;

    use Billable {
        createAsStripeCustomer as protected createAsStripeCustomerDefault;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'username',
        'password',
        'postcode',
        'lat',
        'long',
        'billing_user_id',
        'country',
        'city',
        'addr_line_1',
        'addr_line_2',
        'phone',
        'is_global',
        'branch_id',
        'manager_type',
        'can_assign_to_enquiries',
        'can_check_competency',
        'locations',
        'head_office_address',
        'customer_service',
        'description',
        'logo_url',
        'trial_ends',
        'is_free_account',
        'is_gold_account',
        'is_test_account',
        'is_sme',
        'company_number',
        'pipeline_of_work',
        'potential_users',
        'turnover',
        'number_of_employees',
        'onboarding_call',
        'next_call',
        'pain_points',
        'activity_tracker_mapping',
        'created_by',
        'job_title',
        'alias',
        'company_type',
        'credit_application_form',
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected $appends = [
        'role_name',
        'role_ids',
        'product_ids',
        'permissions',
        'credit_application_form_url',
        'is_paid',
        'onboarding_active',
        'competency_users'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'can_assign_to_enquiries' => 'boolean',
        'can_check_competency' => 'boolean',
        'is_global' => 'boolean',
        'is_free_account' => 'boolean',
        'is_gold_account' => 'boolean',
        'is_test_account' => 'boolean',
        'is_sme' => 'boolean',
    ];

    public function getJWTIdentifier(): string
    {
        return (string)$this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'user_id', 'id');
    }

    public function supplyFitEnquiries(): HasMany
    {
        return $this->hasMany(SupplyFitEnquiry::class, 'user_id', 'id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'user_id', 'id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'user_id', 'id');
    }

    public function personalNotes(): HasMany
    {
        return $this->hasMany(UserNote::class, 'user_id', 'id');
    }

    public function authoredNotes(): HasMany
    {
        return $this->hasMany(UserNote::class, 'author_id', 'id');
    }

    public function billingUser(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'billing_user_id');
    }

    public function hasPermissionTo($permission): bool
    {
        return $this->hasPermissionThroughRole($permission);
    }

    public function hasPermissionThroughRole(Permission $permission): bool
    {
        foreach ($permission->roles as $role) {
            if ($this->roles->contains($role)) {
                return true;
            }
        }

        return false;
    }

    public function getPermissionsAttribute(): array
    {
        return $this->getPermissions();
    }

    public function getPermissions(): array
    {
        $permissions = [];
        foreach ($this->roles as $role) {
            $permissions = array_merge($permissions, $role->permissions->toArray());
        }

        return $permissions;
    }

    public function isBranchManager(): bool
    {
        return $this->hasRole(Role::ROLE_BRANCH_MANAGER);
    }

    public function hasRole(...$roles): bool
    {
        foreach ($roles as $role) {
            if ($this->roles->contains('slug', $role)) {
                return true;
            }
        }

        return false;
    }

    public function isBillingUser(): bool
    {
        return $this->hasRole(Role::ROLE_BILLING_USER_SLUG);
    }

    public function isSupplier(): bool
    {
        return $this->hasRole(Role::ROLE_COMPANY_SLUG);
    }

    public function isLogistics(): bool
    {
        return $this->hasRole(Role::ROLE_LOGISTICS);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isSubContractor(): bool
    {
        return $this->hasRole('user');
    }

    public function can($abilities, $arguments = []): bool
    {
        return $this->hasPermission($abilities);
    }

    public function hasPermission(...$permissions): bool
    {
        foreach ($permissions as $permission) {
            /** @var Role $role */
            foreach ($this->roles as $role) {
                if ($role->permissions->contains('slug', $permission)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getRoleNameAttribute(): Collection
    {
        return $this->roles()->select('name')->pluck('name');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'users_roles');
    }

    public function getRoleIdsAttribute(): Collection
    {
        return $this->roles()->select('id')->pluck('id');
    }

    public function getRolesShortAttribute(): Collection
    {
        return $this->roles()->select('id', 'name', 'slug')->get();
    }

    public function getProductIdsAttribute(): Collection
    {
        return $this->products()->select('products.id')->pluck('products.id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function stripeSubs(): HasMany
    {
        return $this->hasMany(Subscription::class, 'user_id', 'id');
    }

    public function getPreferredUsersFileUrl(): ?string
    {
        if (!$this->getPreferredSuppliersFile()) {
            return null;
        }

        return url(rtrim(config('app.url'), '/') . '/api/downloadFile?file=' . urlencode($this->getPreferredSuppliersFile()));
    }

    public function getPreferredSuppliersFile(): ?string
    {
        return $this->preferred_suppliers_file;
    }

    public function setPassword($password): self
    {
        $this->password = Hash::make($password);

        return $this;
    }

    public function preferredSuppliers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'users_preferred_suppliers', 'user_id', 'supplier_id');
    }

    public function preferredSubcontractors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'users_preferred_subcontractors', 'user_id', 'subcontractor_id');
    }

    public function scopeInRadius($query, float $lat, float $lon, float $radius)
    {
        $haversine = "(0.62137 * (6371 * acos(cos(radians(" . $lat . "))
                      * cos(radians(`users`.`lat`))
                      * cos(radians(`users`.`long`)
                      - radians(" . $lon . "))
                      + sin(radians(" . $lat . "))
                      * sin(radians(`users`.`lat`)))))";

        return $query->addSelect(DB::raw("{$haversine} AS distance"))
            ->whereRaw("{$haversine} <= ?", [$radius]);
    }

    public function scopeInRadiusArray($query, array $coords, float $radius)
    {
        if (empty($coords)) {
            return $query;
        }

        foreach($coords as $key => $value) {
            if (empty($value['lat']) || empty($value['long'])) {
                continue;
            }

            $haversine = "(0.62137 * (6371 * acos(cos(radians(" . $value['lat'] . "))
                      * cos(radians(`users`.`lat`))
                      * cos(radians(`users`.`long`)
                      - radians(" . $value['long'] . "))
                      + sin(radians(" . $value['lat'] . "))
                      * sin(radians(`users`.`lat`)))))";

            $alias = 'distance' . $key;

            $query->addSelect(DB::raw("{$haversine} AS {$alias}"));

            if ($key === 0) {
                $query->whereRaw("{$haversine} <= ?", [$radius]);
            } else {
                $query->orWhereRaw("{$haversine} <= ?", [$radius]);
            }
        }

        return $query;
    }

    /**
     * Create stripe subscription
     * @param $name
     * @param $plans
     * @param Carbon|null $trialEnds
     * @return SubscriptionBuilderCustom
     */
    public function newSubscription($name, $plans, ?Carbon $trialEnds = null): SubscriptionBuilderCustom
    {
        return new SubscriptionBuilderCustom($this, $name, $plans, $trialEnds);
    }

    public function createAsStripeCustomer(array $options = []): \Stripe\Customer
    {
        $options = $this->appendCustomProps($options);

        return $this->createAsStripeCustomerDefault($options);
    }

    private function appendCustomProps(array $options): array
    {
        $options['name'] = $this->getFullName();
        $options['phone'] = $this->phone;
        $options['address'] = [
            'country' => 'GB',
            'city' => $this->city,
            'line1' => $this->addr_line_1,
            'line2' => $this->addr_line_2,
            'postal_code' => $this->postcode,
        ];

        return $options;
    }

    private function getFullName(): string
    {
        return trim(implode(' ', [$this->first_name, $this->last_name]));
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->first_name;
    }

    public function getLastName(): string
    {
        return $this->last_name;
    }

    public function getManagerType(): ?string
    {
        return $this->manager_type;
    }

    public function getBranchId(): ?int
    {
        return $this->branch_id;
    }

    public function getBranchName(): ?string
    {
        $branch = User::find(['id' => $this->branch_id])->first();

        return $branch ? $branch->first_name : null;
    }

    public function getIsGlobal(): bool
    {
        return $this->is_global;
    }

    public function getIsFreeAccount(): bool
    {
        return $this->is_free_account;
    }

    public function getIsGoldAccount(): bool
    {
        return $this->is_gold_account;
    }

    public function getIsTestAccount(): bool
    {
        return $this->is_test_account;
    }

    public function getIsSme(): bool
    {
        return $this->is_sme;
    }

    public function getPostcode(): string
    {
        return $this->postcode;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getAddressLine1(): ?string
    {
        return $this->addr_line_1;
    }

    public function getAddressLine2(): ?string
    {
        return $this->addr_line_2;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getLat(): ?float
    {
        return $this->lat;
    }

    public function getLong(): ?float
    {
        return $this->long;
    }

    public function getCanAssignToEnquiries(): bool
    {
        //return $this->can_assign_to_enquiries;

        $billingUserId = $this->getBillingUserId();

        if (!empty($billingUserId)) {
            $user_count = DB::table('users')
                ->join('subscriptions', 'users.billing_user_id', '=', 'subscriptions.user_id')
                ->where('billing_user_id', '=', $billingUserId)
                ->where('stripe_status', '=', 'active')
                ->count();

            if ($user_count >= 2) {
                return true;
            }
        }

        return false;
    }

    public function getBillingUserId(): ?int
    {
        return $this->billing_user_id;
    }

    public function getCompanyNumber(): string
    {
        return $this->company_number;
    }

    public function getJobTitle(): ?string
    {
        return $this->job_title;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function getCompanyType(): ?string
    {
        return $this->company_type;
    }

    public function getCreditApplicationFormUrlAttribute(): ?string
    {
        if (!$this->getCreditApplicationForm()) {
            return null;
        }

        return '/' . str_replace('public', 'storage', $this->getCreditApplicationForm());
    }

    public function getCreditApplicationForm(): ?string
    {
        return $this->credit_application_form;
    }

    public function getIsPaidAttribute(): bool
    {
        $billingUser = $this->billingUser;

        if ($billingUser && !$billingUser->is_free_account && config("app.subscriptions_enabled")) {
            $subscription = $billingUser->subscription(Subscription::DEFAULT_NAME);

            if ($subscription) {
                $subscriptionArr = $subscription->toArray();
                $trial_ends = !empty($subscriptionArr['trial_ends_at']) ? new Carbon($subscriptionArr['trial_ends_at']) : null;

                if ($subscription->stripe_status !== 'active' || ($trial_ends && $trial_ends->isPast())) {
                    return false;
                }
            }
        }

        return true;
    }

    public function getOnboardingActiveAttribute(): bool
    {
        $oc = $this->onboardingChecklist()->get()->first();

        if (!$oc) {
            return false;
        }

        // disable onboarding if end date is past
//        if (!empty($oc->onboarding_end_date)) {
//            $end_date = Carbon::createFromFormat('Y-m-d', $oc->onboarding_end_date);
//
//            if ($end_date->isPast()) {
//                return false;
//            }
//        }

        // disable onboarding if user was created by a partner
        if ($this->created_by) {
            $createdByUser = User::find($this->created_by);

            if (!empty($createdByUser) && $createdByUser->hasRole(Role::ROLE_PARTNER)) {
                return false;
            }
        }

        if ($this->hasRole(Role::ROLE_USER_SLUG)) {
            $checklist = [
                'account_setup' => $oc->account_setup,
                'form_complete' => $oc->form_complete,
                'first_enquiry' => $oc->first_enquiry,
                'line_of_credit' => $oc->line_of_credit,
            ];

            foreach ($checklist as $check) {
                if (!$check) {
                    return true;
                }
            }
        }

        if ($this->hasRole(Role::ROLE_CONTRACTOR)) {
            $checklist = [
                'account_setup' => $oc->account_setup,
                'form_complete' => $oc->form_complete,
                'first_project' => $oc->first_project,
                'first_works_package' => $oc->first_works_package,
                'first_tender' => $oc->first_tender
            ];

            foreach ($checklist as $check) {
                if (!$check) {
                    return true;
                }
            }
        }

        return false;
    }

    public function onboardingChecklist(): HasOne
    {
        return $this->hasOne(UserOnboarding::class, 'user_id', 'id');
    }

    public function contractorOnboarding(): HasOne
    {
        return $this->hasOne(ContractorOnboarding::class, 'user_id', 'id');
    }

    public function creditsafeInfo(): HasOne
    {
        return $this->hasOne(UserCreditsafe::class, 'user_id', 'id');
    }

    public function getCompanyUsers(): array {
        if ($this->hasRole(Role::ROLE_BILLING_USER_SLUG)) {
            $billingUserId = $this->getId();
        } else {
            $billingUserId = $this->getBillingUserId();
        }

        if ($billingUserId === null) {
            return [$this->getId()];
        }

        return User::query()->select('id')
            ->where('billing_user_id', '=', $billingUserId)
            ->pluck('id')->toArray();
    }

    public function getCompetencyUsersAttribute(): array {
        $companyUserIds = $this->getCompanyUsers();

        return User::query()->select('first_name')
            ->whereIn('id', $companyUserIds)
            ->where('can_check_competency', '=', true)
            ->pluck('first_name')->toArray();
    }
}
