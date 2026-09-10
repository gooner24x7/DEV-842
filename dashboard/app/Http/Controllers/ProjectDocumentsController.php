<?php

namespace App\Http\Controllers;

use App\Models\Questionnaire\Project;
use App\Models\Questionnaire\ProjectDocumentCategory;
use App\Repository\ProjectDocumentsRepository;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProjectDocumentsController
{
    private UserService $userService;
    private ProjectDocumentsRepository $projectDocumentsRepository;

    public function __construct(UserService $userService, ProjectDocumentsRepository $projectDocumentsRepository)
    {
        $this->userService = $userService;
        $this->projectDocumentsRepository = $projectDocumentsRepository;
    }

    public function index(int $id, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $searchParams = [
            'search' => $request->get('search'),
            'category_id' => $request->get('category_id') ? (int) $request->get('category_id') : null,
            'group_id' => $request->get('group_id') ? (int) $request->get('group_id') : null,
        ];

        $project = Project::find($id);
        $category = $searchParams['category_id'] ? ProjectDocumentCategory::find($searchParams['category_id']) : null;

        if (empty($project)) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $items = $this->projectDocumentsRepository->find($id, $searchParams);

        if (empty($searchParams['group_id'])) {
            $items = $this->projectDocumentsRepository->groupDocuments($items);
        }

        return new JsonResponse([
            'project' => $project,
            'category' => $category,
            'documents' => $items,
        ]);
    }

    public function create(int $id, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $file = $request->file('file');
        $categoryId = $request->get('category_id') ? (int) $request->get('category_id') : null;
        $groupId = $request->get('group_id') ? (int) $request->get('group_id') : null;
        $description = $request->get('description') ? (string) $request->get('description') : null;

        if (!$request->hasFile('file')) {
            return new JsonResponse('Please select a file to upload', Response::HTTP_BAD_REQUEST);
        }

        $document = null;

        try {
            $originalName = $file->getClientOriginalName();
            //$withoutExt = preg_replace('/\.\w+$/', '', $originalName); // remove file extension
            $parts = explode('.', $originalName);
            $ext = array_pop($parts);
            $name = implode('.', $parts);
            $filename = $name . '_' . strtotime('now') . '.' . $ext;

            $path = $file->storeAs('public/projects/' . $id . '/', $filename);

            if ($path) {
                $document = $this->projectDocumentsRepository->create([
                    'group_id' => $groupId,
                    'project_id' => $id,
                    'user_id' => $user->getId(),
                    'name' => $documentName ?? $originalName,
                    'filename' => $filename,
                    'description' => $description,
                    'category_id' => $categoryId
                ]);
            }
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($document);
    }

    public function delete(int $projectId, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $id = $request->get('id') ? (int) $request->get('id') : null;
        $groupId = $request->get('group_id') ? (int) $request->get('group_id') : null;

        if ($groupId) {
            $group = $this->projectDocumentsRepository->getGroup($groupId);

            if (empty($group) || $group->getProjectId() !== $projectId) {
                return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
            }

            $result = $this->projectDocumentsRepository->deleteByGroupId($groupId);
        } else if ($id) {
            $document = $this->projectDocumentsRepository->get($id);

            if (empty($document) || $document->getProjectId() !== $projectId) {
                return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
            }

            $result = $this->projectDocumentsRepository->delete($document);
        } else {
            return new JsonResponse('Bad Request', Response::HTTP_BAD_REQUEST);
        }

        if (!$result) {
            return new JsonResponse('An error occurred', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(true);
    }

    public function getCategories(): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $categories = $this->projectDocumentsRepository->getCategories();

        return new JsonResponse($categories);
    }
}
