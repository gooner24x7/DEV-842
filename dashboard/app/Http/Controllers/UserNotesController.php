<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataProvider\UserNotesDataProvider;
use App\Dto\SearchParamsDto;
use App\Dto\UserNote\UserNoteDto;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RedisException;
use Symfony\Component\HttpFoundation\Response;

class UserNotesController extends Controller
{
    private UserNotesDataProvider $userNotesDataProvider;
    private UserService $userService;

    public function __construct(UserNotesDataProvider $userNotesDataProvider, UserService $userService)
    {
        $this->userNotesDataProvider = $userNotesDataProvider;
        $this->userService = $userService;
    }

    /**
     * @throws RedisException
     */
    public function index(Request $request, string $userId): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse($this->userNotesDataProvider->find(
            SearchParamsDto::createFromRequest($request),
            $user,
            (int)$userId
        ));
    }

    public function store(Request $request, string $userId): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse($this->userNotesDataProvider->store(
            UserNoteDto::createFromRequest($request)
                ->setUserId((int)$userId)
                ->setAuthorId($user->getId())
        ));
    }
}
