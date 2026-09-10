<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataProvider\UserDataProvider;
use App\Dto\Onboarding\ContractorOnboardingDto;
use App\Dto\Onboarding\ContractorOnboardingUserDto;
use App\Dto\SearchParamsDto;
use App\Dto\User\UserDto;
use App\Mail\OnboardingFormSaved;
use App\Models\Role;
use App\Repository\ContractorOnboardingRepository;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;

class ContractorOnboardingController extends Controller
{
    private UserService $userService;
    private UserDataProvider $userDataProvider;
    private ContractorOnboardingRepository $contractorOnboardingRepository;

    public function __construct(
        UserService $userService,
        UserDataProvider $userDataProvider,
        ContractorOnboardingRepository $contractorOnboardingRepository
    ) {
        $this->userService = $userService;
        $this->userDataProvider = $userDataProvider;
        $this->contractorOnboardingRepository = $contractorOnboardingRepository;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $searchParamsDto = SearchParamsDto::createFromRequest($request);

        $items = $this->contractorOnboardingRepository->find($searchParamsDto);

        return new JsonResponse($items);
    }

    public function get(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if ($user->hasRole(Role::ROLE_BILLING_USER_SLUG)) {
            $billingUserId = $user->getId();
        } else {
            $billingUserId = $user->getBillingUserId();
        }

        if (empty($billingUserId)) {
            return new JsonResponse('Billing user not found', Response::HTTP_NOT_FOUND);
        }

        $onboarding = $this->contractorOnboardingRepository->getByUserId($billingUserId);

        return new JsonResponse($onboarding);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if ($user->hasRole(Role::ROLE_BILLING_USER_SLUG)) {
            $billingUser = $user;
        } else {
            $billingUser = $this->userService->getById($user->getBillingUserId());
        }

        if (empty($billingUser)) {
            return new JsonResponse('Billing user not found', Response::HTTP_NOT_FOUND);
        }

        $dto = ContractorOnboardingDto::createFromRequest($request);
        $id = !empty($request->get('id')) ? (int) $request->get('id') : null;

        if ($id) {
            $response = $this->contractorOnboardingRepository->update($id, $billingUser, $dto);
        } else {
            $response = $this->contractorOnboardingRepository->create($billingUser, $dto);
        }

        //Mail::to('support@thebuildchain.co.uk')->send(new OnboardingFormSaved($user));

        return new JsonResponse($response);
    }

    public function delete(int $id): JsonResponse
    {
        $response = [];

        return new JsonResponse($response);
    }

    public function getUsers(Request $request): JsonResponse
    {
        $currentUser = $this->userService->getCurrentUser();
        if (empty($currentUser)) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if ($currentUser->hasRole(Role::ROLE_BILLING_USER_SLUG)) {
            $billingUserId = $currentUser->getId();
        } else {
            $billingUserId = $currentUser->getBillingUserId();
        }

        if (empty($billingUserId)) {
            return new JsonResponse('Billing user not found', Response::HTTP_NOT_FOUND);
        }

        $users = DB::table('users')
            ->leftJoin('contractor_onboarding_tasks', 'contractor_onboarding_tasks.user_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.first_name',
                'users.last_name',
                'users.job_title',
                'users.email',
                'users.phone',
                'users.alias',
                'users.can_check_competency',
                DB::raw("GROUP_CONCAT(contractor_onboarding_tasks.task SEPARATOR ',') as tasks")
            )
            ->where('users.billing_user_id', $billingUserId)
            ->groupBy(
                'users.id',
                'users.first_name',
                'users.last_name',
                'users.job_title',
                'users.email',
                'users.phone',
                'users.alias',
                'users.can_check_competency'
            )
            ->get();

        foreach($users as $user) {
            if (!empty($user->tasks)) {
                $user->tasks = explode(',', $user->tasks);
            }
        }

        return new JsonResponse($users);
    }

