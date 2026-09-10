<?php

namespace App\Http\Controllers;

use App\Dto\Review\ReviewDto;
use App\Dto\Review\SearchParamsDto;
use App\Repository\ReviewRepository;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReviewsController
{
    private UserService $userService;
    private ReviewRepository $reviewRepository;

    public function __construct(UserService $userService, ReviewRepository $reviewRepository)
    {
        $this->userService = $userService;
        $this->reviewRepository = $reviewRepository;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $reviews = $this->reviewRepository->find(SearchParamsDto::createFromRequest($request), $user);

        return new JsonResponse($reviews);
    }

    public function get(int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $review = $this->reviewRepository->get($id, $user);

        if (!$review) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($review);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $dto = ReviewDto::createFromRequest($request);
        $reviewId = $request->get('id');

        if (!empty($reviewId)) {
            $review = $this->reviewRepository->update($dto, $reviewId, $user);
        } else {
            $review = $this->reviewRepository->create($dto, $user);
        }

        return new JsonResponse($review);
    }

    public function delete(int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $review = $this->reviewRepository->get($id, $user);

        if (!$review) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $this->reviewRepository->delete($id);

        return new JsonResponse('Deleted', Response::HTTP_OK);
    }
}
