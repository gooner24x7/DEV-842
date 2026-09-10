<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataProvider\UserRoleDataProvider;
use App\Dto\User\UserDto;
use App\Kafka\Dto\OrganisationDto;
use App\Models\Role;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CrmController extends Controller
{
    private UserService $userService;
    private UserRoleDataProvider $userRoleDataProvider;

    public function __construct(UserService $userService, UserRoleDataProvider $userRoleDataProvider)
    {
        $this->userService = $userService;
        $this->userRoleDataProvider = $userRoleDataProvider;
    }

    public function publishOrganisation(Request $request): JsonResponse
    {
        $result = [];

        $dto = OrganisationDto::createFromArray(json_decode($request->getContent(), true));

        $billingUserRole = $this->userRoleDataProvider->getBySlug(Role::ROLE_BILLING_USER_SLUG);
        $companyUserRole = $this->userRoleDataProvider->getBySlug(Role::ROLE_COMPANY_SLUG);

        if (!$billingUserRole || !$companyUserRole) {
            die('failed to find user roles');
        }

        $passwordBillingUser = Hash::make(Str::random(8));
        $userDto = new UserDto(
            $dto->getName(),
            $dto->getName(),
            'bl-' . UserService::slugify($dto->getName()),
            $dto->getEmail(),
            $passwordBillingUser,
            [
                $billingUserRole->getId(),
            ],
            $dto->getPostcode(),
            $dto->getPhone(),
            $dto->getCountry(),
            $dto->getCity(),
            $dto->getAddressLine1(),
            $dto->getAddressLine2(),
            [],
            null,
            [],
            false,
            null,
            null
        );

        $billingUser = $this->userService->store($userDto);
        if (!$billingUser) {
            Log::debug('failed to create billing user', [
                'email' => $userDto->getEmail(),
            ]);

            return new JsonResponse();
        }

        $result['billing_user'] = [
            'id' => $dto->getId(),
            'ext_id' => $billingUser->getId(),
        ];

        $result['branches'] = [];

        //create companies
        foreach ($dto->getBranches() as $branch) {
            $passwordCompany = Hash::make(Str::random(8));

            $userDto = new UserDto(
                $branch->getFirstName(),
                $branch->getLastName(),
                'us-' . UserService::slugify($dto->getName()),
                $branch->getEmail(),
                $passwordCompany,
                [
                    $companyUserRole->getId()
                ],
                $dto->getPostcode(),
                $dto->getPhone(),
                $dto->getCountry(),
                $dto->getCity(),
                $dto->getAddressLine1(),
                $dto->getAddressLine2(),
                [],
                $billingUser->getId(),
                [],
                false,
                null,
                null
            );

            $companyUser = $this->userService->store($userDto);
            if (!$companyUser) {
                Log::debug('failed to create company user', [
                    'email' => $userDto->getEmail(),
                ]);
            }

            $result['branches'] = [
                'id' => $branch->getId(),
                'ext_id' => $companyUser->getId(),
            ];
        }

        return new JsonResponse($result);
    }
}
