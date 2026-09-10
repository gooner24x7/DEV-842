<?php
declare(strict_types=1);

namespace App\Repository;

use App\Dto\SearchParamsDto;
use App\Dto\User\UserDto;
use App\Models\Role;
use App\Models\Stripe\Subscription;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserRepository
{
    const string DEFAULT_ORDER_FIELD_NAME = 'id';
    const int ITEMS_PER_PAGE = 20;
    private PostcodesRepository $postcodesRepository;

    public function __construct(PostcodesRepository $postcodesRepository)
    {
        $this->postcodesRepository = $postcodesRepository;
    }

    public function find(SearchParamsDto $searchParamsDto, ?int $billingUserId = null, $roleId = '', $branches = false): LengthAwarePaginator
    {
        $query = User::select(
            'users.id',
            'users.billing_user_id',
            'users.username',
            'users.first_name',
            'users.last_name',
            'users.postcode',
            'users.email',
            'users.lat',
            'users.long',
            'users.city',
            'users.phone',
            'users.addr_line_1',
            'users.addr_line_2',
            'users.preferred_suppliers_file',
            'users.is_global',
            'users.manager_type',
            'users.branch_id',
            'users.can_assign_to_enquiries',
            'users.can_check_competency',
            'users.trial_ends',
            'users.credit_application_form',
            'users.is_gold_account',
            'users.is_test_account',
            'users.is_sme',
            'users.company_number',
            'users.pipeline_of_work',
            'users.potential_users',
            'users.turnover',
            'users.number_of_employees',
            'users.onboarding_call',
            'users.pain_points',
            'users.activity_tracker_mapping',
            'users.job_title',
            'users.alias',
            'users.company_type',
            DB::raw('((select a.id from answers a where a.user_id=users.id limit 1) is not null) as is_quoted'),
        )
            ->distinct()
            ->leftJoin('users_roles', 'users.id', '=', 'users_roles.user_id')
            ->leftJoin('roles', 'roles.id', '=', 'users_roles.role_id');

        if ($billingUserId) {
            $query->where(['billing_user_id' => $billingUserId]);
        }

        if ($branches) {
            $query->where('roles.slug', '=', Role::ROLE_COMPANY_SLUG);
        }

        if ($roleId) {
            $query->where('roles.id', '=', $roleId);
        }

        if ($searchParamsDto->getSearch()) {
            $query->where(function ($query) use ($searchParamsDto) {
                $query->where('users.username', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('users.first_name', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('users.last_name', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('users.phone', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('users.email', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('users.postcode', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('users.city', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('users.addr_line_1', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('users.addr_line_2', 'like', '%' . $searchParamsDto->getSearch() . '%');
            });
        }

        $orderBy = $searchParamsDto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME;
        $orderBy = ($orderBy == 'role_name') ? 'roles.name' : $orderBy;

        $query = $query->orderBy(
            $orderBy,
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        $itemsPerPage = $searchParamsDto->getItemsPerPage() ?? self::ITEMS_PER_PAGE;
        if (!$searchParamsDto->isPaginationEnabled()) {
            $itemsPerPage = PHP_INT_MAX;
        }

        return $query->paginate($itemsPerPage);
    }

    public function delete(int $userId): bool
    {
        $user = $this->getUserById($userId);

        return $user->delete();
    }

    public function getUserById(int $userId): ?User
    {
        return User::where(['id' => $userId])->first();
    }

    public function getUserStripeSubscription(int $userId): ?Subscription
    {
        /** @var User $user */
        $user = User::where(['id' => $userId])->first();
        if (!$user) {
            return null;
        }

        return $user->stripeSubs()->active()->first();
    }

    public function searchUserByRole(int $roleId, string $search, array $productIds = []): Collection
    {
        $query = User::join('users_roles', 'users.id', '=', 'users_roles.user_id')
            ->distinct()
            ->where(['users_roles.role_id' => $roleId])
            ->where('users.first_name', 'like', $search . '%')
            ->orderBy('users.first_name', 'asc')
            ->limit(self::ITEMS_PER_PAGE);

        if ($productIds) {
            $placeholder = implode(', ', array_fill(0, count($productIds), '?'));

            $query->whereRaw('users.id IN (select product_user.user_id from product_user where product_user.product_id in (' . $placeholder . '))', $productIds);
        }

        return $query->get();
    }

    public function store(UserDto $userDto): ?User
    {
        $postcode = $userDto->getPostcode();
        $alias = $userDto->getAlias();

        if (empty($alias) && !empty($userDto->getFirstName()) && !empty($userDto->getLastName())) {
            $alias = strtoupper(substr($userDto->getFirstname(), 0, 1) . substr($userDto->getLastname(), 0, 1));
        }

        $user = User::create([
            'first_name' => $userDto->getFirstname(),
            'last_name' => $userDto->getLastname(),
            'username' => $userDto->getUsername(),
            'email' => $userDto->getEmail(),
            'password' => Hash::make($userDto->getPassword()),
            'postcode' => $postcode,
            'phone' => $userDto->getPhone(),
            'country' => $userDto->getCountry(),
            'city' => $userDto->getCity(),
            'addr_line_1' => $userDto->getAddressLine1(),
            'addr_line_2' => $userDto->getAddressLine2(),
            'billing_user_id' => $userDto->getBillingUserId(),
            'is_global' => $userDto->getIsGlobal(),
            'is_gold_account' => $userDto->getIsGoldAccount(),
            'is_test_account' => $userDto->getIsTestAccount(),
            'is_sme' => $userDto->getIsSme(),
            'manager_type' => $userDto->getManagerType(),
            'branch_id' => $userDto->getBranchId(),
            'can_assign_to_enquiries' => $userDto->getCanAssignToEnquiries(),
            'can_check_competency' => $userDto->getCanCheckCompetency(),
            'trial_ends' => $userDto->getTrialEndsCarbon(),
            'credit_application_form' => ($userDto->getCreditApplicationForm()) ? $userDto->getCreditApplicationForm()->storeAs('public/credit_application_forms', $userDto->getCreditApplicationForm()->getClientOriginalName()) : null,
            'company_number' => $userDto->getCompanyNumber(),
            'pipeline_of_work' => $userDto->getPipelineOfWorkCarbon(),
            'potential_users' => $userDto->getPotentialUsers(),
            'turnover' => $userDto->getTurnover(),
            'number_of_employees' => $userDto->getNumberOfEmployees(),
            'onboarding_call' => $userDto->getOnboardingCallCarbon(),
            'next_call' => $userDto->getNextCallCarbon(),
            'pain_points' => $userDto->getPainPoints(),
            'activity_tracker_mapping' => $userDto->getActivityTrackerMapping() ?? '',
            'job_title' => $userDto->getJobTitle(),
            'alias' => $alias,
            'company_type' => $userDto->getCompanyType()
        ]);

        $this->updateUserCoords($user, $postcode);

        return $user;
    }

    public function storeRolesForUser(int $userId, array $roleIds): array
    {
        $user = $this->getUserById($userId);
        if (!$user) {
            return [];
        }

        return $user->roles()->sync($roleIds);
    }

    public function storeProductsForUser(int $userId, array $productIds): array
    {
        $user = $this->getUserById($userId);
        if (!$user) {
            return [];
        }

        if (empty($productIds)) {
            DB::table('product_user')->where(['user_id' => $userId])->delete();

            return [];
        }

        return $user->products()->sync($productIds);
    }

    public function getByUsername(string $username): ?User
    {
        return User::where(['username' => $username])->first();
    }

    public function getByEmail(string $email): ?User
    {
        return User::where(['email' => $email])->first();
    }

    public function getContractorStats(SearchParamsDto $searchParamsDto): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = User::query()
            ->select(
                'users.id',
                "users.first_name as company_name",
                "users.company_number as company_number",
                "users.email as email",
                "users.phone as phone",
                "users.billing_user_id",
                "b.trial_ends",
                "b.first_name AS billing_company",
                "b.email AS billing_email",
                "b.phone AS billing_phone",
                "users.created_at as date_created",
                "users.pipeline_of_work as pipeline_of_work",
                "users.potential_users as potential_users",
                "users.turnover as turnover",
                "users.number_of_employees as number_of_employees",
                "users.onboarding_call as onboarding_call",
                "users.next_call as next_call",
                "users.pain_points as pain_points",
                "uo.onboarding_end_date as onboarding_end_date",
                DB::raw("MAX(questions.created_at) as last_enquiry_date"),
                DB::raw("count(questions.id) as total_enquiries"),
            )
            ->leftJoin('users_roles', 'users.id', '=', 'users_roles.user_id')
            ->leftJoin('roles', 'roles.id', '=', 'users_roles.role_id')
            ->leftJoin('questions', 'questions.user_id', '=', 'users.id')
            ->leftJoin('users As b', 'users.billing_user_id', '=', 'b.id')
            ->leftJoin('user_onboarding AS uo', 'users.id', '=', 'uo.user_id');

        $query->where('roles.slug', '=', Role::ROLE_USER_SLUG);

        if ($searchParamsDto->getSearch()) {
            $query->where(function ($query) use ($searchParamsDto) {
                $query->orWhere('users.first_name', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('users.last_name', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('users.phone', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('users.email', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('b.first_name', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('b.email', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('b.phone', 'like', '%' . $searchParamsDto->getSearch() . '%');
            });
        }

        $query->groupBy(
            'users.id',
            'users.first_name',
            'users.company_number',
            'users.email',
            'users.phone',
            "users.billing_user_id",
            "b.trial_ends",
            'b.first_name',
            'b.email',
            'b.phone',
            'users.created_at',
            'users.pipeline_of_work',
            'users.potential_users',
            'users.turnover',
            'users.number_of_employees',
            'users.onboarding_call',
            'users.next_call',
            'users.pain_points',
            'uo.onboarding_end_date'
        );

        $orderBy = $searchParamsDto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME;
        $orderBy = ($orderBy == 'role_name') ? 'roles.name' : $orderBy;

        $query = $query->orderBy(
            $orderBy,
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        $itemsPerPage = $searchParamsDto->getItemsPerPage() ?? self::ITEMS_PER_PAGE;
        if (!$searchParamsDto->isPaginationEnabled()) {
            $itemsPerPage = PHP_INT_MAX;
        }

        return $query->paginate($itemsPerPage);
    }

    public function getMerchantStats(SearchParamsDto $searchParamsDto)
    {
        $query = User::query()
            ->select(
                'users.id',
                "users.first_name as company_name",
                "users.company_number as company_number",
                "users.email as email",
                "users.phone as phone",
                "b.first_name AS billing_company",
                "users.billing_user_id",
                "b.trial_ends",
                "b.email AS billing_email",
                "b.phone AS billing_phone",
                "users.created_at as date_created",
                "users.pipeline_of_work as pipeline_of_work",
                "users.potential_users as potential_users",
                "users.turnover as turnover",
                "users.number_of_employees as number_of_employees",
                "users.onboarding_call as onboarding_call",
                "users.next_call as next_call",
                "users.pain_points as pain_points",
                "uo.onboarding_end_date as onboarding_end_date",
                DB::raw("MAX(answers.created_at) as last_quote_date"),
                DB::raw("count(answers.id) as total_quotes"),
            )
            ->leftJoin('users_roles', 'users.id', '=', 'users_roles.user_id')
            ->leftJoin('roles', 'roles.id', '=', 'users_roles.role_id')
            ->leftJoin('answers', 'answers.user_id', '=', 'users.id')
            ->leftJoin('users AS b', 'users.billing_user_id', '=', 'b.id')
            ->leftJoin('user_onboarding AS uo', 'users.id', '=', 'uo.user_id');

        $query->where('roles.slug', '=', Role::ROLE_COMPANY_SLUG);

        if ($searchParamsDto->getSearch()) {
            $query->where(function ($query) use ($searchParamsDto) {
                $query->orWhere('users.first_name', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('users.last_name', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('users.phone', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('users.email', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('b.first_name', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('b.email', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('b.phone', 'like', '%' . $searchParamsDto->getSearch() . '%');
            });
        }

        $query->groupBy(
            'users.id',
            'users.first_name',
            'users.company_number',
            'users.email',
            'users.phone',
            "users.billing_user_id",
            "b.trial_ends",
            'b.first_name',
            'b.email',
            'b.phone',
            'users.created_at',
            'users.pipeline_of_work',
            'users.potential_users',
            'users.turnover',
            'users.number_of_employees',
            'users.onboarding_call',
            'users.next_call',
            'users.pain_points',
            'uo.onboarding_end_date'
        );

        $orderBy = $searchParamsDto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME;
        $orderBy = ($orderBy == 'role_name') ? 'roles.name' : $orderBy;

        $query = $query->orderBy(
            $orderBy,
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        $itemsPerPage = $searchParamsDto->getItemsPerPage() ?? self::ITEMS_PER_PAGE;
        if (!$searchParamsDto->isPaginationEnabled()) {
            $itemsPerPage = PHP_INT_MAX;
        }

        return $query->paginate($itemsPerPage);
    }

    public function getManufacturerStats(SearchParamsDto $searchParamsDto): LengthAwarePaginator
    {
        $query = User::query()
            ->select(
                'users.id',
                "users.first_name as company_name",
                "users.company_number as company_number",
                "users.email as email",
                "users.phone as phone",
                "b.first_name AS billing_company",
                "b.email AS billing_email",
                "b.phone AS billing_phone",
                "users.billing_user_id",
                "b.trial_ends",
                "users.created_at as date_created",
                "users.pipeline_of_work as pipeline_of_work",
                "users.potential_users as potential_users",
                "users.turnover as turnover",
                "users.number_of_employees as number_of_employees",
                "users.onboarding_call as onboarding_call",
                "users.next_call as next_call",
                "users.pain_points as pain_points",
                "uo.onboarding_end_date as onboarding_end_date",
                DB::raw("SUM(case when analytics_events.action=\"view\" then 1 else 0 end) as impressions"),
                DB::raw("SUM(case when analytics_events.action=\"click\" then 1 else 0 end) as clicks"),
                DB::raw("SUM(case when analytics_events.action=\"play-video\" then 1 else 0 end) as video_views"),
            )
            ->leftJoin('users_roles', 'users.id', '=', 'users_roles.user_id')
            ->leftJoin('roles', 'roles.id', '=', 'users_roles.role_id')
            ->leftJoin('virtual_expos', 'virtual_expos.user_id', '=', 'users.id')
            ->leftJoin('analytics_events', 'analytics_events.object_id', '=', 'virtual_expos.id')
            ->leftJoin('users AS b', 'users.billing_user_id', '=', 'b.id')
            ->leftJoin('user_onboarding AS uo', 'users.id', '=', 'uo.user_id');

        $query->whereRaw('analytics_events.object_type="virtual-expo"');
        $query->where('roles.slug', '=', Role::ROLE_MANUFACTURER);

        if ($searchParamsDto->getSearch()) {
            $query->where(function ($query) use ($searchParamsDto) {
                $query->orWhere('users.first_name', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('users.last_name', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('users.phone', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('users.email', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('b.first_name', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('b.email', 'like', '%' . $searchParamsDto->getSearch() . '%');
                $query->orWhere('b.phone', 'like', '%' . $searchParamsDto->getSearch() . '%');
            });
        }

        $query->groupBy(
            'users.id',
            'users.first_name',
            'users.company_number',
            'users.email',
            'users.phone',
            "users.billing_user_id",
            "b.trial_ends",
            'b.first_name',
            'b.email',
            'b.phone',
            'users.created_at',
            'users.pipeline_of_work',
            'users.potential_users',
            'users.turnover',
            'users.number_of_employees',
            'users.onboarding_call',
            'users.next_call',
            'users.pain_points',
            'uo.onboarding_end_date'
        );

        $orderBy = $searchParamsDto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME;
        $orderBy = ($orderBy == 'role_name') ? 'roles.name' : $orderBy;

        $query = $query->orderBy(
            $orderBy,
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        $itemsPerPage = $searchParamsDto->getItemsPerPage() ?? self::ITEMS_PER_PAGE;
        if (!$searchParamsDto->isPaginationEnabled()) {
            $itemsPerPage = PHP_INT_MAX;
        }

        return $query->paginate($itemsPerPage);
    }

    public function getCalledUsers(SearchParamsDto $searchParamsDto, ?int $merchantId): LengthAwarePaginator
    {
        $query = DB::table('inquiry_merchant')
            ->select([
                'inquiry_merchant.comment',
                'inquiry_merchant.created_at',
                'inquiry_merchant.called_at',
                'inquiry_merchant.user_id as merchant_id',
                'a.first_name as merchant_name',
                'b.first_name as author_name',
                'inquiry_merchant.inquiry_id',
            ])
            ->leftJoin('users as a', 'a.id', '=', 'inquiry_merchant.user_id')
            ->leftJoin('users as b', 'b.id', '=', 'inquiry_merchant.author_id');

        if ($merchantId) {
            $query->where([
                'inquiry_merchant.user_id' => $merchantId,
            ]);
        }

        $orderBy = $searchParamsDto->getOrderBy() ?? 'created_at';
        if ($orderBy === 'id') {
            $orderBy = 'inquiry_merchant.id';
        }

        $query = $query->orderBy(
            $orderBy,
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        $itemsPerPage = $searchParamsDto->getItemsPerPage() ?? self::ITEMS_PER_PAGE;
        if (!$searchParamsDto->isPaginationEnabled()) {
            $itemsPerPage = PHP_INT_MAX;
        }

        return $query->paginate($itemsPerPage);
    }

    public function updateCsp(int $userId, array $data): User
    {
        $user = $this->getUserById($userId);
        $user->pipeline_of_work = $data['pipeline_of_work'] ?: null;
        $user->potential_users = $data['potential_users'] ?: null;
        $user->turnover = $data['turnover'] ?: null;
        $user->number_of_employees = $data['number_of_employees'] ?: null;
        $user->onboarding_call = $data['onboarding_call'] ?: null;
        $user->next_call = $data['next_call'] ?: null;
        $user->pain_points = $data['pain_points'] ?: null;

        if (!empty($data['onboarding_end_date'])) {
            $user->onboardingChecklist()->update(['onboarding_end_date' => $data['onboarding_end_date']]);
        }

        $user->save();

        return $user;
    }

    public function update(int $userId, UserDto $userDto): ?User
    {
        $user = $this->getUserById($userId);
        if (!$user) {
            return null;
        }

        $postcode = $userDto->getPostcode();

        // update user coords if postcode was changed
        if (!empty($postcode) && $postcode !== $user->getPostcode()) {
            $this->updateUserCoords($user, $postcode);
        }

        $user->first_name = $userDto->getFirstname();
        $user->last_name = $userDto->getLastname();
        $user->username = $userDto->getUsername();
        $user->email = $userDto->getEmail();
        $user->postcode = $postcode;
        $user->phone = $userDto->getPhone();
        $user->country = $userDto->getCountry();
        $user->city = $userDto->getCity();
        $user->addr_line_1 = $userDto->getAddressLine1();
        $user->addr_line_2 = $userDto->getAddressLine2();
        $user->billing_user_id = $userDto->getBillingUserId();
        $user->is_global = $userDto->getIsGlobal() ?? false;
        $user->manager_type = $userDto->getManagerType();
        $user->branch_id = $userDto->getBranchId();
        $user->can_assign_to_enquiries = $userDto->getCanAssignToEnquiries();
        $user->can_check_competency = $userDto->getCanCheckCompetency();
        $user->trial_ends = $userDto->getTrialEndsCarbon();
        $user->is_gold_account = $userDto->getIsGoldAccount();
        $user->is_test_account = $userDto->getIsTestAccount();
        $user->is_sme = $userDto->getIsSme();
        $user->company_number = $userDto->getCompanyNumber();
        $user->pipeline_of_work = $userDto->getPipelineOfWorkCarbon();
        $user->potential_users = $userDto->getPotentialUsers();
        $user->turnover = $userDto->getTurnover();
        $user->number_of_employees = $userDto->getNumberOfEmployees();
        $user->onboarding_call = $userDto->getOnboardingCallCarbon();
        $user->next_call = $userDto->getNextCallCarbon();
        $user->pain_points = $userDto->getPainPoints();
        $user->activity_tracker_mapping = $userDto->getActivityTrackerMapping() ?? '';
        $user->job_title = $userDto->getJobTitle();
        $user->alias = $userDto->getAlias();
        $user->company_type = $userDto->getCompanyType();

        if (!empty($userDto->getPassword())) {
            $user->setPassword($userDto->getPassword());
        }

        if ($userDto->getCreditApplicationForm()) {
            $user->credit_application_form = $userDto->getCreditApplicationForm()->storeAs('public/credit_application_forms', $userDto->getCreditApplicationForm()->getClientOriginalName());
        }

        $user->save();

        return $user;
    }

    private function updateUserCoords(User $user, string $postcode): void
    {
        if (empty($postcode)) {
            return;
        }

        $coords = $this->postcodesRepository->getCoords([$postcode]);

        if (!empty($coords)) {
            $user->lat = $coords[0]['latitude'];
            $user->long = $coords[0]['longitude'];
        } else {
            $districtStr = $this->postcodesRepository->preparePostcode($postcode);
            $district = $this->postcodesRepository->getDistrict($districtStr);

            if ($district) {
                $user->lat = $district->Latitude;
                $user->long = $district->Longitude;
            }
        }

        $user->save();
    }

    public function getPreferredSuppliers(User $user): Collection
    {
        $billingUserId = $user->getBillingUserId() ?? $user->getId();

        $query = User::query()->select('users.*')
            ->join('users_preferred_suppliers', 'users_preferred_suppliers.supplier_id', '=', 'users.id')
            ->where('users_preferred_suppliers.user_id', $billingUserId);

        return $query->get();
    }

    public function getPreferredSubcontractors(User $user): Collection
    {
        $billingUserId = $user->getBillingUserId() ?? $user->getId();

        $query = User::query()->select('users.*')
            ->join('users_preferred_subcontractors', 'users_preferred_subcontractors.subcontractor_id', '=', 'users.id')
            ->where('users_preferred_subcontractors.user_id', $billingUserId);

        return $query->get();
    }
}
