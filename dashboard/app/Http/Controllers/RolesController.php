<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataProvider\UserRoleDataProvider;
use App\Dto\SearchParamsDto;
use App\Models\Role;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RedisException;
use Symfony\Component\HttpFoundation\Response;

class RolesController extends Controller
{
    private UserRoleDataProvider $userRolesDataProvider;
    private UserService $userService;

    public function __construct(
        UserRoleDataProvider $userRoleDataProvider,
        UserService $userService
    ) {
        $this->userRolesDataProvider = $userRoleDataProvider;
        $this->userService = $userService;
    }

    /**
     * @throws RedisException
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse($this->userRolesDataProvider->find(
            SearchParamsDto::createFromRequest($request),
            false
        ));
    }

    public function getPermissions(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $roleId = $request->get('role_id');
        $roleId = !empty($roleId) ? (int) $roleId : null;

        return new JsonResponse($this->userRolesDataProvider->getPermissions($roleId));
    }

    public function updateRolePermissions(Request $request, int $roleId): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (!$user->hasRole(Role::ROLE_ADMIN_SLUG)) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $role = Role::find($roleId);
        $categories = $request->all();

        $data = [];

        foreach($categories as $category) {
            $data = array_merge($data, $category);
        }

        if (empty($role)) {
            return new JsonResponse('Role not found', Response::HTTP_NOT_FOUND);
        }

        if (empty($data)) {
            return new JsonResponse('Bad request', Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->userRolesDataProvider->updateRolePermissions($roleId, $data);
        } catch(\Exception $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(true);
    }
}
