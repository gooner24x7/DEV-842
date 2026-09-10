<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataProvider\AnswerDataProvider;
use App\DataProvider\QuestionDataProvider;
use App\Dto\Question\QuestionBatchDto;
use App\Dto\Question\QuestionDto;
use App\Dto\Question\SearchParamsDto;
use App\Mail\EnquiryDuplicated;
use App\Mail\NudgeSales;
use App\Mail\QuestionCreatedNational;
use App\Models\Answer;
use App\Models\FavPurchaseHireInquiry;
use App\Models\PermissionsReference;
use App\Models\Question;
use App\Models\Questionnaire\WorksPackage;
use App\Repository\InquiryMerchantRepository;
use App\Service\HousebuildingService;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RedisException;
use Symfony\Component\HttpFoundation\Response;

class QuestionsController extends Controller
{
    public const int SEARCH_RADIUS = 40;

    private UserService $userService;
    private QuestionDataProvider $questionDataProvider;
    private AnswerDataProvider $answerDataProvider;
    private InquiryMerchantRepository $inquiryMerchantRepository;
    private HousebuildingService $houseBuildingService;

    public function __construct(
        UserService $userService,
        QuestionDataProvider $questionDataProvider,
        AnswerDataProvider $answerDataProvider,
        InquiryMerchantRepository $inquiryMerchantRepository,
        HousebuildingService $houseBuildingService
    ) {
        $this->userService = $userService;
        $this->questionDataProvider = $questionDataProvider;
        $this->answerDataProvider = $answerDataProvider;
        $this->inquiryMerchantRepository = $inquiryMerchantRepository;
        $this->houseBuildingService = $houseBuildingService;
    }

    /**
     * @throws RedisException
     */
    public function favouriteInquiries(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (!$user->can(PermissionsReference::readQuestion)) {
            return new JsonResponse('Forbidden', Response::HTTP_FORBIDDEN);
        }

        $branchUserId = $request->query->get('branchId');
        if ($branchUserId) {
            $user = $this->userService->getById((int)$branchUserId);
        }

        return new JsonResponse($this->questionDataProvider->findFav(
            SearchParamsDto::createFromRequest($request),
            $user,
            false
        ));
    }

    /**
     * @throws RedisException
     */
    public function get(int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (!$user->can(PermissionsReference::readQuestion)) {
            return new JsonResponse('Forbidden', Response::HTTP_FORBIDDEN);
        }

        $question = $this->questionDataProvider->get($id);
        if (!$question) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($question);
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

        if (!$user->can(PermissionsReference::readQuestion)) {
            return new JsonResponse('Forbidden', Response::HTTP_FORBIDDEN);
        }

        $branchUserId = $request->query->get('branchId');
        if ($branchUserId) {
            $userBranch = $this->userService->getById((int)$branchUserId);
        }

        $onlyUnexpired = $request->query->getBoolean('onlyUnexpired') ?? false;

        return new JsonResponse($this->questionDataProvider->find(
            SearchParamsDto::createFromRequest($request),
            $userBranch ?? $user,
            $user,
            $onlyUnexpired,
            false
        ));
    }

    public function removeFav(Request $request, int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $item = FavPurchaseHireInquiry::where(['user_id' => $user->getId(), 'inquiry_id' => $id])->first();
        if (!$item) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $item->delete();

        return new JsonResponse([]);
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

        $question = $this->questionDataProvider->get($id);
        if (!$question) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        if ($question->getUserId() !== $user->getId() && !$user->isAdmin()) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse(
            $this->questionDataProvider->delete($id)
        );
    }

    public function addToFav(Request $request, int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $item = FavPurchaseHireInquiry::where(['user_id' => $user->getId(), 'inquiry_id' => $id])->first();
        if ($item) {
            return new JsonResponse('Already added', Response::HTTP_ALREADY_REPORTED);
        }

        //TODO: put to repository
        $fav = new FavPurchaseHireInquiry();
        $fav->user_id = $user->getId();
        $fav->inquiry_id = $id;
        $fav->save();

        return new JsonResponse($fav);
    }

    /**
     * @throws RedisException
     */
    public function archive(int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $question = $this->questionDataProvider->get($id);
        if (!$question) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        if ($question->getUserId() !== $user->getId() && !$user->isAdmin()) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse(
            $this->questionDataProvider->setArchived($question)
        );
    }

