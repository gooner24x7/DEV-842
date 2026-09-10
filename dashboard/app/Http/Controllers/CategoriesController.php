<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataProvider\CategoryDataProvider;
use App\Dto\Category\CategoryDto;
use App\Dto\SearchParamsDto;
use App\Exceptions\InvalidRequestException;
use App\Exceptions\NotFoundException;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RedisException;
use Symfony\Component\HttpFoundation\Response;

class CategoriesController extends Controller
{
    private CategoryDataProvider $categoryDataProvider;
    private UserService $userService;

    public function __construct(CategoryDataProvider $categoryDataProvider, UserService $userService)
    {
        $this->categoryDataProvider = $categoryDataProvider;
        $this->userService = $userService;
    }

    /**
     * @throws RedisException
     */
    public function index(Request $request): JsonResponse
    {
        return new JsonResponse($this->categoryDataProvider->find(
            SearchParamsDto::createFromRequest($request), false
        ));
    }

    /**
     * @throws RedisException
     */
    public function options(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $search = $request->get('search') ?? '';

        return new JsonResponse(
            $this->categoryDataProvider->getSelectOptions($user->getId(), $search, false)
        );
    }

    public function tree(Request $request): JsonResponse
    {
        return new JsonResponse(
            $this->categoryDataProvider->getTree(false)
        );
    }

    /**
     * @throws RedisException
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $dto = CategoryDto::createFromRequest($request);
        } catch (InvalidRequestException $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($this->categoryDataProvider->create($dto));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            return new JsonResponse($this->categoryDataProvider->update(
                $id,
                CategoryDto::createFromRequest($request)
            ));
        } catch (InvalidRequestException $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (NotFoundException $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (RedisException $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete(int $id): JsonResponse
    {
        try {
            return new JsonResponse($this->categoryDataProvider->delete($id));
        } catch (NotFoundException $e) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        } catch (\Psr\SimpleCache\InvalidArgumentException|\RedisException  $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
