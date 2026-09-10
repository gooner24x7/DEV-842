<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Dto\SearchParamsDto;
use App\Dto\User\UserDto;
use App\Exceptions\NotFoundException;
use App\Models\PermissionsReference;
use App\Models\UserOnboarding;
use App\Repository\InquiryMerchantRepository;
use App\Repository\ManufacturerImportedProductsRepository;
use App\Repository\UserRepository;
use App\Service\SubscriptionService;
use App\Service\UserService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Laravel\Cashier\Exceptions\PaymentActionRequired;
use Laravel\Cashier\Exceptions\PaymentFailure;
use Symfony\Component\HttpFoundation\Response;

class UsersController extends Controller
{
    private UserService $userService;
    private SubscriptionService $subscriptionService;
    private ManufacturerImportedProductsRepository $manufacturerImportedProductsRepository;
    private InquiryMerchantRepository $inquiryMerchantRepository;
    private UserRepository $userRepository;

    public function __construct(
        UserService                            $userService,
        SubscriptionService                    $subscriptionService,
        ManufacturerImportedProductsRepository $manufacturerImportedProductsRepository,
        InquiryMerchantRepository              $inquiryMerchantRepository,
        UserRepository                         $userRepository
    ) {
        $this->userService = $userService;
        $this->subscriptionService = $subscriptionService;
        $this->manufacturerImportedProductsRepository = $manufacturerImportedProductsRepository;
        $this->inquiryMerchantRepository = $inquiryMerchantRepository;
        $this->userRepository = $userRepository;
    }

    public function index(Request $request): JsonResponse
    {
        $billingUserId = request()->get('billing_user_id');
        $roleId = request()->get('role_id');
        $searchParams = SearchParamsDto::createFromRequest($request);
        $billingUserId = ($billingUserId) ? (int)$billingUserId : null;

        if ($billingUserId) {
            if (!$this->userService->getById($billingUserId)) {
                return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
            }
        }

        $response = $this->userService->getUsersWithDetails($searchParams, $billingUserId, $roleId, false);

        return new JsonResponse($response);
    }

    public function get(int $id): JsonResponse
    {
        $user = $this->userService->getById($id, false);
        if (!$user) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($user);
    }

    public function billingUserBranches(int $id, Request $request): JsonResponse
    {
        $searchParams = SearchParamsDto::createFromRequest($request);

        if ($id) {
            if (!$this->userService->getById($id)) {
                return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
            }
        }

        $response = $this->userService->getUsersWithDetails($searchParams, $id, null, true);

        return new JsonResponse($response);
    }

