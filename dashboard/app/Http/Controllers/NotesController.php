<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Dto\Note\NoteDto;
use App\Dto\Note\SearchParamsDto;
use App\Repository\NoteRepository;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NotesController extends Controller
{
    private UserService $userService;
    private NoteRepository $noteRepository;

    public function __construct(UserService $userService, NoteRepository $noteRepository)
    {
        $this->userService = $userService;
        $this->noteRepository = $noteRepository;
    }

    public function index(int $type, int $parentId, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $items = $this->noteRepository->find(SearchParamsDto::createFromRequest($request), $parentId, $type);

        return new JsonResponse($items);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $item = $this->noteRepository->store(NoteDto::createFromRequest($request), $user);

        return new JsonResponse($item);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $id = (int) $request->get('id');
        $message = $request->get('message');

        if (empty($id)) {
            return new JsonResponse('Bad request', Response::HTTP_BAD_REQUEST);
        }

        $item = $this->noteRepository->update($id, $message);

        return new JsonResponse($item);
    }

    public function delete(int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $item = $this->noteRepository->getById($id);

        if (!$item) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($item->delete());
    }
}
