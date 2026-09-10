<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Dto\SearchParamsDto;
use App\Repository\WorksPackageQuestionRepository;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WorksPackageQuestionsController extends Controller
{
    private UserService $userService;
    private WorksPackageQuestionRepository $worksPackageQuestionRepository;

    public function __construct(UserService $userService, WorksPackageQuestionRepository $worksPackageQuestionRepository)
    {
        $this->userService = $userService;
        $this->worksPackageQuestionRepository = $worksPackageQuestionRepository;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $wpIds = $request->get('worksPackageIds');

        if (empty($wpIds)) {
            return new JsonResponse('Bad request', Response::HTTP_BAD_REQUEST);
        }

        $items = $this->worksPackageQuestionRepository->find(SearchParamsDto::createFromRequest($request), $wpIds);

        return new JsonResponse($items);
    }

    public function store(int $id, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $qid = $request->get('qid');
        $question = $request->get('question');
        $answer = $request->get('answer');

        if (!empty($qid)) {
            $item = $this->worksPackageQuestionRepository->update($qid, $user, $answer);
        } else {
            $item = $this->worksPackageQuestionRepository->store($id, $user, $question);
        }

        return new JsonResponse($item);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }



        if (empty($id)) {
            return new JsonResponse('Bad request', Response::HTTP_BAD_REQUEST);
        }

        $item = $this->worksPackageQuestionRepository->update($qid, $question, $answer, $user);

        return new JsonResponse($item);
    }

    public function delete(int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $item = $this->worksPackageQuestionRepository->getById($id);

        if (!$item) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($item->delete());
    }
}
