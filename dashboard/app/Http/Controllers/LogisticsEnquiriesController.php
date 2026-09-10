<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataProvider\LogisticsEnquiryDataProvider;
use App\DataProvider\LogisticsQuoteDataProvider;
use App\Dto\Logistics\LogisticsEnquiryDto;
use App\Dto\Logistics\SearchParamsDto;
use App\Models\LogisticsEnquiry;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response;
use RedisException;

class LogisticsEnquiriesController extends Controller
{
    public const int SEARCH_RADIUS = 40;

    private UserService $userService;
    private LogisticsEnquiryDataProvider $enquiryDataProvider;
    private LogisticsQuoteDataProvider $quoteDataProvider;

    public function __construct(
        UserService $userService,
        LogisticsEnquiryDataProvider $enquiryDataProvider,
        LogisticsQuoteDataProvider $quoteDataProvider,
    ) {
        $this->userService = $userService;
        $this->enquiryDataProvider = $enquiryDataProvider;
        $this->quoteDataProvider = $quoteDataProvider;
    }

    /**
     * @throws RedisException
     */
    public function get(int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        $enquiry = $this->enquiryDataProvider->get($id);

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (!$enquiry) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($enquiry);
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

        $enquiries = $this->enquiryDataProvider->find(SearchParamsDto::createFromRequest($request), $user, false);

        return new JsonResponse($enquiries);
    }

    /**
     * @throws RedisException
     */
    public function store(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $dto = LogisticsEnquiryDto::createFromRequest($request, $user->getId());
        $enquiry = $this->enquiryDataProvider->store($dto);

        if ($enquiry) {
            Artisan::call('geocode:postcode-logistics', [
                'enquiry-id' => $enquiry->getId(),
            ]);

            return new JsonResponse($enquiry);
        }

        return new JsonResponse('failed to create the enquiry', Response::HTTP_BAD_GATEWAY);
    }

    /**
     * @throws RedisException
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $dto = LogisticsEnquiryDto::createFromRequest($request, $user->getId());
        $enquiry = $this->enquiryDataProvider->get($id);

        if (!$enquiry) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        if ($this->enquiryDataProvider->update($dto, $id)) {
            return new JsonResponse($enquiry);
        }

        return new JsonResponse('failed to update the enquiry', Response::HTTP_BAD_GATEWAY);
    }

    /**
     * @throws RedisException
     */
    public function delete(int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        $enquiry = $this->enquiryDataProvider->get($id);

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (!$enquiry) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        if ($enquiry->getUserId() !== $user->getId() && !$user->isAdmin()) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse($this->enquiryDataProvider->delete($id));
    }

    /**
     * @throws RedisException
     */
    public function archive(int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        $enquiry = $this->enquiryDataProvider->get($id);

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (!$enquiry) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        if ($enquiry->getUserId() !== $user->getId() && !$user->isAdmin()) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse($this->enquiryDataProvider->archive($enquiry));
    }

    /**
     * @throws RedisException
     */
    public function restore(int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        $enquiry = $this->enquiryDataProvider->get($id);

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (!$enquiry) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        if ($enquiry->getUserId() !== $user->getId() && !$user->isAdmin()) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse($this->enquiryDataProvider->restore($enquiry));
    }

    /**
     * @throws RedisException
     */
    public function duplicate(int $id, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        $enquiry = $this->enquiryDataProvider->get($id);

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (!$enquiry) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        if ($enquiry->getUserId() !== $user->getId() && !$user->isAdmin()) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $dto = LogisticsEnquiryDto::createFromRequest($request, $user->getId());

        return new JsonResponse($this->enquiryDataProvider->duplicate($id, $dto));
    }

    /**
     * @throws RedisException
     */
    public function toggleIgnore(int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        $enquiry = $this->enquiryDataProvider->get($id);

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (!$enquiry) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $this->enquiryDataProvider->toggleIgnore($enquiry, $user);

        return new JsonResponse('Success');
    }

    public function worksPackageOptions(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $options = $this->enquiryDataProvider->getWorksPackageOptions(
            $request->get('search') ?? '',
            $user,
            $request->get('archived') ?? false,
            false,
            $request->get('projectIds') ?? []
        );

        return new JsonResponse($options);
    }

    public function projectOptions(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $options = $this->enquiryDataProvider->getProjectOptions(
            $request->get('search') ?? '',
            $user,
            $request->get('archived') ?? false
        );

        return new JsonResponse($options);
    }

}
