<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataProvider\AnswerDataProvider;
use App\DataProvider\QuestionDataProvider;
use App\Dto\Answer\AnswerDto;
use App\Dto\Answer\SearchParamsDto;
use App\Mail\QuestionAnswered;
use App\Models\PermissionsReference;
use App\Models\Role;
use App\Service\OpenAIService;
use App\Service\ReportsService;
use App\Service\UserService;
use App\Service\ZohoCrmService;
use App\Service\ZohoOauthCustom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use RedisException;
use Symfony\Component\HttpFoundation\Response;

class AnswersController extends Controller
{
    private AnswerDataProvider $answerDataProvider;
    private UserService $userService;
    private QuestionDataProvider $questionDataProvider;
    private ReportsService $reportsService;

    public function __construct(
        AnswerDataProvider   $answerDataProvider,
        UserService          $userService,
        QuestionDataProvider $questionDataProvider,
        ReportsService       $reportsService
    ) {
        $this->answerDataProvider = $answerDataProvider;
        $this->userService = $userService;
        $this->questionDataProvider = $questionDataProvider;
        $this->reportsService = $reportsService;
    }

    /**
     * @throws RedisException
     */
    public function index(int $questionId, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (!$user->can(PermissionsReference::readAnswer)) {
            return new JsonResponse('Forbidden', Response::HTTP_FORBIDDEN);
        }

        if (!$this->questionDataProvider->get($questionId)) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $branchUserId = $request->query->get('branchId');
        if ($user->hasRole(Role::ROLE_BRANCH_MANAGER) && !$branchUserId) {
            return new JsonResponse('Wrong request, missing branch id', Response::HTTP_UNAUTHORIZED);
        }

        if ($branchUserId) {
            $user = $this->userService->getById((int)$branchUserId);
        }

        $answers = $this->answerDataProvider->find(
            SearchParamsDto::createFromRequest($request),
            $user,
            (bool)$branchUserId,
            $questionId,
            false
        );

        return new JsonResponse($answers);
    }

    /**
     * @throws RedisException
     */
    public function store(Request $request, int $questionId): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (!$user->can(PermissionsReference::answerQuestion)) {
            return new JsonResponse('Forbidden', Response::HTTP_FORBIDDEN);
        }

        $question = $this->questionDataProvider->get($questionId);
        if (!$question) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        if ($question->archived_at) {
            return new JsonResponse('Archived', Response::HTTP_NOT_FOUND);
        }

        $answer = $this->answerDataProvider->store(
            AnswerDto::createFromRequest($request),
            $question,
            $user
        );

