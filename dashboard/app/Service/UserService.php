<?php
declare(strict_types=1);

namespace App\Service;

use App\Builder\Response\UserResponseBuilder;
use App\DataProvider\ProductDataProvider;
use App\DataProvider\UserDataProvider;
use App\DataProvider\UserRoleDataProvider;
use App\Dto\SearchParamsDto;
use App\Dto\User\UserDto;
use App\Exceptions\NotFoundException;
use App\Mail\UserRegister;
use App\Models\Role;
use App\Models\Stripe\Subscription;
use App\Models\User;
use App\Models\UserCreditsafe;
use App\Models\UserOnboarding;
use App\Repository\InquiryMerchantRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Cache\Repository;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RedisException;

class UserService
{
    private UserDataProvider $userDataProvider;
    private UserRoleDataProvider $userRoleDataProvider;
    private ProductDataProvider $productDataProvider;
    private InquiryMerchantRepository $inquiryMerchantRepository;
    private SubscriptionService $subscriptionService;
    private CreditSafeService $creditSafeService;

    public function __construct(
        UserDataProvider          $userDataProvider,
        UserRoleDataProvider      $userRoleDataProvider,
        ProductDataProvider       $productDataProvider,
        InquiryMerchantRepository $inquiryMerchantRepository,
        SubscriptionService       $subscriptionService,
        CreditSafeService         $creditSafeService
    )
    {
        $this->userDataProvider = $userDataProvider;
        $this->userRoleDataProvider = $userRoleDataProvider;
        $this->productDataProvider = $productDataProvider;
        $this->inquiryMerchantRepository = $inquiryMerchantRepository;
        $this->subscriptionService = $subscriptionService;
        $this->creditSafeService = $creditSafeService;
    }

    public static function slugify(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    /**
     * @throws RedisException
     */
    public function getUsersWithDetails(SearchParamsDto $searchParamsDto, ?int $billingUserId, ?string $roleId, bool $branches): LengthAwarePaginator
    {
        $response = $this->userDataProvider->find($searchParamsDto, $billingUserId, $branches, $roleId, false);

        $response->getCollection()->transform(function (User $user) {
            $subscriptionName = $this->subscriptionService->getUserStripeSubscriptionName($user->getId());
            return UserResponseBuilder::getResponseForUser($user, $subscriptionName);
        });

        return $response;
    }

    /**
     * @throws RedisException
     * @throws NotFoundException
     */
    public function searchUsersByRole(string $roleSlug, string $search = '', array $productIds = []): Collection
    {
        $role = $this->userRoleDataProvider->getBySlug($roleSlug, false);
        if (!$role) {
            throw new NotFoundException();
        }

        return $this->userDataProvider->searchUserByRole($role->getId(), $search, $productIds, false);
    }

    /**
     * @throws RedisException
     */
    public function deleteUser(int $userId): bool
    {
        $user = $this->getById($userId);
        if (!$user) {
            return false;
        }

        $this->userDataProvider->storeRolesForUser($user->getId(), []);
        $this->userDataProvider->storeProductsForUser($user->getId(), []);
        $user->onboardingChecklist()->delete();

        return $this->userDataProvider->delete($user->getId());
    }

    /**
     * @throws RedisException
     */
    public function getById(int $id, bool $useCache = true): ?User
    {
        return $this->userDataProvider->getById($id, $useCache);
    }

    /**
     * @throws RedisException
     */
    public function update(int $userId, UserDto $userDto): ?User
    {
        $user = $this->userDataProvider->update($userId, $userDto);
        if ($user) {
            $roleIds = $userDto->getRoleIds();
            if ($roleIds) {

                // check if billing user role was added
                foreach($roleIds as $rid) {
                    $role = Role::find($rid);
                    if ($role && $role->slug === Role::ROLE_BILLING_USER_SLUG) {
                        $user->billing_user_id = null;
                        $user->save();
                    }
                }

                $this->userDataProvider->storeRolesForUser($user->getId(), $userDto->getRoleIds());
            }

            if (is_array($userDto->getProductIds())) {
                $this->userDataProvider->storeProductsForUser($user->getId(), $userDto->getProductIds());
            }

            if (!empty($userDto->getCompanyNumber())) {
                $this->updateCreditsafeInfo($userDto->getCompanyNumber(), $userId);
            }

            return $user;
        }

        return null;
    }

    /**
     * @throws RedisException
     * @throws Exception
     */
    public function importFromCsv(User $billingUser, UploadedFile $attachment): void
    {
        /** @var UserDto[] $dtoItems */
        $dtoItems = [];
        if (($handle = fopen($attachment->path(), "r")) !== false) {
            fgetcsv($handle, 1000, ",");
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                [$firstName, $lastName, $email, $phone, $username, $password, $postcode, $city, $addr1, $addr2, $roleSlug, $products, $isNational] = $data;

                if (!in_array($roleSlug, [Role::ROLE_USER_SLUG, Role::ROLE_COMPANY_SLUG])) {
                    throw new Exception("Not available user role for $username");
                }

                /** @var Role $role */
                $role = $this->userRoleDataProvider->getBySlug($roleSlug);
                if (!$role) {
                    throw new Exception("Wrong user role for $username");
                }

                /** @var []int $productIds */
                $productIds = array_filter(array_map(function (string $productName): ?int {
                    $product = $this->productDataProvider->getByName($productName);
                    return $product?->id;

                }, explode(",", $products)));

                $dtoItems[] = new UserDto(
                    $firstName,
                    $lastName,
                    $username,
                    $email,
                    $password,
                    [$role->id],
                    $postcode,
                    $phone,
                    '',
                    $city,
                    $addr1,
                    $addr2,
                    $productIds,
                    $billingUser->id,
                    null,
                    $isNational == 'Y'
                );
            }
            fclose($handle);
        }

        $this->importFromDtoForBillingUser($dtoItems);
    }

