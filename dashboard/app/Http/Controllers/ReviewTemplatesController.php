<?php

namespace App\Http\Controllers;

use App\Dto\ReviewTemplate\ReviewTemplateDto;
use App\Dto\ReviewTemplate\SearchParamsDto;
use App\Models\Role;
use App\Models\User;
use App\Repository\ReviewTemplateRepository;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReviewTemplatesController
{
    private UserService $userService;
    private ReviewTemplateRepository $reviewTemplateRepository;

    public function __construct(UserService $userService, ReviewTemplateRepository $reviewTemplateRepository)
    {
        $this->userService = $userService;
        $this->reviewTemplateRepository = $reviewTemplateRepository;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $templates = $this->reviewTemplateRepository->find(SearchParamsDto::createFromRequest($request), $user);

        return new JsonResponse($templates);
    }

    public function get(int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $template = $this->reviewTemplateRepository->get($id, $user);

        if (!$template) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($template);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $dto = ReviewTemplateDto::createFromRequest($request);
        $templateId = $request->get('id');

        if (!empty($templateId)) {
            $template = $this->reviewTemplateRepository->update($dto, $templateId);
        } else {
            $template = $this->reviewTemplateRepository->create($dto, $user);
        }

        return new JsonResponse($template);
    }

    public function delete(int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $template = $this->reviewTemplateRepository->get($id, $user);

        if (!$template) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $this->reviewTemplateRepository->delete($id);

        return new JsonResponse('Deleted', Response::HTTP_OK);
    }

    public function options(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $options = $this->reviewTemplateRepository->getOptions($user);

        return new JsonResponse($options);
    }
}
