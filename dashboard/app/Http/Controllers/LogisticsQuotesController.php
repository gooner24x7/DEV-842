<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataProvider\LogisticsQuoteDataProvider;
use App\DataProvider\LogisticsEnquiryDataProvider;
use App\Dto\Logistics\LogisticsQuoteDto;
use App\Dto\Logistics\SearchParamsDto;
use App\Mail\LogisticsQuoteAccepted;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use RedisException;
use Symfony\Component\HttpFoundation\Response;

class LogisticsQuotesController extends Controller
{
    private LogisticsQuoteDataProvider $quoteDataProvider;
    private LogisticsEnquiryDataProvider $enquiryDataProvider;
    private UserService $userService;

    public function __construct(
        LogisticsQuoteDataProvider $quoteDataProvider,
        LogisticsEnquiryDataProvider $enquiryDataProvider,
        UserService $userService
    ) {
        $this->quoteDataProvider = $quoteDataProvider;
        $this->enquiryDataProvider = $enquiryDataProvider;
        $this->userService = $userService;
    }

    /**
     * @throws RedisException
     */
    public function index(Request $request, int $enquiryId): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (!$this->enquiryDataProvider->get($enquiryId)) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $quotes = $this->quoteDataProvider->find(
            SearchParamsDto::createFromRequest($request),
            $user,
            $enquiryId,
            false
        );

        return new JsonResponse($quotes);
    }

    /**
     * @throws RedisException
     */
    public function store(Request $request, int $enquiryId): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        $enquiry = $this->enquiryDataProvider->get($enquiryId);

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (!$enquiry) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        if ($enquiry->archived_at) {
            return new JsonResponse('Archived', Response::HTTP_NOT_FOUND);
        }

        $quote = $this->quoteDataProvider->store(
            LogisticsQuoteDto::createFromRequest($request),
            $enquiry,
            $user
        );

        return new JsonResponse($quote);
    }

    /**
     * @throws RedisException
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        $quote = $this->quoteDataProvider->getById($id);

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (!$quote) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        if ($quote->getUserId() !== $user->getId() && !$user->isAdmin()) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if ($quote = $this->quoteDataProvider->update(LogisticsQuoteDto::createFromRequest($request), $id)) {
            return new JsonResponse($quote);
        }

        return new JsonResponse('failed to update', Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws RedisException
     */
    public function delete(int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        $quote = $this->quoteDataProvider->getById($id);

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (!$quote) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        if ($quote->getUserId() !== $user->getId() && !$user->isAdmin()) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse($this->quoteDataProvider->delete($id));
    }

    /**
     * @throws RedisException
     */
    public function accept(int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        $quote = $this->quoteDataProvider->getById($id);

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (!$quote) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $quoteUser = $this->userService->getById($quote->user_id);
        $result = $this->quoteDataProvider->accept($id);

        if ($result && $quoteUser) {
            Mail::to($quoteUser->getEmail())->send(new LogisticsQuoteAccepted($quote, $quoteUser));
        }

        return new JsonResponse($result);
    }

    /**
     * @throws RedisException
     */
    public function unaccept(int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        $quote = $this->quoteDataProvider->getById($id);

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (!$quote) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->quoteDataProvider->unaccept($id));
    }
}