    /** @param UserDto[] $dtoItems
     * @throws Exception
     */
    private function importFromDtoForBillingUser(array $dtoItems): void
    {
        DB::beginTransaction();

        foreach ($dtoItems as $userDto) {
            $user = $this->getByUsername($userDto->getUsername());
            if ($user) {
                DB::rollBack();

                throw new Exception('Already exist ' . $userDto->getUsername());
            }

            $user = $this->store($userDto, false);
            if (!$user) {
                DB::rollBack();

                throw new Exception('Failed to create ' . $userDto->getUsername());
            }
        }

        DB::commit();
    }

    /**
     * @throws RedisException
     */
    public function getByUsername(string $username, bool $useCache = true): ?User
    {
        return $this->userDataProvider->getByUsername($username, $useCache);
    }

    /**
     * @throws RedisException
     */
    public function getByEmail(string $email, bool $useCache = true): ?User
    {
        return $this->userDataProvider->getByEmail($email, $useCache);
    }

    /**
     * @throws RedisException
     */
    public function store(UserDto $userDto, bool $notify = true, bool $onboarding = true): ?User
    {
        $user = $this->userDataProvider->store($userDto);

        if ($user) {
            $currentUser = $this->getCurrentUser();

            if ($currentUser) {
                $user->created_by = $currentUser->getId();
                $user->save();
            }

            if ($userDto->getRoleIds()) {
                $this->userDataProvider->storeRolesForUser($user->getId(), $userDto->getRoleIds());
            }

            if ($userDto->getProductIds()) {
                $this->userDataProvider->storeProductsForUser($user->getId(), $userDto->getProductIds());
            }

            if (!empty($userDto->getCompanyNumber())) {
                $this->updateCreditsafeInfo($userDto->getCompanyNumber(), $user->getId());
            }

            if ($onboarding) {
                $date = Carbon::createFromTimestamp(strtotime(UserOnboarding::ONBOARDING_INTERVAL))->format('Y-m-d');
                $onboarding = new UserOnboarding([
                    'user_id' => $user->getId(),
                    'account_setup' => 1,
                    'onboarding_end_date' => $date
                ]);

                $user->onboardingChecklist()->save($onboarding);
            }

            if ($notify) {
                Mail::to($user->getEmail())->send(new UserRegister($user, $userDto->getPassword()));
            }

            return $user;
        }

        return null;
    }

    public function getCurrentUser(): ?User
    {
        /** @var User $user */
        $user = auth()->user();
        if (!$user) {
            return null;
        }

        return $user;
    }

    /**
     * @throws RedisException
     */
    public function generateNewPassword(User $user): ?string
    {
        $user = $this->userDataProvider->getById($user->getId(), false);

        $randomPassword = Str::random(12);

        $user->setPassword($randomPassword);

        if ($user->save()) {
            return $randomPassword;
        }

        return null;
    }

