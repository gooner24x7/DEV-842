<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataProvider\TutorialDataProvider;
use App\Dto\SearchParamsDto;
use App\Dto\Tutorial\TutorialDto;
use App\Exceptions\InvalidRequestException;
use App\Exceptions\NotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Psr\SimpleCache\InvalidArgumentException;
use RedisException;
use Symfony\Component\HttpFoundation\Response;

class TutorialsController extends Controller
{
    private TutorialDataProvider $tutorialDataProvider;

    public function __construct(
        TutorialDataProvider $tutorialDataProvider
    )
    {
        $this->tutorialDataProvider = $tutorialDataProvider;
    }

    /**
     * @throws RedisException
     */
    public function index(Request $request): JsonResponse
    {
        return new JsonResponse($this->tutorialDataProvider->find(
            SearchParamsDto::createFromRequest($request),
            false
        ));
    }

    /**
     * @throws RedisException
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $dto = TutorialDto::createFromRequest($request);
        } catch (InvalidRequestException $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($this->tutorialDataProvider->create($dto));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            return new JsonResponse($this->tutorialDataProvider->update(
                $id,
                TutorialDto::createFromRequest($request)
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
            return new JsonResponse($this->tutorialDataProvider->delete($id));
        } catch (NotFoundException $e) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        } catch (InvalidArgumentException $e) {
        } catch (RedisException $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