    public function createUser(Request $request): JsonResponse
    {
        $currentUser = $this->userService->getCurrentUser();
        if (empty($currentUser)) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $dto = ContractorOnboardingUserDto::createFromRequest($request);

        // check username
        $username = $dto->getFirstName() . ' ' . $dto->getLastName();
        $existingUser = $this->userService->getByUsername($username, false);

        if (!empty($existingUser)) {
            return new JsonResponse('Username already exists', Response::HTTP_BAD_REQUEST);
        }

        // fetch billing user
        $billingUser = $this->userService->getById($dto->getBillingUserId());
        if (empty($billingUser)) {
            return new JsonResponse('Billing user not found', Response::HTTP_NOT_FOUND);
        }

        // get company type from billing user
        $companyType = $billingUser->getCompanyType();
        $role = Role::where(['slug' => $companyType])->first();

        if (empty($role)) {
            return new JsonResponse('Company type not found', Response::HTTP_NOT_FOUND);
        }

        // check email domain
        $billingUserEmailDomain = explode('@', $billingUser->getEmail())[1];
        $emailDomain = explode('@', $dto->getEmail())[1];

        if ($billingUserEmailDomain !== $emailDomain) {
            return new JsonResponse('Email domain does not match', Response::HTTP_BAD_REQUEST);
        }

        // create new user
        $userDto = UserDto::createFromArray([
            'first_name' => $dto->getFirstName(),
            'last_name' => $dto->getLastName(),
            'email' => $dto->getEmail(),
            'username' => $username,
            'password' => $this->userService->generatePassword(),
            'role_ids' => [$role->getId()],
            'postcode' => $billingUser->getPostcode(),
            'phone' => $dto->getPhone(),
            'country' => $billingUser->getCountry(),
            'city' => $billingUser->getCity(),
            'addr_line_1' => $billingUser->getAddressLine1(),
            'addr_line_2' => $billingUser->getAddressLine2(),
            'billing_user_id' => $billingUser->getId(),
            'job_title' => $dto->getJobTitle(),
            'alias' => $dto->getAlias(),
            'can_check_competency' => $dto->getCanCheckCompetency(),
        ]);

        $user = $this->userService->store($userDto);

        // assign tasks
        $taskData = [];

        foreach($dto->getTasks() as $task) {
            $taskData[] = [
                'billing_user_id' => $billingUser->getId(),
                'user_id' => $user->getId(),
                'task' => $task
            ];
        }

        DB::table('contractor_onboarding_tasks')->insert($taskData);

        return new JsonResponse($user);
    }

    public function updateUser(int $id, Request $request): JsonResponse
    {
        $currentUser = $this->userService->getCurrentUser();
        if (empty($currentUser)) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $dto = ContractorOnboardingUserDto::createFromRequest($request);

        // fetch the user to update
        $user = $this->userService->getById($id);
        if (empty($user)) {
            return new JsonResponse('User not found', Response::HTTP_NOT_FOUND);
        }

        // fetch billing user
        $billingUser = $this->userService->getById($dto->getBillingUserId());
        if (empty($billingUser)) {
            return new JsonResponse('Billing user not found', Response::HTTP_NOT_FOUND);
        }

        // check email domain
        $billingUserEmailDomain = explode('@', $billingUser->getEmail())[1];
        $emailDomain = explode('@', $dto->getEmail())[1];

        if ($billingUserEmailDomain !== $emailDomain) {
            return new JsonResponse('Email domain does not match', Response::HTTP_BAD_REQUEST);
        }

        // reset cache before updating
        $this->userDataProvider->touch($id);

        // update user fields
        $user->first_name = $dto->getFirstName();
        $user->last_name = $dto->getLastName();
        $user->email = $dto->getEmail();
        $user->phone = $dto->getPhone();
        $user->job_title = $dto->getJobTitle();
        $user->alias = $dto->getAlias();
        $user->can_check_competency = $dto->getCanCheckCompetency();
        $user->save();

        // update tasks: remove old, insert new
        DB::table('contractor_onboarding_tasks')->where('user_id', $id)->delete();

        $taskData = [];
        foreach ($dto->getTasks() as $task) {
            $taskData[] = [
                'billing_user_id' => $billingUser->getId(),
                'user_id' => $user->getId(),
                'task' => $task,
            ];
        }

        if (!empty($taskData)) {
            DB::table('contractor_onboarding_tasks')->insert($taskData);
        }

        return new JsonResponse($user);
    }

    public function deleteUser(int $id): JsonResponse
    {
        $currentUser = $this->userService->getCurrentUser();
        if (empty($currentUser)) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $response = [];

        return new JsonResponse($response);
    }
}
