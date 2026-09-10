<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataProvider\VirtualExpoDataProvider;
use App\Dto\VirtualExpo\SearchParamsDto;
use App\Dto\VirtualExpo\VirtualExpoDto;
use App\Exceptions\InvalidRequestException;
use App\Exceptions\NotFoundException;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Psr\SimpleCache\InvalidArgumentException;
use RedisException;
use Symfony\Component\HttpFoundation\Response;

class VirtualExpoController extends Controller
{
    private VirtualExpoDataProvider $virtualExpoDataProvider;
    private UserService $userService;

    public function __construct(
        VirtualExpoDataProvider $virtualExpoDataProvider,
        UserService             $userService
    )
    {
        $this->virtualExpoDataProvider = $virtualExpoDataProvider;
        $this->userService = $userService;
    }

    /**
     * @throws RedisException
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if ($user === null) {
            return new JsonResponse('Unknown user', Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($this->virtualExpoDataProvider->find(
            SearchParamsDto::createFromRequest($request),
            $user,
            [],
            false
        ));
    }

    /**
     * @throws RedisException
     */
    public function getList(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if ($user === null) {
            return new JsonResponse('Unknown user', Response::HTTP_BAD_REQUEST);
        }

        $matchingProducts = $request->query->getBoolean('matchingProducts');
        $onlyGold = $request->query->getBoolean('onlyGold');

        $items = $this->virtualExpoDataProvider->list(
            new SearchParamsDto('id', true, false, null, 1, true),
            $user,
            $matchingProducts,
            $onlyGold,
            false
        );

        if ($items->count() <= 0) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($items);
    }

    /**
     * @throws RedisException
     */
    public function getRandom(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if ($user === null) {
            return new JsonResponse('Unknown user', Response::HTTP_BAD_REQUEST);
        }

        $productIds = [];
        if ($request->get('productIds')) {
            $productIds = json_decode($request->get('productIds'));
        }

        $item = $this->virtualExpoDataProvider->find(
            new SearchParamsDto('id', true, false, null, 1, true),
            $user,
            $productIds,
            false
        );


        if ($item->count() <= 0) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $index = mt_rand(0, $item->count() - 1);

        return new JsonResponse($item->get($index));
    }

    /**
     * @throws RedisException
     */
    public function get(int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if ($user === null) {
            return new JsonResponse('Unknown user', Response::HTTP_BAD_REQUEST);
        }

        $item = $this->virtualExpoDataProvider->getById($id, false);

        return new JsonResponse($item->toArray());
    }

    /**
     * @throws RedisException
     */
    public function store(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if ($user === null) {
            return new JsonResponse('Unknown user', Response::HTTP_BAD_REQUEST);
        }

        try {
            $dto = VirtualExpoDto::createFromRequest($request, $user);
        } catch (InvalidRequestException $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($this->virtualExpoDataProvider->create($dto));
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if ($user === null) {
            return new JsonResponse('Unknown user', Response::HTTP_BAD_REQUEST);
        }

        try {
            return new JsonResponse($this->virtualExpoDataProvider->update(
                $id,
                VirtualExpoDto::createFromRequest($request, $user)
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
            return new JsonResponse($this->virtualExpoDataProvider->delete($id));
        } catch (NotFoundException $e) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        } catch (InvalidArgumentException $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (RedisException $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateCheckbox(Request $request, int $id): JsonResponse
    {
        $key = $request->get('key');
        $value = $request->get('value');

        $expo = $this->virtualExpoDataProvider->getById($id, false);

        if (!$expo) {
            return new JsonResponse('Virtual Expo not found', Response::HTTP_NOT_FOUND);
        }

        $result = $expo->update([$key => $value]);

        return new JsonResponse($result);
    }
}
