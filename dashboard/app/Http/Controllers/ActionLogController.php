<?php

namespace App\Http\Controllers;

use App\DataProvider\ActionLogDataProvider;
use App\Dto\ActionLog\ActionLogDto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActionLogController extends Controller
{
    private ActionLogDataProvider $actionLogDataProvider;

    public function __construct(ActionLogDataProvider $actionLogDataProvider)
    {
        $this->actionLogDataProvider = $actionLogDataProvider;
    }

    public function store(Request $request): JsonResponse
    {
        $result = $this->actionLogDataProvider->create(ActionLogDto::createFromRequest($request));

        return new JsonResponse($result);
    }
}