    public function getContractorStats($searchParams): LengthAwarePaginator
    {
        $response = $this->userDataProvider->getContractorStats($searchParams);

        $response->getCollection()->transform(function (User $user) {
            /** @var Subscription $subscription */
            $subscription = $this->userDataProvider->getUserStripeSubscription($user->getBillingUserId() ?? $user->id);
            $subscriptionName = $this->subscriptionService->getStripeSubscriptionNameForSubscription($subscription);

            $record = $this->inquiryMerchantRepository->getRecord($user->id);
            $onboarding = $user->onboardingChecklist()->get()->first();

            return [
                "id" => $user->id,
                "company_name" => $user->company_name,
                "company_number" => $user->company_number,
                "email" => $user->email,
                "phone" => $user->phone,
                "billing_company" => $user->billing_company,
                "billing_email" => $user->billing_email,
                "billing_phone" => $user->billing_phone,
                "date_created" => $user->date_created,
                "last_enquiry_date" => $user->last_enquiry_date,
                "total_enquiries" => $user->total_enquiries,
                "pipeline_of_work" => $user->pipeline_of_work,
                "potential_users" => $user->potential_users,
                "turnover" => $user->turnover,
                "number_of_employees" => $user->number_of_employees,
                "onboarding_call" => $user->onboarding_call,
                "next_call" => $user->next_call,
                "pain_points" => $user->pain_points,
                "plan" => $subscriptionName,
                "plan_end_date" => $subscription->ends_at ?? $subscription->trial_ends_at ?? $user->trial_ends,
                "paid" => !!($subscription && $subscription->active() && !$subscription->onTrial()),
                'called_at' => ($record) ? $record->called_at : null,
                'comment' => ($record) ? $record->comment : '',
                "account_setup" => $onboarding->account_setup ?? 0,
                "billing_portal" => $onboarding->billing_portal ?? 0,
                "preferred_supplier" => $onboarding->preferred_supplier ?? 0,
                "customer_intro" => $onboarding->customer_intro ?? 0,
                "first_enquiry" => $onboarding->first_enquiry ?? 0,
                "line_of_credit" => $onboarding->line_of_credit ?? 0,
                "onboarding_end_date" => $onboarding->onboarding_end_date ?? ''
            ];
        });

        return $response;
    }

    public function getMerchantStats($searchParams): LengthAwarePaginator
    {
        $response = $this->userDataProvider->getMerchantStats($searchParams);

        $response->getCollection()->transform(function (User $user) {
            /** @var Subscription $subscription */
            $subscription = $this->userDataProvider->getUserStripeSubscription($user->getBillingUserId() ?? $user->id);
            $subscriptionName = $this->subscriptionService->getStripeSubscriptionNameForSubscription($subscription);

            $record = $this->inquiryMerchantRepository->getRecord($user->id);
            $onboarding = $user->onboardingChecklist()->get()->first();

            return [
                "id" => $user->id,
                "company_name" => $user->company_name,
                "company_number" => $user->company_number,
                "email" => $user->email,
                "phone" => $user->phone,
                "billing_company" => $user->billing_company,
                "billing_email" => $user->billing_email,
                "billing_phone" => $user->billing_phone,
                "date_created" => $user->date_created,
                "last_quote_date" => $user->last_quote_date,
                "total_quotes" => $user->total_quotes,
                "pipeline_of_work" => $user->pipeline_of_work,
                "potential_users" => $user->potential_users,
                "turnover" => $user->turnover,
                "number_of_employees" => $user->number_of_employees,
                "onboarding_call" => $user->onboarding_call,
                "next_call" => $user->next_call,
                "pain_points" => $user->pain_points,
                "plan" => $subscriptionName,
                "plan_end_date" => $subscription->ends_at ?? $subscription->trial_ends_at ?? $user->trial_ends,
                "paid" => !!($subscription && $subscription->active() && !$subscription->onTrial()),
                'called_at' => ($record) ? $record->called_at : null,
                'comment' => ($record) ? $record->comment : '',
                "account_setup" => $onboarding->account_setup ?? 0,
                "billing_portal" => $onboarding->billing_portal ?? 0,
                "preferred_supplier" => $onboarding->preferred_supplier ?? 0,
                "customer_intro" => $onboarding->customer_intro ?? 0,
                "first_enquiry" => $onboarding->first_enquiry ?? 0,
                "line_of_credit" => $onboarding->line_of_credit ?? 0,
                "onboarding_end_date" => $onboarding->onboarding_end_date ?? ''
            ];
        });

        return $response;
    }

