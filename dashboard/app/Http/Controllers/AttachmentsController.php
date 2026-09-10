<?php

namespace App\Http\Controllers;

use App\Service\UserService;
use Bnb\Laravel\Attachments\Attachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AttachmentsController
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $type = $request->get('type');
        $parentId = $request->get('parent_id');

        $model = "App\Models\\$type";

        if (empty($parentId) || empty($type) || !class_exists($model)) {
            return new JsonResponse('Bad request', Response::HTTP_BAD_REQUEST);
        }

        try {
            $items = $model::find($parentId)->attachments;

            if ($request->has('description')) {
                $description = $request->get('description');

                $items = array_values(array_filter($items, function ($item) use ($description) {
                    if ($description === '' || $description === null) {
                        return $item['description'] === '' || $item['description'] === null;
                    }

                    return $item['description'] === $description;
                }));
            }
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($items);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $type = $request->get('type');
        $parentId = $request->get('parent_id');
        $description = $request->get('description');

        $model = "App\Models\\$type";

        if (empty($parentId) || empty($type) || !class_exists($model)) {
            return new JsonResponse('Bad request', Response::HTTP_BAD_REQUEST);
        }

        $files = $request->file('files');

        if (empty($files)) {
            return new JsonResponse('Please select at least one file to upload', Response::HTTP_BAD_REQUEST);
        }

        try {
            foreach($files as $file) {
                $model::find($parentId)->attach($file, ['description' => $description]);
            }
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(true);
    }

    public function delete(int $id, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if(empty($id)) {
            return new JsonResponse('Bad request', Response::HTTP_BAD_REQUEST);
        }

        $attachment = Attachment::find($id);

        if (empty($attachment)) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($attachment->delete());
    }
}
