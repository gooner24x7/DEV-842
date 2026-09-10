<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataProvider\PartnerDataProvider;
use App\Dto\SearchParamsDto;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PartnersController extends Controller
{
    private PartnerDataProvider $partnerDataProvider;
    private UserService $userService;

    public function __construct(PartnerDataProvider $partnerDataProvider, UserService $userService)
    {
        $this->partnerDataProvider = $partnerDataProvider;
        $this->userService = $userService;
    }

    /**
     * @throws \RedisException
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse($this->partnerDataProvider->find(
            SearchParamsDto::createFromRequest($request)
        ));
    }
}
