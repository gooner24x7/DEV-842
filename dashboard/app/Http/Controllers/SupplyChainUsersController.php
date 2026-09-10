<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Dto\SupplyChainUser\SearchParamsDto;
use App\Dto\SupplyChainUser\SupplyChainUserDto;
use App\Dto\User\UserDto;
use App\Repository\SupplyChainUserRepository;
use App\Service\SubscriptionService;
use App\Service\SupplyChainUserService;
use App\Service\UserService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Exceptions\PaymentActionRequired;
use Laravel\Cashier\Exceptions\PaymentFailure;
use Symfony\Component\HttpFoundation\Response;

class SupplyChainUsersController extends Controller
{
    private SupplyChainUserRepository $supplyChainUserRepository;
    private SupplyChainUserService $supplyChainUserService;
    private UserService $userService;
    private SubscriptionService $subscriptionService;

    public function __construct(
        SupplyChainUserRepository $supplyChainUserRepository,
        SupplyChainUserService $supplyChainUserService,
        UserService $userService,
        SubscriptionService $subscriptionService
    ) {
        $this->supplyChainUserRepository = $supplyChainUserRepository;
        $this->supplyChainUserService = $supplyChainUserService;
        $this->userService = $userService;
        $this->subscriptionService = $subscriptionService;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $searchParamsDto = SearchParamsDto::createFromRequest($request);

        $paginator = $this->supplyChainUserRepository->find($searchParamsDto, $user);

        return new JsonResponse($paginator);
    }

    public function get(int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $supplyChainUser = $this->supplyChainUserRepository->getUserById($id);

        return new JsonResponse($supplyChainUser);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $dto = SupplyChainUserDto::createFromRequest($request);
        $emailExists = $this->supplyChainUserService->emailExists($dto->getEmail());

        if ($emailExists) {
            return new JsonResponse('Email already exists', Response::HTTP_BAD_REQUEST);
        }

        $supplyChainUser = $this->supplyChainUserRepository->store($dto, $user);

        if (!$supplyChainUser) {
            return new JsonResponse('Error storing user', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($supplyChainUser);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $dto = SupplyChainUserDto::createFromRequest($request);
        $supplyChainUser = $this->supplyChainUserRepository->getUserById($id);

        if (!$supplyChainUser) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $supplyChainUser = $this->supplyChainUserRepository->update($supplyChainUser, $dto);

        return new JsonResponse($supplyChainUser);
    }

    public function delete(int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $supplyChainUser = $this->supplyChainUserRepository->getUserById($id);

        if (!$supplyChainUser) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($supplyChainUser->delete());
    }

    public function importCsv(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $requestArr = $request->all();
        $file = $requestArr['file'];
        $fileType = $file->getMimeType();

        if (empty($file)) {
            return new JsonResponse('Please upload a valid csv file', Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->supplyChainUserService->importCsv($file, $user);
            $message = ($result['total'] === 1) ? "{$result['total']} user was imported" : "{$result['total']} users were imported" . PHP_EOL;

            foreach($result['errors'] as $error) {
                $message .= $error . PHP_EOL;
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return new JsonResponse('An error occurred: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($message);
    }

    public function getCompanyNames(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $names = $this->supplyChainUserRepository->getCompanyNames($user);

        return new JsonResponse($names);
    }

    /**
     * @throws PaymentFailure
     * @throws PaymentActionRequired
     */
    public function convert(Request $request): JsonResponse
    {
        $currentUser = $this->userService->getCurrentUser();

        if (!$currentUser) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $supplyChainUserId = $request->get('id');
        $supplyChainUser = $this->supplyChainUserRepository->getUserById($supplyChainUserId);

        if (empty($supplyChainUser)) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $username = $request->get('username');
        $password = $request->get('password');
        $email = !empty($request->get('email')) ? $request->get('email') : $supplyChainUser->getEmail();

        if (empty($username) || empty($password)) {
            return new JsonResponse('Please enter username and password', Response::HTTP_BAD_REQUEST);
        }

        $existingUser = $this->userService->getByUsername($username, false);

        if (!empty($existingUser)) {
            return new JsonResponse('Username already exists', Response::HTTP_BAD_REQUEST);
        }

        // create billing user first
        $dtoBilling = UserDto::createFromArray([
            'first_name' => $supplyChainUser->getBusinessName() . ' Billing',
            'last_name' => $supplyChainUser->getLastName(),
            'email' => $email,
            'username' => $username . ' Billing',
            'password' => $password,
            'role_ids' => [5],
            'postcode' => $supplyChainUser->getPostcode(),
            'phone' => $supplyChainUser->getPhone(),
            'country' => $supplyChainUser->getCountry(),
            'city' => $supplyChainUser->getCity(),
            'addr_line_1' => $supplyChainUser->getAddressLine1(),
            'addr_line_2' => $supplyChainUser->getAddressLine2(),
            'user_plans' => ['free']
        ]);

        $billingUser = $this->userService->store($dtoBilling, false);
        $this->subscriptionService->setSubscriptionForUser($billingUser, $dtoBilling->getUserPlans(), $dtoBilling->getTrialEndsCarbon());

        if (empty($billingUser)) {
            return new JsonResponse('Error storing billing user', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // then create app user
        $dto = UserDto::createFromArray([
            'first_name' => $supplyChainUser->getBusinessName(),
            'last_name' => $supplyChainUser->getLastName(),
            'email' => $email,
            'username' => $username,
            'password' => $password,
            'role_ids' => [$supplyChainUser->getRoleId()],
            'postcode' => $supplyChainUser->getPostcode(),
            'phone' => $supplyChainUser->getPhone(),
            'country' => $supplyChainUser->getCountry(),
            'city' => $supplyChainUser->getCity(),
            'addr_line_1' => $supplyChainUser->getAddressLine1(),
            'addr_line_2' => $supplyChainUser->getAddressLine2(),
            'billing_user_id' => $billingUser->getId(),
        ]);

        $user = $this->userService->store($dto);

        if (empty($user)) {
            return new JsonResponse('Error storing user', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $supplyChainUser->setConvertedAt(Carbon::now());
        $supplyChainUser->save();

        return new JsonResponse($user);
    }

    public function getSubcontractors(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $searchParamsDto = \App\Dto\User\SearchParamsDto::createFromRequest($request);
        $items = $this->supplyChainUserRepository->getSubcontractors($user, $searchParamsDto);

        return new JsonResponse($items);
    }
}
