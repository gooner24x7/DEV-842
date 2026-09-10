<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataProvider\SupplyFitEnquiryDataProvider;
use App\DataProvider\SupplyFitEnquiryQuoteDataProvider;
use App\Dto\Answer\SearchParamsDto;
use App\Dto\SupplyFitEnquiryQuote\SupplyFitEnquiryQuoteDto;
use App\Mail\NoQuestionnaireResponseForEnquiry;
use App\Mail\SupplyFitEnquiryQuoteAccepted;
use App\Repository\QuestionnaireSessionRepository;
use App\Repository\WorksPackagesRepository;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use RedisException;
use Symfony\Component\HttpFoundation\Response;

class SupplyFitEnquiryQuotesController extends Controller
{
    private SupplyFitEnquiryQuoteDataProvider $supplyFitEnquiryQuoteDataProvider;
    private UserService $userService;
    private SupplyFitEnquiryDataProvider $supplyFitEnquiryDataProvider;
    private QuestionnaireSessionRepository $questionnaireSessionRepository;

    public function __construct(
        SupplyFitEnquiryQuoteDataProvider $supplyFitEnquiryQuoteDataProvider,
        UserService                       $userService,
        SupplyFitEnquiryDataProvider      $supplyFitEnquiryDataProvider,
        QuestionnaireSessionRepository    $questionnaireSessionRepository,
    ) {
        $this->supplyFitEnquiryQuoteDataProvider = $supplyFitEnquiryQuoteDataProvider;
        $this->userService = $userService;
        $this->supplyFitEnquiryDataProvider = $supplyFitEnquiryDataProvider;
        $this->questionnaireSessionRepository = $questionnaireSessionRepository;
    }

    /**
     * @throws RedisException
     */
    public function index(int $enquiryId, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (!$this->supplyFitEnquiryDataProvider->get($enquiryId)) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->supplyFitEnquiryQuoteDataProvider->find(
            SearchParamsDto::createFromRequest($request),
            $user,
            $enquiryId,
            false
        ));
    }

    /**
     * @throws RedisException
     */
    public function store(Request $request, int $enquiryId): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $enquiry = $this->supplyFitEnquiryDataProvider->get($enquiryId);
        if (!$enquiry) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $quote = $this->supplyFitEnquiryQuoteDataProvider->store(
            SupplyFitEnquiryQuoteDto::createFromRequest($request),
            $enquiry,
            $user
        );

        if ($quote !== null) {
            if ($this->supplyFitEnquiryQuoteDataProvider->isLastAnswerOlderThanADay($enquiryId, $quote->getId())) {
                return new JsonResponse($quote);
            }

            //send email

            return new JsonResponse($quote);
        }

        return new JsonResponse('Failed', Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws RedisException
     */
    public function partialUpdate(Request $request, int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $quote = $this->supplyFitEnquiryQuoteDataProvider->getById($id);
        if (!$quote) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $supplierInvoiceNo = (string) $request->get('supplier_invoice_no');
        $localMaterialSpend = (float) $request->get('local_material_spend');
        $localMaterialSpend = $localMaterialSpend === 0.0 ? null : $localMaterialSpend;

        $params = $request->all();

        if (array_key_exists('supplier_invoice_no', $params)) {
            $quote = $this->supplyFitEnquiryQuoteDataProvider->storeSupplierInvoiceNo($supplierInvoiceNo, $id);
        }

        if (array_key_exists('local_material_spend', $params)) {
            $quote = $this->supplyFitEnquiryQuoteDataProvider->storeLocalMaterialSpend($localMaterialSpend, $id);
        }

        if ($quote) {
            return new JsonResponse($quote);
        }

        return new JsonResponse('Failed', Response::HTTP_SERVICE_UNAVAILABLE);
    }

    /**
     * @throws RedisException
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $quote = $this->supplyFitEnquiryQuoteDataProvider->getById($id);
        if (!$quote) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        if ($quote = $this->supplyFitEnquiryQuoteDataProvider->update(SupplyFitEnquiryQuoteDto::createFromRequest($request), $id)) {
            return new JsonResponse($quote);
        }

        return new JsonResponse('failed to update', Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws RedisException
     */
    public function accept(int $id): JsonResponse
    {
        $quote = $this->supplyFitEnquiryQuoteDataProvider->getById($id);
        if (!$quote) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $accepted = $this->supplyFitEnquiryQuoteDataProvider->accept($id);
        $user = $this->userService->getById($quote->user_id);

        if ($accepted) {
            Mail::to($user->getEmail())->queue(new SupplyFitEnquiryQuoteAccepted($quote, $user));
        }

        return new JsonResponse($accepted);
    }

    /**
     * @throws RedisException
     */
    public function unaccept(int $id): JsonResponse
    {
        $quote = $this->supplyFitEnquiryQuoteDataProvider->getById($id);
        if (!$quote) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->supplyFitEnquiryQuoteDataProvider->unaccept($id));
    }

    /**
     * @throws RedisException
     */
    public function delete(int $id): JsonResponse
    {
        $quote = $this->supplyFitEnquiryQuoteDataProvider->getById($id);
        if (!$quote) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->supplyFitEnquiryQuoteDataProvider->delete($id));
    }

    public function overview(int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $response = $this->supplyFitEnquiryDataProvider->getOverview($id);

        return new JsonResponse($response);
    }

    public function resendQuestionnaire(int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $quote = $this->supplyFitEnquiryQuoteDataProvider->getById($id);
        $enquiry = $this->supplyFitEnquiryDataProvider->get($quote->enquiry_id);
        $companyUser = $this->userService->getById($enquiry->user_id);
        $toUser = $this->userService->getById($quote->user_id);

        $projectId = $enquiry->getProjectId();
        $worksPackageId = $enquiry->getWorksPackageId();

        if (!$projectId || !$worksPackageId) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $hash = $this->questionnaireSessionRepository->getHash($toUser->id, $enquiry->id, $projectId, $worksPackageId);

        Mail::to($toUser->email)->bcc('ruslancer@gmail.com')->send(new NoQuestionnaireResponseForEnquiry($enquiry, $toUser, $companyUser, $hash));

        return new JsonResponse([]);
    }
}
