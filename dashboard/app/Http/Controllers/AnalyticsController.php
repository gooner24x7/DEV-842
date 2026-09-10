<?php

namespace App\Http\Controllers;

use App\Dto\Analytics\EventDto;
use App\Repository\AnalyticsRepository;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AnalyticsController extends Controller
{
    private UserService $userService;
    private AnalyticsRepository $repository;

    public function __construct(UserService $userService, AnalyticsRepository $repository)
    {
        $this->userService = $userService;
        $this->repository = $repository;
    }

    public function newEvent(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $dto = EventDto::createFromRequest($request);
        $dto->setUserId($user->id);

        $event = $this->repository->newEvent($dto);

        return new JsonResponse($event);
    }
}
