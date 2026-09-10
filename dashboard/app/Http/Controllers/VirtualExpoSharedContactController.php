<?php

namespace App\Http\Controllers;

use App\DataProvider\VirtualExpoDataProvider;
use App\DataProvider\VirtualExpoSharedContactDataProvider;
use App\Dto\VirtualExpo\SharedContactSearchParamsDto;
use App\Dto\VirtualExpo\VirtualExpoSharedContactDto;
use App\Exceptions\NotFoundException;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Psr\SimpleCache\InvalidArgumentException;
use RedisException;
use Symfony\Component\HttpFoundation\Response;

class VirtualExpoSharedContactController extends Controller
{
    private VirtualExpoDataProvider $virtualExpoDataProvider;
    private VirtualExpoSharedContactDataProvider $virtualExpoSharedContactDataProvider;
    private UserService $userService;

    public function __construct(
        VirtualExpoDataProvider              $virtualExpoDataProvider,
        VirtualExpoSharedContactDataProvider $virtualExpoSharedContactDataProvider,
        UserService                          $userService
    )
    {
        $this->virtualExpoDataProvider = $virtualExpoDataProvider;
        $this->virtualExpoSharedContactDataProvider = $virtualExpoSharedContactDataProvider;
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

        return new JsonResponse($this->virtualExpoSharedContactDataProvider->find(
            SharedContactSearchParamsDto::createFromRequest($request),
            $user,
            false
        ));
    }

    public function delete(int $id): JsonResponse
    {
        try {
            return new JsonResponse($this->virtualExpoSharedContactDataProvider->delete($id));
        } catch (NotFoundException $e) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        } catch (InvalidArgumentException $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (RedisException $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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

        $manufacturerId = $request->get('manufacturerId');
        if ($manufacturerId) {
            $virtualExpo = $this->virtualExpoDataProvider->getLastByManufacturerId($manufacturerId);

            $sharedContact = $this->virtualExpoSharedContactDataProvider->create(new VirtualExpoSharedContactDto($virtualExpo->id, $user));

            return new JsonResponse($sharedContact);
        }

        $virtualExpoId = $request->get('virtual_expo_id');
        if (!$virtualExpoId) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $virtualExpo = $this->virtualExpoDataProvider->getById($virtualExpoId);
        if (!$virtualExpo) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $record = $this->virtualExpoSharedContactDataProvider->findByUserExpoId($user->getId(), $virtualExpoId, false);
        if ($record) {
            return new JsonResponse('Already exist', Response::HTTP_ALREADY_REPORTED);
        }

        $sharedContact = $this->virtualExpoSharedContactDataProvider->create(VirtualExpoSharedContactDto::createFromRequest($request, $user));

        return new JsonResponse($sharedContact);
    }
}
