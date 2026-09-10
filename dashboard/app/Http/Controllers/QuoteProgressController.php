<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Dto\QuoteProgress\QuoteProgressDto;
use App\Dto\QuoteProgress\SearchParamsDto;
use App\Repository\QuoteProgressRepository;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class QuoteProgressController extends Controller
{
    private UserService $userService;
    private QuoteProgressRepository $quoteProgressRepository;

    public function __construct(UserService $userService, QuoteProgressRepository $quoteProgressRepository)
    {
        $this->userService = $userService;
        $this->quoteProgressRepository = $quoteProgressRepository;
    }

    public function index(int $quoteId, int $type, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $items = $this->quoteProgressRepository->find(
            SearchParamsDto::createFromRequest($request),
            $quoteId,
            $type
        );

        return new JsonResponse($items);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $dto = QuoteProgressDto::createFromRequest($request);

        $item = $this->quoteProgressRepository->store($dto, $user);

        return new JsonResponse($item);
    }

    public function complete(Request $request, int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $item = $this->quoteProgressRepository->getById($id);

        if (!$item) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $item = $this->quoteProgressRepository->complete($item);

        return new JsonResponse($item);
    }

    public function uncomplete(Request $request, int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $item = $this->quoteProgressRepository->getById($id);

        if (!$item) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $item = $this->quoteProgressRepository->uncomplete($item);

        return new JsonResponse($item);
    }

    public function delete(int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $item = $this->quoteProgressRepository->getById($id);

        if (!$item) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($item->delete());
    }
}