    public function getManufacturerStats($searchParams): LengthAwarePaginator
    {
        $response = $this->userDataProvider->getManufacturerStats($searchParams);

        $response->getCollection()->transform(function (User $user) {
            /** @var Subscription $subscription */
            $subscription = $this->userDataProvider->getUserStripeSubscription($user->getBillingUserId() ?? $user->id);
            $subscriptionName = $this->subscriptionService->getStripeSubscriptionNameForSubscription($subscription);

            $record = $this->inquiryMerchantRepository->getRecord($user->id);
            $onboarding = $user->onboardingChecklist()->get()->first();

            return [
                "id" => $user->id,
                "company_name" => $user->company_name,
                "company_number" => $user->company_number,
                "email" => $user->email,
                "phone" => $user->phone,
                "billing_company" => $user->billing_company,
                "billing_email" => $user->billing_email,
                "billing_phone" => $user->billing_phone,
                "date_created" => $user->date_created,
                "pipeline_of_work" => $user->pipeline_of_work,
                "potential_users" => $user->potential_users,
                "turnover" => $user->turnover,
                "number_of_employees" => $user->number_of_employees,
                "onboarding_call" => $user->onboarding_call,
                "next_call" => $user->next_call,
                "pain_points" => $user->pain_points,
                "location" => $user->location,
                "banner" => $user->banner,
                "impressions" => $user->impressions,
                "clicks" => $user->clicks,
                "video_views" => $user->video_views,
                "plan" => $subscriptionName,
                "plan_end_date" => $subscription->ends_at ?? $subscription->trial_ends_at ?? $user->trial_ends,
                "paid" => !!($subscription && $subscription->active() && !$subscription->onTrial()),
                'called_at' => ($record) ? $record->called_at : null,
                'comment' => ($record) ? $record->comment : '',
                "account_setup" => $onboarding->account_setup ?? 0,
                "billing_portal" => $onboarding->billing_portal ?? 0,
                "preferred_supplier" => $onboarding->preferred_supplier ?? 0,
                "customer_intro" => $onboarding->customer_intro ?? 0,
                "first_enquiry" => $onboarding->first_enquiry ?? 0,
                "line_of_credit" => $onboarding->line_of_credit ?? 0,
                "onboarding_end_date" => $onboarding->onboarding_end_date ?? ''
            ];
        });

        return $response;
    }

    public function updateCreditsafeInfo(string $companyNumber, int $userId): void
    {
        try {
            $this->creditSafeService->getToken();
            $data = $this->creditSafeService->searchCompanies($companyNumber);

            if (!empty($data['companies'][0])) {
                $vatNo = $data['companies'][0]['vatNo'][0] ?? null;
                $companyId = $data['companies'][0]['id'];

                $data = $this->creditSafeService->getCreditReport($companyId);

                $internationalScore = $data['report']['creditScore']['currentCreditRating']['commonValue'] ?? null;
                $riskScore = $data['report']['creditScore']['currentCreditRating']['providerValue']['value'] ?? null;
                $creditLimit = $data['report']['creditScore']['currentCreditRating']['creditLimit']['value'] ?? null;
                $contractLimit = $data['report']['creditScore']['currentContractLimit']['value'] ?? null;
                $totalCcjs = $data['report']['negativeInformation']['ccjSummary']['numberOfExact'] ?? 0;

                $creditsafe = UserCreditsafe::updateOrCreate(
                    [
                        'user_id' => $userId
                    ],
                    [
                        'vat_no' => $vatNo,
                        'risk_score' => $riskScore,
                        'international_score' => $internationalScore,
                        'credit_limit' => $creditLimit,
                        'contract_limit' => $contractLimit,
                        'total_ccjs' => $totalCcjs,
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::debug($e->getMessage());
        }
    }

    public function generatePassword(int $length = 8): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $maxIndex = strlen($characters) - 1;
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $characters[random_int(0, $maxIndex)];
        }

        return $result;
    }


    /**
     * Validates whether the provided password adheres to the defined password policy.
     * The password policy requires:
     * - At least 12 characters in length.
     * - At least one uppercase letter.
     * - At least one lowercase letter.
     * - At least one numeric digit.
     * - At least one special character.
     *
     * @param string $password The password to validate.
     * @return bool True if the password meets the policy requirements, otherwise false.
     */
    public function validatePassword(string $password): bool
    {
        return strlen($password) >= 12
            && preg_match('/[A-Z]/', $password) === 1
            && preg_match('/[a-z]/', $password) === 1
            && preg_match('/[0-9]/', $password) === 1
            && preg_match('/[^A-Za-z0-9]/', $password) === 1;
    }
}
