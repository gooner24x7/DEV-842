<?php

namespace App\Http\Controllers;

use App\Dto\ZohoDesk\CreateTicketRequestDto;
use App\Models\Role;
use App\Service\UserService;
use App\Service\ZohoDeskService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ZohoDeskController extends Controller
{
    private ZohoDeskService $zohoDeskService;
    private UserService $userService;

    public function __construct(ZohoDeskService $zohoDeskService, UserService $userService)
    {
        $this->zohoDeskService = $zohoDeskService;
        $this->userService = $userService;
    }

    /**
     * @throws Exception
     */
    public function create(Request $request): JsonResponse
    {
        $responseCreateTicketDto = $this->zohoDeskService->createTicket(
            CreateTicketRequestDto::createFromRequest($request)
        );

        return new JsonResponse($responseCreateTicketDto);
    }

    public function categoriesOptions(): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $categories = [
            [
                'id' => 'I cant login',
                'name' => 'I can\'t login',
            ],
        ];

        if ($user->hasRole(Role::ROLE_COMPANY_SLUG, Role::ROLE_MANUFACTURER)) {
            $categories[] = [
                'id' => 'I cant send a quote',
                'name' => 'I can\'t send a quote',
            ];
        }

        if ($user->hasRole(Role::ROLE_COMPANY_SLUG, Role::ROLE_MANUFACTURER, Role::ROLE_USER_SLUG)) {
            $categories[] = [
                'id' => 'I cant upload or download documents',
                'name' => 'I can\'t upload / download documents',
            ];
            $categories[] = [
                'id' => 'I cant chat',
                'name' => 'I can\'t chat',
            ];
            $categories[] = [
                'id' => 'I cant view enquiries',
                'name' => 'I can\'t view enquiries',
            ];
        }

        if ($user->hasRole(Role::ROLE_MANUFACTURER, Role::ROLE_USER_SLUG)) {
            $categories[] = [
                'id' => 'I cant archive or restore an enquiry',
                'name' => 'I can\'t archive / restore an enquiry',
            ];
        }

        if ($user->hasRole(Role::ROLE_COMPANY_SLUG, Role::ROLE_USER_SLUG)) {
            $categories[] = [
                'id' => 'I cant view quotes',
                'name' => 'I can\'t view quotes',
            ];
            $categories[] = [
                'id' => 'I cant upload preferred suppliers file',
                'name' => 'I can\'t upload preferred suppliers file',
            ];
        }

        $categories[] = [
            'id' => 'Other',
            'name' => 'Other',
        ];

        return new JsonResponse($categories);
    }
}