        if ($answer !== null) {
            if (!$this->answerDataProvider->isLastAnswerOlderThanADay($questionId, $answer->getId())) {
                return new JsonResponse($answer);
            }

            if ($userEmail = $this->questionDataProvider->getEmailForQuestion($questionId)) {
                Mail::to($userEmail)->send(new QuestionAnswered($question, $answer));
            }

            return new JsonResponse($answer);
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

        $answer = $this->answerDataProvider->getById($id);
        if (!$answer) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $supplierInvoiceNo = (string) $request->get('supplier_invoice_no');
        $localMaterialSpend = (float) $request->get('local_material_spend');
        $localMaterialSpend = $localMaterialSpend === 0.0 ? null : $localMaterialSpend;

        $params = $request->all();

        if (array_key_exists('supplier_invoice_no', $params)) {
            $answer = $this->answerDataProvider->storeSupplierInvoiceNo($supplierInvoiceNo, $id);
        }

        if (array_key_exists('local_material_spend', $params)) {
            $answer = $this->answerDataProvider->storeLocalMaterialSpend($localMaterialSpend, $id);
        }

        if ($answer) {
            return new JsonResponse($answer);
        }

        return new JsonResponse('Failed', Response::HTTP_SERVICE_UNAVAILABLE);
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

        $answer = $this->answerDataProvider->getById($id);
        if (!$answer) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        if ($answer->getUserId() !== $user->getId() && !$user->isAdmin()) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if ($answer = $this->answerDataProvider->update(AnswerDto::createFromRequest($request), $id)) {
            return new JsonResponse($answer);
        }

        return new JsonResponse('failed to update', Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws RedisException
     */
    public function accept(Request $request, int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (!$user->can(PermissionsReference::askQuestion)) {
            return new JsonResponse('Forbidden', Response::HTTP_FORBIDDEN);
        }

        $answer = $this->answerDataProvider->getById($id);
        if (!$answer) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $questionUser = $answer->question->users;

        try {
            if (!empty($questionUser)) {
                $zohoOauthCustom = new ZohoOauthCustom($request);
                $zohoCrmService = new ZohoCrmService($zohoOauthCustom);

                $zohoContact = $zohoCrmService->searchContacts($questionUser->email);

                if (!empty($zohoContact['id'])) {
                    $zohoCrmService->updateContact($zohoContact['id'], ['Had_a_Quote_Accepted' => true]);
                }
            }
        } catch (\Exception $e) {
            //Log::debug($e->getMessage());
        }

        return new JsonResponse($this->answerDataProvider->accept($id));
    }

    /**
     * @throws RedisException
     */
    public function unaccept(int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (!$user->can(PermissionsReference::askQuestion)) {
            return new JsonResponse('Forbidden', Response::HTTP_FORBIDDEN);
        }

        $answer = $this->answerDataProvider->getById($id);
        if (!$answer) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->answerDataProvider->unaccept($id));
    }

    /**
     * @throws RedisException
     */
    public function setCheckedAt(Request $request, int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $answer = $this->answerDataProvider->getById($id);
        if (!$answer) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $checked = (bool)$request->get('checked');

        return new JsonResponse($this->answerDataProvider->setCheckedAt($answer, $checked));
    }

    /**
     * @throws RedisException
     */
    public function delete(int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $answer = $this->answerDataProvider->getById($id);
        if (!$answer) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        if ($answer->getUserId() !== $user->getId() && !$user->isAdmin()) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse($this->answerDataProvider->delete($id));
    }

    public function exportComparisonActivityTracker(int $questionId, Request $request): void
    {
        $user = $this->userService->getCurrentUser();
        $quoteIds = $request->query->all('quoteIds');

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->reportsService->generateQuotesComparisonSpreadsheet($quoteIds);
    }

    public function export(int $questionId, Request $request): void
    {
        $user = $this->userService->getCurrentUser();
        $branchUserId = $request->query->get('branchId');

        if ($branchUserId) {
            $user = $this->userService->getById((int)$branchUserId);
        }

        $question = $this->questionDataProvider->get($questionId);
        $answers = $this->answerDataProvider->find(
            SearchParamsDto::createFromRequest($request),
            $user,
            (bool)$branchUserId,
            $questionId,
            false
        );

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->reportsService->generateQuotesSpreadsheet($question, $answers);
    }

    public function exportComparison(Request $request): void
    {
        //$files = $request->get('files');
        $files = $request->query->all();
        $service = new OpenAIService();
        $jsonOutput = '{}';

        try {
            // create new vector store
            $response = $service->createVectorStore('tbc_quote_comparison_' . time());
            $vectorStoreId = $response['id'];

            // upload files and attach to vector store
            foreach ($files as $file) {
                $parts = explode('/', $file);
                $fileName = end($parts);
                $fileContent = fopen($file, 'r');
                $response = $service->uploadFile($fileContent, $fileName);
                $fileId = $response['id'];

                $response = $service->createVectorStoreFile($vectorStoreId, $fileId);
            }

            // list vector store items
            //$response = $service->getVectorStoreFiles($vectorStoreId);

            // create response
            $body = [
                "model" => 'gpt-4.1',
                "tools" => [[
                    "type" => "file_search",
                    "vector_store_ids" => [$vectorStoreId],
                    "max_num_results" => 20
                ]],
                "input" => [
                    [
                        "role" => "system",
                        "content" => "You are an expert assistant for The Build Chain. You understand the process called
                        'Quick Comparison Bruce' which includes the following steps: extracting product data, matching
                        similar products, aligning quotes side by side, calculating total prices, adding summary rows,
                        and including savings calculations. Whenever a user says 'Run Quick Comparison Bruce', follow
                        this exact sequence without skipping any steps."
                    ],
                    [
                        "role" => "user",
                        "content" => "Run Quick Comparison Bruce"
                    ],
                    [
                        "role" => "user",
                        "content" => "Please return the comparison as json using the provided schema"
                    ]
                ],
                "text" => [
                    "format" => [
                        "type" => "json_schema",
                        "name" => "tbc_quote_comparison",
                        "schema" => [
                            "type" => "object",
                            "properties" => [
                                "summary" => [
                                    "type" => "object",
                                    "properties" => [
                                        "total_1" => ["type" => "number"],
                                        "total_2" => ["type" => "number"],
                                        "total_3" => ["type" => "number"],
                                        "savings" => ["type" => "number"],
                                        "percentage_saved" => ["type" => "number"]
                                    ],
                                    "required" => ["total_1", "total_2", "total_3", "savings", "percentage_saved"],
                                    "additionalProperties" => false
                                ],
                                "products" => [
                                    "type" => "array",
                                    "items" => [
                                        "type" => "object",
                                        "properties" => [
                                            "product_name" => ["type" => "string"],
                                            "product_data" => [
                                                "type" => "array",
                                                "items" => [
                                                    "type" => "object",
                                                    "properties" => [
                                                        "qty" => ["type" => "integer"],
                                                        "unit_price" => ["type" => "number"],
                                                        "total" => ["type" => "number"]
                                                    ],
                                                    "required" => ["qty", "unit_price", "total"],
                                                    "additionalProperties" => false
                                                ]
                                            ]
                                        ],
                                        "required" => ["product_name", "product_data"],
                                        "additionalProperties" => false
                                    ]
                                ]
                            ],
                            "required" => ["summary", "products"],
                            "additionalProperties" => false
                        ],
                        "strict" => true
                    ]
                ]
            ];

            $response = $service->createResponse($body);
            $jsonOutput = $response['output'][1]['content'][0]['text'] ?? '{}';

        } catch (\Exception $e) {
            //return new JsonResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (empty($jsonOutput)) {
            //return new JsonResponse('An error occurred', Response::HTTP_INTERNAL_SERVER_ERROR);
            throw new \Exception('An error occurred');
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->reportsService->generateQuoteComparisonSpreadsheet(json_decode($jsonOutput, true));;
    }
}
