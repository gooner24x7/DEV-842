<?php

namespace App\Http\Controllers;

use App\Dto\SearchParamsDto;
use App\Repository\ProjectTimeTrackingRepository;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProjectTimeTrackingController
{
    private UserService $userService;
    private ProjectTimeTrackingRepository $projectTimeTrackingRepository;

    public function __construct(UserService $userService, ProjectTimeTrackingRepository $projectTimeTrackingRepository)
    {
        $this->userService = $userService;
        $this->projectTimeTrackingRepository = $projectTimeTrackingRepository;
    }

    public function index(Request $request, int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (empty($id)) {
            return new JsonResponse('Project id is required', Response::HTTP_BAD_REQUEST);
        }

        $response = $this->projectTimeTrackingRepository->find(SearchParamsDto::createFromRequest($request), $id);

        return new JsonResponse($response);
    }

    public function store(Request $request, int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (empty($id)) {
            return new JsonResponse('Project id is required', Response::HTTP_BAD_REQUEST);
        }

        $data = [
            'project_id' => $id,
            'user_id' => $user->getId(),
            'hours_ap' => $request->get('hours_ap'),
            'hours_se' => $request->get('hours_se'),
            'spend_se' => $request->get('spend_se'),
            'name_se' => $request->get('name_se'),
        ];

        $timeTracking = $this->projectTimeTrackingRepository->create($data);

        return new JsonResponse($timeTracking);
    }
}