    public function searchByRole(Request $request, string $roleSlug): JsonResponse
    {
        try {
            return new JsonResponse($this->userService->searchUsersByRole(
                $roleSlug,
                ($request->get('search') ?? ''),
                $request->get('productIds') ?? [],
            ));
        } catch (NotFoundException $e) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }
    }

    public function getManufacturerProducts(int $id, Request $request): JsonResponse
    {
        $params = SearchParamsDto::createFromRequest($request);

        return new JsonResponse($this->manufacturerImportedProductsRepository->find($params, $id));
    }

    public function getManufacturerByProductId(int $id, Request $request): JsonResponse
    {
        return new JsonResponse($this->manufacturerImportedProductsRepository->getManufacturerByProductId($id));
    }

    /**
     * @throws PaymentFailure
     * @throws PaymentActionRequired
     */
    public function store(Request $request): JsonResponse
    {
        $currentUser = $this->userService->getCurrentUser();

        if (empty($currentUser)) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $dto = UserDto::createFromRequest($request);
        $user = $this->userService->store($dto);

        if (empty($user)) {
            return new JsonResponse(['message' => 'failed to create the user'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $userPlans = $dto->getUserPlans();

        if (!empty($userPlans) && $currentUser->hasPermission('manage_subscriptions')) {
            $this->subscriptionService->setSubscriptionForUser($user, $userPlans, $dto->getTrialEndsCarbon());
        }

        return new JsonResponse($user);
    }

    public function delete(int $id): JsonResponse
    {
        $currentUser = $this->userService->getCurrentUser();

        if (empty($currentUser)) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $user = $this->userService->getById($id);
        if (!$user) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->userService->deleteUser($user->getId()));
    }

    public function importCsvForBillingUser(int $id, Request $request): JsonResponse
    {
        $user = $this->userService->getById($id);
        if (!$user) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $attachment = $request->file('attachment');
        if (!$attachment) {
            return new JsonResponse('Wrong request', Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->userService->importFromCsv($user, $attachment);
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse();
    }

    public function contractorsStats(Request $request): JsonResponse
    {
        $searchParams = SearchParamsDto::createFromRequest($request);

        $response = $this->userService->getContractorStats($searchParams);

        return new JsonResponse($response);
    }

    public function merchantsStats(Request $request): JsonResponse
    {
        $searchParams = SearchParamsDto::createFromRequest($request);

        $response = $this->userService->getMerchantStats($searchParams);

        return new JsonResponse($response);
    }

    public function manufacturersStats(Request $request): JsonResponse
    {
        $searchParams = SearchParamsDto::createFromRequest($request);

        $response = $this->userService->getManufacturerStats($searchParams);

        return new JsonResponse($response);
    }

    public function called(Request $request, string $uid): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (!$user->can(PermissionsReference::manageUsers) &&
            !$user->hasRole(\App\Models\Role::ROLE_CUSTOMER_SUCCESS_ADMIN)
        ) {
            return new JsonResponse('Forbidden', Response::HTTP_FORBIDDEN);
        }

        $comment = (string)$request->get('comment');

        $response = $this->inquiryMerchantRepository->setCalled((int)$uid, $comment, $user);

        return new JsonResponse($response);
    }

    public function updateCsp(Request $request, int $id): JsonResponse
    {
        $data = [
            'pipeline_of_work' => $request->get('pipeline_of_work'),
            'potential_users' => $request->get('potential_users'),
            'turnover' => $request->get('turnover'),
            'number_of_employees' => $request->get('number_of_employees'),
            'onboarding_call' => $request->get('onboarding_call'),
            'next_call' => $request->get('next_call'),
            'pain_points' => $request->get('pain_points'),
            'onboarding_end_date' => $request->get('onboarding_end_date'),
        ];

        $user = $this->userService->getById($id, false);
        if (!$user) {
            return new JsonResponse('User not found', Response::HTTP_NOT_FOUND);
        }

        $user = $this->userRepository->updateCsp($user->getId(), $data);

        return new JsonResponse($user);
    }

    public function updateOnboardingChecklist(Request $request, int $id): JsonResponse
    {
        $key = $request->get('key');
        $value = $request->get('value');

        $user = $this->userService->getById($id, false);

        if (!$user) {
            return new JsonResponse('User not found', Response::HTTP_NOT_FOUND);
        }

        $oc = $user->onboardingChecklist()->get()->first();

        if (empty($oc)) {
            $date = Carbon::createFromTimestamp(strtotime(UserOnboarding::ONBOARDING_INTERVAL))->format('Y-m-d');
            $onboarding = new UserOnboarding(['user_id' => $user->id, 'onboarding_end_date' => $date]);

            $user->onboardingChecklist()->save($onboarding);
        }

        $result = $user->onboardingChecklist()->update([$key => $value]);

        return new JsonResponse($result);
    }

    /**
     * @throws PaymentFailure
     * @throws PaymentActionRequired
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $currentUser = $this->userService->getCurrentUser();

        if (empty($currentUser)) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $dto = UserDto::createFromRequest($request, $id);
        $user = $this->userService->getById($id, false);

        if (empty($user)) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $user = $this->userService->update($user->getId(), $dto);
        $userPlans = $dto->getUserPlans();

        if (empty($user)) {
            return new JsonResponse('failed updating the user', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        if (!empty($userPlans) && $currentUser->hasPermission('manage_subscriptions')) {
            $this->subscriptionService->setSubscriptionForUser($user, $userPlans, $dto->getTrialEndsCarbon());
        }

        return new JsonResponse($user);
    }

    public function getOnboardingChecklist(int $id): JsonResponse
    {
        $user = $this->userService->getById($id, false);

        if (!$user) {
            return new JsonResponse('User not found', Response::HTTP_NOT_FOUND);
        }

        $oc = $user->onboardingChecklist()->get()->first();

        return new JsonResponse($oc);
    }

    public function getCreditsafeInfo(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $userIds = $request->get('user_ids', []);

        if (empty($userIds)) {
            return new JsonResponse('Bad request', Response::HTTP_BAD_REQUEST);
        }

        $data = [];

        foreach ($userIds as $userId) {
            $user = $this->userService->getById((int) $userId);

            if (!empty($user)) {
                $creditsafe = $user->creditsafeInfo()->get()->first();

                if (empty($creditsafe)) {
                    continue;
                }

                $creditsafe = $creditsafe->toArray();

                $data[$user->getId()] = [
                    'Company Number' => $user->getCompanyNumber(),
                    'VAT Number' => $creditsafe['vat_no'],
                    'Risk Score' => $creditsafe['risk_score'],
                    'International Score' => $creditsafe['international_score'],
                    'Credit Limit' => is_numeric($creditsafe['credit_limit']) ? '£' . number_format((float)$creditsafe['credit_limit'], 2) : $creditsafe['credit_limit'],
                    'Contract Limit' => is_numeric($creditsafe['contract_limit']) ? '£' . number_format((float)$creditsafe['contract_limit'], 2) : $creditsafe['contract_limit'],
                    'Total CCJs' => $creditsafe['total_ccjs']
                ];
            }
        }

        return new JsonResponse($data);
    }

    public function updateQuestionnaireSessions(Request $request): JsonResponse
    {
        $userId = !empty($request->get('id')) ? (int) $request->get('id') : null;
        if (empty($userId)) {
            return new JsonResponse('Bad request', Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userService->getById($userId, false);
        if (!$user) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        Artisan::call('questionnaire:update-session-supply-fit-user', ['id' => $user->getId()]);

        return new JsonResponse([]);
    }
}
