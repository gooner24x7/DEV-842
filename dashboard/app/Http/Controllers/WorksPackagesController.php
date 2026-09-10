<?php

namespace App\Http\Controllers;

use App\Dto\SearchParamsDto;
use App\Exceptions\NotFoundException;
use App\Repository\WorksPackagesRepository;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response;

class WorksPackagesController
{
    private UserService $userService;
    private WorksPackagesRepository $worksPackagesRepository;

    public function __construct(
        UserService             $userService,
        WorksPackagesRepository $worksPackagesRepository
    ) {
        $this->userService = $userService;
        $this->worksPackagesRepository = $worksPackagesRepository;
    }

    public function index(int $projectId, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        //$items = $this->worksPackagesRepository->find($projectId, SearchParamsDto::createFromRequest($request));
        $items = $this->worksPackagesRepository->getTree($projectId);

        return new JsonResponse($items);
    }

    /**
     * @throws NotFoundException
     */
    public function create(int $projectId, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $name = $request->get('name');
        if (empty($name)) {
            return new JsonResponse('Bad request', Response::HTTP_BAD_REQUEST);
        }

        $templateId = $request->get('template_id');
        $parentId = $request->get('parent_id');
        $highRisk = $request->get('high_risk');

        $data = [
            'name' => $name,
            'project_id' => $projectId,
            'user_id' => $user->getId(),
            'template_id' => $templateId,
            'parent_id' => $parentId,
            'high_risk' => $highRisk,
        ];

        $worksPackage = $this->worksPackagesRepository->create($data);

        return new JsonResponse($worksPackage);
    }

    /**
     * @throws NotFoundException
     */
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $name = $request->get('name');
        if (empty($name)) {
            return new JsonResponse('Bad request', Response::HTTP_BAD_REQUEST);
        }

        $templateId = $request->get('template_id');
        $parentId = $request->get('parent_id');
        $highRisk = $request->get('high_risk');

        $data = [
            'name' => $name,
            'template_id' => $templateId,
            'parent_id' => $parentId,
            'high_risk' => $highRisk,
        ];

        $worksPackage = $this->worksPackagesRepository->update($id, $data);

        if (empty($worksPackage)) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($worksPackage);
    }

    public function delete(int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $worksPackage = $this->worksPackagesRepository->getById($id);

        if (empty($worksPackage)) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        if ($worksPackage->user_id !== $user->id) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $this->worksPackagesRepository->delete($worksPackage);

        return new JsonResponse('Deleted');
    }

    public function send(int $projectId, int $worksPackageId): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        Artisan::call('questionnaire:resend', [
            'project-id' => $projectId,
            'works-package-id' => $worksPackageId,
        ]);

        return new JsonResponse([]);
    }

    public function options(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $collection = $this->worksPackagesRepository->getWorksPackageOptions(
            $request->get('search') ?? '',
            $user,
            $request->get('projectIds') ?? [],
        );

        return new JsonResponse($collection);
    }

    public function storeAssignedUsers(int $worksPackageId, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $worksPackage = $this->worksPackagesRepository->getById($worksPackageId);
        if (!$worksPackage) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $userIds = $request->get('user_ids');
        if (!isset($userIds)) {
            return new JsonResponse('Bad request', Response::HTTP_BAD_REQUEST);
        }

        $result = $this->worksPackagesRepository->storeAssignedUsers($worksPackage, $userIds);
        if (!$result) {
            return new JsonResponse('Internal server error', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse();
    }

    public function getAssignedUsers(int $worksPackageId): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $assignedUsers = $this->worksPackagesRepository->getAssignedUsers($worksPackageId);

        return new JsonResponse($assignedUsers);
    }
}
