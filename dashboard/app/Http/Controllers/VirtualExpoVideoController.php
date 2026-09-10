<?php

namespace App\Http\Controllers;

use App\DataProvider\VirtualExpoDataProvider;
use App\Dto\VirtualExpo\SearchParamsDto;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RedisException;
use Symfony\Component\HttpFoundation\Response;

class VirtualExpoVideoController extends Controller
{
    private VirtualExpoDataProvider $virtualExpoDataProvider;
    private UserService $userService;

    public function __construct(
        VirtualExpoDataProvider $virtualExpoDataProvider,
        UserService             $userService
    )
    {
        $this->virtualExpoDataProvider = $virtualExpoDataProvider;
        $this->userService = $userService;
    }

    /**
     * @throws RedisException
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if ($user === null) {
            return new JsonResponse('Unknown user', Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($this->virtualExpoDataProvider->findVideos(
            SearchParamsDto::createFromRequest($request),
            $id,
            $user,
            false
        ));
    }
}