    /**
     * @throws RedisException
     */
    public function duplicate(int $id, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $question = $this->questionDataProvider->get($id);
        if (!$question) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        if ($question->getUserId() !== $user->getId() && !$user->isAdmin()) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $dto = QuestionDto::createFromRequest($request, $user->getId());

        $duplicated = $this->questionDataProvider->duplicate($id, $dto);

        /** @var Answer $answer */
        foreach ($question->answers as $answer) {
            $user = $this->userService->getById($answer->user_id);
            if ($user) {
                Mail::to($user->getEmail())->queue(new EnquiryDuplicated($question, $duplicated, $user));
            }
        }

        return new JsonResponse(
            $duplicated
        );
    }

    /**
     * @throws RedisException
     */
    public function restore(int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $question = $this->questionDataProvider->get($id);
        if (!$question) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        if ($question->getUserId() !== $user->getId() && !$user->isAdmin()) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse(
            $this->questionDataProvider->restore($question)
        );
    }

    public function storeBatch(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (!$user->can(PermissionsReference::askQuestion)) {
            return new JsonResponse('Forbidden', Response::HTTP_FORBIDDEN);
        }

        // if first enquiry then update user onboarding checklist
        if ($user->questions()->get()->isEmpty()) {
            $user->onboardingChecklist()->update(['first_enquiry' => true]);
        }

        $worksPackageName = $request->get('works_package_name');

        // check if new works package was added
        if (!empty($worksPackageName)) {
            /** @var WorksPackage $wp */
            $wp = WorksPackage::create([
                'name' => $worksPackageName,
                'user_id' => $user->getId(),
                'project_id' => $request->get('project_id') ?? 0,
            ]);

            $request->merge(['works_package_id' => $wp->getId()]);
        }

        $questionDtoItems = QuestionBatchDto::createFromRequest($request, $user);

        $nationalsItems = array_map(function ($item) {
            return $item['nationals'] ?? false;
        }, $request->all()['items']);

        $questions = $this->questionDataProvider->storeBatch($questionDtoItems, $nationalsItems);

        foreach ($questions as $question) {
            if (!empty($housebuildingFilePath)) {
                $question->attach($housebuildingFilePath);
            }
        }

        return new JsonResponse($questions);
    }

