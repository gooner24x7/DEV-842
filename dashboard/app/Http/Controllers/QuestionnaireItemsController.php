<?php


namespace App\Http\Controllers;

use App\Dto\Question\SearchParamsDto;
use App\Dto\Questionnaire\ItemDto;
use App\Repository\QuestionnaireItemRepository;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class QuestionnaireItemsController
{
    private UserService $userService;
    private QuestionnaireItemRepository $questionnaireItemRepository;

    public function __construct(UserService $userService, QuestionnaireItemRepository $questionnaireItemRepository)
    {
        $this->userService = $userService;
        $this->questionnaireItemRepository = $questionnaireItemRepository;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $worksPackageId = $request->get('worksPackageId') ?? null;

        return new JsonResponse($this->questionnaireItemRepository->find(
            SearchParamsDto::createFromRequest($request),
            $worksPackageId
        ));
    }

    public function get(int $id): JsonResponse
    {
        $item = $this->questionnaireItemRepository->get($id);
        if (!$item) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($item);
    }

    public function delete(int $id): JsonResponse
    {
        $item = $this->questionnaireItemRepository->get($id);
        if (!$item) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(
            $this->questionnaireItemRepository->delete($id)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $dto = ItemDto::createFromRequest($request, $user->getId());
        $item = $this->questionnaireItemRepository->store($dto);

        if ($item) {
            return new JsonResponse($item);
        }

        return new JsonResponse('failed to create the record', Response::HTTP_BAD_GATEWAY);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $dto = ItemDto::createFromRequest($request, $user->getId());

        $question = $this->questionnaireItemRepository->get($id);
        if (!$question) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        if ($this->questionnaireItemRepository->update($dto, $id)) {
            return new JsonResponse($question);
        }

        return new JsonResponse('failed to update the record', Response::HTTP_BAD_GATEWAY);
    }
}
