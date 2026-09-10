<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataProvider\ProductDataProvider;
use App\DataProvider\QuestionDataProvider;
use App\Dto\Product\ProductDto;
use App\Dto\SearchParamsDto;
use App\Exceptions\InvalidRequestException;
use App\Exceptions\NotFoundException;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Psr\SimpleCache\InvalidArgumentException;
use RedisException;
use Symfony\Component\HttpFoundation\Response;

class ProductsController extends Controller
{
    private QuestionDataProvider $questionDataProvider;
    private ProductDataProvider $productDataProvider;
    private UserService $userService;

    public function __construct(
        QuestionDataProvider $questionDataProvider,
        ProductDataProvider  $productDataProvider,
        UserService          $userService
    )
    {
        $this->questionDataProvider = $questionDataProvider;
        $this->productDataProvider = $productDataProvider;
        $this->userService = $userService;
    }

    /**
     * @throws RedisException
     */
    public function index(Request $request): JsonResponse
    {
        return new JsonResponse($this->productDataProvider->find(
            SearchParamsDto::createFromRequest($request),
            false
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

        $typeIds = (array)($request->get('type_ids') ?? array());

        return new JsonResponse(
            $this->productDataProvider->getSelectOptions($user->getId(), $typeIds, false)
        );
    }

    /**
     * @throws RedisException
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $dto = ProductDto::createFromRequest($request);
        } catch (InvalidRequestException $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($this->productDataProvider->create($dto));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            return new JsonResponse($this->productDataProvider->update(
                $id,
                ProductDto::createFromRequest($request)
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
        if ($this->questionDataProvider->countByProductId($id) > 0) {
            return new JsonResponse('Not allowed', Response::HTTP_NOT_ACCEPTABLE);
        }

        try {
            return new JsonResponse($this->productDataProvider->delete($id));
        } catch (NotFoundException $e) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        } catch (InvalidArgumentException|RedisException $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