    public function storeByHouseName(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (!$user->can(PermissionsReference::askQuestion)) {
            return new JsonResponse('Forbidden', Response::HTTP_FORBIDDEN);
        }

        $worksPackageName = $request->get('works_package_name');

        // check if new works package was added
        if (!empty($worksPackageName)) {
            /** @var WorksPackage $wp */
            $wp = WorksPackage::create([
                'name' => $worksPackageName,
                'user_id' => $user->getId(),
                'project_id' => $request->get('project_id') ?? 0,
            ]);

            $request->merge(['works_package_id' => $wp->getId()]);
        }

        $questionDtoItem = QuestionDto::createFromRequest($request, $user->getId());

        $houseName = $request->get('house_name') ?? null;

        if (empty($houseName)) {
            return new JsonResponse('House Name is required', Response::HTTP_BAD_REQUEST);
        }

        $categories = $this->houseBuildingService->getCategories($houseName);
        $questions = [];

        foreach($categories as $category) {
            $productId = $this->houseBuildingService->getCategoryProductId($category);

            if (empty($productId)) {
                continue;
            }

            $questionDtoItem->setProductId($productId);
            $question = $this->questionDataProvider->store($questionDtoItem);

            if (empty($question)) {
                return new JsonResponse('Error saving question', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $housebuildingFilePath = $this->houseBuildingService->export($houseName, $category, $question->getId());
            $question->attach($housebuildingFilePath);

            $questions[] = $question;
        }

        return new JsonResponse($questions);
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

        if (!$user->can(PermissionsReference::askQuestion)) {
            return new JsonResponse('Forbidden', Response::HTTP_FORBIDDEN);
        }

        $dto = QuestionDto::createFromRequest($request, $user->getId());

        $question = $this->questionDataProvider->get($id);
        if (!$question) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $this->questionDataProvider->updateNationals($question, $request);

        if ($this->questionDataProvider->update($dto, $id)) {
            return new JsonResponse($question);
        }

        return new JsonResponse('failed to update the question', Response::HTTP_BAD_GATEWAY);
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

        if (!$user->can(PermissionsReference::askQuestion)) {
            return new JsonResponse('Forbidden', Response::HTTP_FORBIDDEN);
        }

        // if first enquiry then update user onboarding checklist
        if ($user->questions()->get()->isEmpty()) {
            $user->onboardingChecklist()->update(['first_enquiry' => true]);
        }

        $worksPackageName = $request->get('works_package_name');

        // check if new works package was added
        if (!empty($worksPackageName)) {
            /** @var WorksPackage $wp */
            $wp = WorksPackage::create([
                'name' => $worksPackageName,
                'user_id' => $user->getId(),
                'project_id' => $request->get('project_id') ?? 0,
            ]);

            $request->merge(['works_package_id' => $wp->getId()]);
        }

        $dto = QuestionDto::createFromRequest($request, $user->getId());
        $question = $this->questionDataProvider->store($dto);

        if ($question) {
            Artisan::call('geocode:postcode', [
                'question-id' => $question->getId(),
            ]);

            return new JsonResponse($question);
        }

        return new JsonResponse('failed to create the question', Response::HTTP_BAD_GATEWAY);
    }

    /**
     * @throws RedisException
     */
    public function worksPackageOptions(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $options = $this->questionDataProvider->getWorksPackageOptions(
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

        $options = $this->questionDataProvider->getProjectOptions(
            $request->get('search') ?? '',
            $user,
            $request->get('archived') ?? false
        );

        return new JsonResponse($options);
    }

    /**
     * @throws RedisException
     */
    public function toggleIgnore(int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (!$user->can(PermissionsReference::answerQuestion)) {
            return new JsonResponse('Forbidden', Response::HTTP_FORBIDDEN);
        }

        $question = $this->questionDataProvider->get($id);
        if (!$question) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $this->questionDataProvider->toggleIgnore($question, $user);

        return new JsonResponse('Success');
    }

    /**
     * @throws RedisException
     */
    public function toggleAssign(int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (!$user->can(PermissionsReference::answerQuestion)) {
            return new JsonResponse('Forbidden', Response::HTTP_FORBIDDEN);
        }

        $question = $this->questionDataProvider->get($id);
        if (!$question) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $this->questionDataProvider->toggleAssign($question, $user);

        return new JsonResponse('Success');
    }

    public function seenByMerchants(int $qid, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (!$user->can(PermissionsReference::manageUsers) &&
            !$user->hasRole(\App\Models\Role::ROLE_CUSTOMER_SUCCESS_ADMIN)
        ) {
            return new JsonResponse('Forbidden', Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse($this->questionDataProvider->findBySeenByMerchants(
            $qid,
            SearchParamsDto::createFromRequest($request)
        ));
    }

    /**
     * @throws RedisException
     */
    public function nudgeSales(string $qid, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (!$user->can(PermissionsReference::manageUsers) &&
            !$user->hasRole(\App\Models\Role::ROLE_CUSTOMER_SUCCESS_ADMIN)
        ) {
            return new JsonResponse('Forbidden', Response::HTTP_FORBIDDEN);
        }

        $inquiry = $this->questionDataProvider->get((int)$qid);
        if (!$inquiry) {
            return new JsonResponse('Inquiry not found', Response::HTTP_NOT_FOUND);
        }

        Mail::to(config('app.sales_email_address'))->send(new NudgeSales($inquiry));

        return new JsonResponse();
    }

    public function setCalled(string $qid, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (!$user->can(PermissionsReference::manageUsers) &&
            !$user->hasRole(\App\Models\Role::ROLE_CUSTOMER_SUCCESS_ADMIN)
        ) {
            return new JsonResponse('Forbidden', Response::HTTP_FORBIDDEN);
        }

        $userId = (int) $request->get('user_id', 0);
        $comment = (string) $request->get('comment', '');

        if (empty($userId)) {
            return new JsonResponse('Bad request', Response::HTTP_BAD_REQUEST);
        }

        $response = $this->inquiryMerchantRepository->setCalled($userId, $comment, $user, (int)$qid);

        return new JsonResponse($response);
    }

    public function getNationalsOptions(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $options = DB::table('national_suppliers')->select('id', 'name')->get()->toArray();

        return new JsonResponse($options);
    }

    public function getPreferredSuppliers(int $qid, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse($this->questionDataProvider->findPreferredSuppliers(
            $qid,
            SearchParamsDto::createFromRequest($request)
        ));
    }
}
