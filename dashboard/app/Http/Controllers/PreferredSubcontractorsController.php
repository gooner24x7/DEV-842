<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataProvider\PreferredSubcontractorDataProvider;
use App\Dto\SearchParamsDto;
use App\Exceptions\NotFoundException;
use App\Models\User;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RedisException;
use Symfony\Component\HttpFoundation\Response;

class PreferredSubcontractorsController extends Controller
{
    private PreferredSubcontractorDataProvider $preferredSubcontractorDataProvider;
    private UserService $userService;

    public function __construct(PreferredSubcontractorDataProvider $provider, UserService $userService)
    {
        $this->preferredSubcontractorDataProvider = $provider;
        $this->userService = $userService;
    }

    /**
     * @throws RedisException
     * @throws NotFoundException
     */
    public function index(Request $request, int $userId): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse($this->preferredSubcontractorDataProvider->find(
            SearchParamsDto::createFromRequest($request),
            $userId
        ));
    }

    /**
     * @throws RedisException
     */
    public function delete(Request $request, int $userId, int $subcontractorId): JsonResponse
    {
        $user = $this->userService->getById($userId);
        if (!$user) {
            return new JsonResponse('User Not Found', Response::HTTP_NOT_FOUND);
        }

        if ($this->preferredSubcontractorDataProvider->delete($user, $subcontractorId)) {
            return new JsonResponse('Success');
        }

        return new JsonResponse('Failed', Response::HTTP_BAD_REQUEST);
    }

    public function store(Request $request, int $userId): JsonResponse
    {
        $subcontractorId = $request->get('subcontractorId');
        if (!$subcontractorId) {
            return new JsonResponse('Wrong subcontractor id', Response::HTTP_BAD_REQUEST);
        }

        /** @var User $user */
        $user = $this->userService->getById($userId);
        if (!$user) {
            return new JsonResponse('User Not Found', Response::HTTP_NOT_FOUND);
        }

        if ($this->preferredSubcontractorDataProvider->store($user, $subcontractorId)) {
            return new JsonResponse('Success');
        }

        return new JsonResponse('Failed', Response::HTTP_BAD_REQUEST);
    }

    public function options(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $search = $request->get('search');

        return new JsonResponse($this->preferredSubcontractorDataProvider->getOptions($search));
    }
}
