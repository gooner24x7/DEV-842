<?php
declare(strict_types=1);

namespace App\Repository;

use App\Dto\Question\QuestionBatchDto;
use App\Dto\Question\QuestionDto;
use App\Dto\Question\SearchParamsDto;
use App\Http\Controllers\MessagesController;
use App\Models\Answer;
use App\Models\Ignored;
use App\Models\Message;
use App\Models\National;
use App\Models\Question;
use App\Models\Role;
use App\Models\SupplyFitEnquiryQuote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class QuestionRepository.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class QuestionRepository
{
    const string DEFAULT_ORDER_FIELD_NAME = 'questions.id';
    const int ITEMS_PER_PAGE = 20;
    const int SEARCH_RADIUS = 40;

    private \Redis $redis;
    private UserRepository $userRepository;

    public function __construct(\Redis $redis, UserRepository $userRepository)
    {
        $this->redis = $redis;
        $this->userRepository = $userRepository;
    }

    public function findFav(SearchParamsDto $searchParamsDto, User $user): LengthAwarePaginator
    {
        $query = Question::join('users', 'users.id', '=', 'questions.user_id')
            ->join('products', 'products.id', '=', 'questions.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->join('favourite_purchase_hire_inquiries', 'favourite_purchase_hire_inquiries.inquiry_id', '=', 'questions.id')
            ->select([
                'questions.id',
                'questions.user_id',
                'questions.postcode',
                'questions.days',
                'questions.comment',
                'questions.works_package_id',
                'questions.project_id',
                'questions.created_at',
                'questions.type',
                'questions.ref_archived_enquiry_id',
                'products.name',
                'questions.product_id',
                'users.first_name',
                'users.last_name',
                'categories.type as product_type',
            ]);

        $query->selectRaw('((select a.id from answers a where a.question_id=questions.id and a.user_id=? limit 1) is not null) as is_quoted', [$user->getId()]);

        $query->where(['favourite_purchase_hire_inquiries.user_id' => $user->getId()]);

        $query = $query->orderBy(
            $searchParamsDto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME,
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        $itemsPerPage = $searchParamsDto->getItemsPerPage() ?? self::ITEMS_PER_PAGE;
        if (!$searchParamsDto->isPaginationEnabled()) {
            $itemsPerPage = PHP_INT_MAX;
        }

        return $this->populateWithIndicator($query->paginate($itemsPerPage), $user);
    }

    private function populateWithIndicator(LengthAwarePaginator $response, User $user): LengthAwarePaginator
    {
        $redis = $this->redis;
        $questionRepository = $this;

        $response->getCollection()->transform(static function (Question $question) use ($user, $redis, $questionRepository): Question {
            $newAnswerIndicatorKey = sprintf(Answer::NEW_ANSWER_TO_ENQUIRY_INDICATOR_CACHE, $question->getId(), $user->getId());
            $createdAfterDate = $redis->get($newAnswerIndicatorKey);

            $answerTotal = Answer::query()
                ->where('question_id', '=', $question->getId())
                ->where('user_id', '<>', $user->getId())
                ->where('created_at', '>', $createdAfterDate)
                ->count('id');

            $question->setNewQuotesQty($answerTotal);
            $question->assigned = self::getAssignedUsersByBillingUserId($question, $user->billing_user_id);

            $newMsgIndicatorKey = sprintf(Message::NEW_MESSAGE_INQUIRY_INDICATOR_CACHE, $question->getId(), $user->getId(), MessagesController::MESSAGE_TYPE_QUESTION);

            $qty = Message::query()
                ->where('question_id', '=', $question->getId())
                ->where('interlocutor_id', '=', $user->getId())
                ->where('created_at', '>', $redis->get($newMsgIndicatorKey))
                ->count('id');

            $question->setNewMsgQty($qty);

            if ($user->hasRole(Role::ROLE_ADMIN_SLUG)) {
                $seenUnquotedQty = $questionRepository->getSeenUnquotedQty($question->getId());
                $question->setSeenUnquotedQty($seenUnquotedQty);
            }

            $question->load('nationals');

            return $question;
        });

        return $response;
    }

    public function get(int $id): ?Question
    {
        $question = Question::where(['id' => $id])->with('product')->first();
        if ($question) {
            return $question;
        }

        return null;
    }

    private static function getAssignedUsersByBillingUserId(Question $question, ?int $billingUserId = null): ?array
    {
        if (null === $billingUserId) {
            return null;
        }

        $assignedUserIds = $question->assignedUsers()->pluck('user_id')->toArray();

        return User::whereIn('id', $assignedUserIds)->where(['billing_user_id' => $billingUserId])->get()->toArray();
    }

    private function getSeenUnquotedQty(int $inquiryId): int
    {
        $inquiry = $this->get($inquiryId);

        /** TODO: update after change in the linking process */
        $query = User::query()->distinct()
            ->leftJoin('product_user', 'users.id', '=', 'product_user.user_id')
            ->leftJoin('users_roles', 'users.id', '=', 'users_roles.user_id')
            ->leftJoin('roles_permissions', 'roles_permissions.role_id', '=', 'users_roles.role_id')
            ->leftJoin('permissions', 'permissions.id', '=', 'roles_permissions.permission_id')
            ->leftJoin('subscriptions', function ($join) {
                $join->on('subscriptions.user_id', '=', 'users.id');
                $join->orOn('subscriptions.user_id', '=', 'users.billing_user_id');
            })
            ->where(['permissions.slug' => \App\Models\PermissionsReference::answerQuestion])
            ->where(['product_user.product_id' => $inquiry->product_id])
            ->where('subscriptions.stripe_status', '!=', 'canceled')
            ->whereRaw('(users.stripe_id IS NOT NULL OR (select u.stripe_id from users u where u.id = users.billing_user_id limit 1) is NOT NULL)')
            ->where(function ($query) use ($inquiry) {
                return $query->inRadius($inquiry->getLat() ?? 0, $inquiry->getLong() ?? 0, QuestionRepository::SEARCH_RADIUS)
                    ->orWhere('users.is_global', '=', '1');
            });

        $query->whereRaw('((select a.id from answers a where a.user_id=users.id and a.question_id=? limit 1) is null)', [$inquiry->id]);

        return $query->count() ?? 0;
    }

    public function find(SearchParamsDto $searchParamsDto, User $user, User $currentUser, bool $onlyUnexpired): LengthAwarePaginator
    {
        $companyUserIds = $user->getCompanyUsers();

        $query = Question::join('users', 'users.id', '=', 'questions.user_id')
            ->join('products', 'products.id', '=', 'questions.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('projects', 'projects.id', '=', 'questions.project_id')
            ->leftJoin('works_packages', 'works_packages.id', '=', 'questions.works_package_id')
            ->select([
                'questions.id',
                'questions.user_id',
                'questions.postcode',
                'questions.days',
                'questions.comment',
                'questions.created_at',
                'questions.type',
                'questions.ref_archived_enquiry_id',
                'questions.send_to_national',
                'questions.lat',
                'questions.long',
                'questions.status',
                'questions.scope',
                'questions.published_at',
                'products.name',
                'questions.product_id',
                'users.first_name',
                'users.last_name',
                'users.company_number',
                'categories.type as product_type',
                'questions.project_id',
                'questions.works_package_id',
                'projects.name as project_name',
                'works_packages.name as works_package_name',
                'works_packages.high_risk'
            ]);

        $query->selectRaw('((select a.id from answers a where a.question_id=questions.id and a.user_id=? limit 1) is not null) as is_quoted', [$user->getId()]);
        $query->selectRaw('((select i.id from ignored i where i.ignorable_id=questions.id and i.ignorable_type=? and i.user_id=? limit 1) is not null) as is_ignored', [Question::class, $user->getId()]);

        if ($currentUser->hasRole(Role::ROLE_BRANCH_MANAGER)) {
            $query->selectRaw('((select a.id from answers a join users u on u.id=a.user_id where a.question_id=questions.id and u.billing_user_id=? limit 1) is not null) as is_quoted_by_branch', [$currentUser->getId()]);
        }

        $worksPackageIds = $searchParamsDto->getWorksPackageIds();
        $projectIds = $searchParamsDto->getProjectIds();
        $archived = $searchParamsDto->getArchived();
        $productIds = $searchParamsDto->getProductIds();

        if (is_array($worksPackageIds) && count($worksPackageIds) > 0) {
            $query->whereIn('questions.works_package_id', $worksPackageIds);
        }

        if (is_array($projectIds) && count($projectIds) > 0) {
            $query->whereIn('questions.project_id', $projectIds);
        }

        if (is_array($productIds) && count($productIds) > 0) {
            $query->whereIn('questions.product_id', $productIds);
        }

        if ($onlyUnexpired === true) {
            $query->whereRaw('STR_TO_DATE(questions.days, "%d-%m-%Y") >= now()');
        }

        if (in_array($searchParamsDto->getIsQuoted(), ['yes', 'no'])) {
            if ($searchParamsDto->getIsQuoted() === 'yes') {
                $query->whereRaw('((select a.id from answers a where a.question_id=questions.id and a.user_id=? limit 1) is not null)', [$user->getId()]);
            } else {
                $query->whereRaw('((select a.id from answers a where a.question_id=questions.id and a.user_id=? limit 1) is null)', [$user->getId()]);
            }
        }

        if (in_array($searchParamsDto->getIsIgnored(), ['yes', 'no'])) {
            if ($searchParamsDto->getIsIgnored() === 'yes') {
                $query->whereRaw('((select i.id from ignored i where i.ignorable_id=questions.id and i.ignorable_type=? and i.user_id=? limit 1) is not null)', [Question::class, $user->getId()]);
            } else {
                $query->whereRaw('((select i.id from ignored i where i.ignorable_id=questions.id and i.ignorable_type=? and i.user_id=? limit 1) is null)', [Question::class, $user->getId()]);
            }
        }

        if ($currentUser->hasRole(Role::ROLE_BRANCH_MANAGER)) {
            if ($searchParamsDto->getIsQuotedByBranch() === 'yes') {
                $query->whereRaw('((select a.id from answers a join users u on u.id=a.user_id where a.question_id=questions.id and (u.billing_user_id=? or u.id=?) limit 1) is not null)', [$currentUser->getId(), $currentUser->getId()]);
            } elseif ($searchParamsDto->getIsQuotedByBranch() === 'no') {
                $query->whereRaw('((select a.id from answers a join users u on u.id=a.user_id where a.question_id=questions.id and (u.billing_user_id=? or u.id=?) limit 1) is null)', [$currentUser->getId(), $currentUser->getId()]);
            }
        }

        if (!$searchParamsDto->getId()) {
            if ($archived) {
                $query->whereNotNull('questions.archived_at');
            } else {
                $query->whereNull('questions.archived_at');
            }
        }

        if ($user->hasRole(Role::ROLE_COMPANY_SLUG)) {
            $query = $query->selectRaw('(select (count(*)>0) from users_preferred_suppliers where supplier_id=? and user_id=questions.user_id) is_preferred_supplier', [
                $user->getId(),
            ]);

            if (!$user->getIsGlobal()) {
                $query->whereNotNull('questions.lat');

                $query->where(function ($query) use ($user) {
                    $query->inRadius($user->getLat() ?? 0, $user->getLong() ?? 0, QuestionRepository::SEARCH_RADIUS);
                    $query->orWhereRaw('((select a.id from answers a where a.question_id=questions.id and a.user_id=? limit 1) is not null)', [$user->getId()]);
                });
            }

            $query->whereIn('questions.product_id', $user->getProductIdsAttribute());
        } else if (!$user->hasRole(Role::ROLE_ADMIN_SLUG)) {
            $query->whereIn('questions.user_id', $companyUserIds);
        }

        if ($searchParamsDto->getId()) {
            $query->where([
                'questions.id' => $searchParamsDto->getId(),
            ]);
        }

        // filter out draft enquiries unless created by current user
        $query->where(function ($query) use ($user) {
            $query->where('questions.status', '=', 0)
                ->orWhere('questions.user_id', '=', $user->getId());
        });

        // filter out closed enquiries unless current user is a preferred supplier
        $query->where(function ($query) use ($user, $companyUserIds) {
            $query->where('questions.scope', '=', 0)
                ->orWhereIn('questions.user_id', $companyUserIds)
                ->orWhereIn('users.billing_user_id', function ($query) use ($user) {
                    $query->selectRaw('user_id')
                        ->from('users_preferred_suppliers')
                        ->where('users_preferred_suppliers.supplier_id', '=', $user->getId());
                });
        });

        $query = $query->orderBy(
            $searchParamsDto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME,
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        $itemsPerPage = $searchParamsDto->getItemsPerPage() ?? self::ITEMS_PER_PAGE;
        if (!$searchParamsDto->isPaginationEnabled()) {
            $itemsPerPage = PHP_INT_MAX;
        }

        $questionsIndicatorKey = sprintf(Question::NEW_ENQUIRIES_INDICATOR_CACHE, $user->getId());
        $this->redis->set($questionsIndicatorKey, Carbon::now()->format('Y-m-d H:i:s'));

        return $this->populateWithIndicator($query->paginate($itemsPerPage), $user);
    }

    public function findBySeenByMerchants(int $inquiryId, SearchParamsDto $searchParamsDto): LengthAwarePaginator
    {
        $query = $this->getMerchantsQuery($inquiryId);

        $query = $query->orderBy(
            $searchParamsDto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME,
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        $itemsPerPage = $searchParamsDto->getItemsPerPage() ?? self::ITEMS_PER_PAGE;
        if (!$searchParamsDto->isPaginationEnabled()) {
            $itemsPerPage = PHP_INT_MAX;
        }

        return $query->paginate($itemsPerPage);
    }

    public function findPreferredSuppliers(int $inquiryId, SearchParamsDto $searchParamsDto): LengthAwarePaginator
    {
        $query = $this->getMerchantsQuery($inquiryId);
        $query->having('is_preferred_supplier', '=', 1);

        $query = $query->orderBy(
            $searchParamsDto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME,
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        $itemsPerPage = $searchParamsDto->getItemsPerPage() ?? self::ITEMS_PER_PAGE;
        if (!$searchParamsDto->isPaginationEnabled()) {
            $itemsPerPage = PHP_INT_MAX;
        }

        return $query->paginate($itemsPerPage);
    }

    private function getMerchantsQuery($inquiryId): Builder
    {
        $inquiry = $this->get($inquiryId);
        $inquiryUser = $inquiry->users();
        $billingUserId = $inquiryUser->first()->getBillingUserId() ?? 0;

        $query = User::query()
            ->select(['users.id', 'users.first_name', 'users.postcode', 'users.email', 'users.phone', 'inquiry_merchant.called_at', 'inquiry_merchant.comment'])
            ->selectRaw('(select (count(*)>0) from users_preferred_suppliers ps where ps.supplier_id = users.id and (ps.user_id = ? or ps.user_id = ?)) is_preferred_supplier', [$inquiry->user_id, $billingUserId])
            ->selectRaw('((select a.id from answers a where a.user_id=users.id and a.question_id=? limit 1) is not null) as quoted', [$inquiry->id])
            ->leftJoin('product_user', 'users.id', '=', 'product_user.user_id')
            ->leftJoin('users_roles', 'users.id', '=', 'users_roles.user_id')
            ->leftJoin('roles_permissions', 'roles_permissions.role_id', '=', 'users_roles.role_id')
            ->leftJoin('permissions', 'permissions.id', '=', 'roles_permissions.permission_id')
            ->leftJoin('subscriptions', function ($join) {
                $join->on('subscriptions.user_id', '=', 'users.id');
                $join->orOn('subscriptions.user_id', '=', 'users.billing_user_id');
            })
            ->leftJoin('inquiry_merchant', function ($join) use ($inquiry) {
                $join->on('inquiry_merchant.user_id', '=', 'users.id');
                $join->on('inquiry_merchant.inquiry_id', '=', DB::raw($inquiry->id));
                $join->on('inquiry_merchant.inquiry_type', '=', DB::raw('\'purchase_hire\''));
            })
            ->where(['permissions.slug' => \App\Models\PermissionsReference::answerQuestion])
            ->where(['product_user.product_id' => $inquiry->product_id])
            ->where(function ($query) use ($inquiry) {
                return $query->inRadius($inquiry->getLat() ?? 0, $inquiry->getLong() ?? 0, QuestionRepository::SEARCH_RADIUS)
                    ->orWhere('users.is_global', '=', '1')
                    ->orWhereRaw('((select a1.id from answers a1 where a1.user_id=users.id and a1.question_id=? limit 1) is not null)', [$inquiry->id]);
            });

        $query->groupBy(['users.id', 'users.first_name', 'users.postcode', 'users.email', 'users.phone', 'inquiry_merchant.called_at', 'inquiry_merchant.comment']);

        return $query;
    }

    public function setArchived(Question $question): ?Question
    {
        $question->setArchivedAt(Carbon::now());
        $question->save();

        return $question;
    }

    public function duplicate(int $id, QuestionDto $dto): ?Question
    {
        $question = $this->get($id)->replicate();

        $question->comment = $dto->getComment();
        $question->type = $dto->getType();
        $question->days = $dto->getDays();
        $question->setCreatedAt(Carbon::now());
        $question->ref_archived_enquiry_id = $id;
        $question->archived_at = null;
        $question->status = $dto->getStatus();
        $question->scope = $dto->getScope();

        $question->save();

        return $question;
    }

    public function restore(Question $question): ?Question
    {
        $question->setArchivedAt(null);
        $question->save();

        return $question;
    }

    public function storeBatch(QuestionBatchDto $questionBatchDto, array $nationalsItems): array
    {
        DB::beginTransaction();

        $questions = [];
        try {
            /** @var QuestionDto $item */
            foreach ($questionBatchDto->getItems() as $index => $item) {
                $question = Question::create([
                    'comment' => $item->getComment(),
                    'postcode' => $item->getPostcode(),
                    'product_id' => $item->getProductId(),
                    'days' => $item->getDays(),
                    'works_package_id' => $item->getWorksPackageId(),
                    'project_id' => $item->getProjectId(),
                    'user_id' => ($questionBatchDto->getUser()) ? $questionBatchDto->getUser()->getId() : null,
                    'type' => $item->getType(),
                    'send_to_national' => $item->getSendToNational(),
                    'status' => $item->getStatus(),
                    'scope' => $item->getScope(),
                    'published_at' => $item->getStatus() === 0 ? Carbon::now() : null,
                ]);

                if (!$question) {
                    throw new \Exception('failed to create question');
                }

                if ($item->getAttachment()) {
                    foreach ($item->getAttachment() as $attachment) {
                        $question->attach($attachment);
                    }
                }

                if ($item->getManufacturerProductSelected()) {
                    $question->manufacturerProducts()->sync($item->getManufacturerProductSelected());
                }

                if ($item->getSendToNational()) {
                    foreach ($nationalsItems[$index] as $nationalItem) {
                        National::create([
                            'question_id' => $question->getId(),
                            'supplier_id' => (int)$nationalItem['supplier_id'],
                            'email' => $nationalItem['email'],
                            'contact_name' => $nationalItem['contact_name'],
                            'account_number' => $nationalItem['account_number']
                        ]);
                    }
                }

                $questions[] = $question;
            }
        } catch (\Exception $e) {
            DB::rollBack();

            throw new \Exception('failed to create the question: ' . $e->getMessage());
        }

        DB::commit();

        return $questions;
    }

    public function store(QuestionDto $questionDto): ?Question
    {
        /* @var Question $question */
        $question = Question::create([
            'comment' => $questionDto->getComment(),
            'postcode' => $questionDto->getPostcode(),
            'product_id' => $questionDto->getProductId(),
            'days' => $questionDto->getDays(),
            'works_package_id' => $questionDto->getWorksPackageId(),
            'project_id' => $questionDto->getProjectId(),
            'user_id' => $questionDto->getUserId(),
            'type' => $questionDto->getType(),
            'status' => $questionDto->getStatus(),
            'scope' => $questionDto->getScope(),
        ]);

        if ($questionDto->getStatus() === 0) {
            $question->setPublishedAt(Carbon::now());
        }

        if ($questionDto->getAttachment()) {
            foreach ($questionDto->getAttachment() as $attachment) {
                $question->attach($attachment);
            }
        }

        if ($questionDto->getManufacturerProductSelected()) {
            $question->manufacturerProducts()->sync($questionDto->getManufacturerProductSelected());
        }

        return $question;
    }

    public function update(QuestionDto $questionDto, int $id): ?Question
    {
        $question = $this->get($id);

        $question->setPostcode($questionDto->getPostcode());
        $question->setProductId($questionDto->getProductId());
        $question->setDays($questionDto->getDays());
        $question->setComment($questionDto->getComment());
        $question->setWorksPackageId($questionDto->getWorksPackageId());
        $question->setProjectId($questionDto->getProjectId());
        $question->setType($questionDto->getType());
        $question->setStatus($questionDto->getStatus());
        $question->setScope($questionDto->getScope());

        if ($question->getPublishedAt() === null && $questionDto->getStatus() === 0) {
            $question->setPublishedAt(Carbon::now());
        }

        if ($question->save()) {
            if ($questionDto->getManufacturerProductSelected()) {
                $question->manufacturerProducts()->sync($questionDto->getManufacturerProductSelected());
            }

            return $question;
        }

        return null;
    }

    public function getProjectOptions(string $search, User $user, bool $archived): Collection
    {
        $companyUserIds = $user->getCompanyUsers();

        $query = Question::query()->select('projects.id', 'projects.name')
            ->join('projects', 'projects.id', '=', 'questions.project_id');

        if (!empty($search)) {
            $query->where('projects.name', 'like', '%' . $search . '%');
        }

        $query->whereNotNull('projects.name');
        $query->where('projects.name', '!=', '');

        if ($archived) {
            $query->whereNotNull('questions.archived_at');
        } else {
            $query->whereNull('questions.archived_at');
        }

        if ($user->hasRole(Role::ROLE_USER_SLUG)) {
            $query->where([
                'questions.user_id' => $user->getId(),
            ]);
        }

        if ($user->hasRole(Role::ROLE_COMPANY_SLUG)) {
            $query->whereNotNull('questions.lat');
            $query->groupBy('questions.lat');
            $query->groupBy('questions.long');

            if (!$user->getIsGlobal()) {
                $query->inRadius($user->getLat() ?? 0, $user->getLong() ?? 0, self::SEARCH_RADIUS);
            }

            $query->whereIn('questions.product_id', $user->getProductIdsAttribute());
        }

        $query->groupBy('projects.id', 'projects.name');
        $query->orderBy('projects.name', 'asc');

        $items = $query->get();

        if ($user->hasRole(Role::ROLE_USER_SLUG)) {
            $itemsSupplyFit = SupplyFitEnquiryQuote::query()->select('projects.id', 'projects.name')
                ->join('supply_fit_enquiries', 'supply_fit_enquiries.id', '=', 'supply_fit_enquiry_quotes.enquiry_id')
                ->join('projects', 'projects.id', '=', 'supply_fit_enquiries.project_id')
                ->whereIn('supply_fit_enquiry_quotes.user_id', $companyUserIds)
                ->whereNotNull('supply_fit_enquiry_quotes.quote_accepted_at')
                ->groupBy('projects.id', 'projects.name')
                ->orderBy('projects.name', 'asc')->get();

            $items = $items->merge($itemsSupplyFit);
        }

        return $items;
    }

    public function getWorksPackageOptions(string $search, User $user, bool $archived, array $projectIds = []): Collection
    {
        $companyUserIds = $user->getCompanyUsers();

        $query = Question::query()
            ->select('works_packages.id', 'works_packages.name', 'questions.postcode')
            ->join('works_packages', 'works_packages.id', '=', 'questions.works_package_id');

        if (!empty($search)) {
            $query->where('works_packages.name', 'like', '%' . $search . '%');
        }

        $query->whereNotNull('works_packages.name');
        $query->where('works_packages.name', '!=', '');

        if ($archived) {
            $query->whereNotNull('questions.archived_at');
        } else {
            $query->whereNull('questions.archived_at');
        }

        if (!empty($projectIds)) {
            $query->whereIn('works_packages.project_id', $projectIds);
        }

        if ($user->hasRole(Role::ROLE_USER_SLUG)) {
            $query->where([
                'questions.user_id' => $user->getId(),
            ]);
        }

        if ($user->hasRole(Role::ROLE_COMPANY_SLUG)) {
            $query->whereNotNull('questions.lat');
            $query->groupBy('questions.lat');
            $query->groupBy('questions.long');

            if (!$user->getIsGlobal()) {
                $query->inRadius($user->getLat() ?? 0, $user->getLong() ?? 0, self::SEARCH_RADIUS);
            }

            $query->whereIn('questions.product_id', $user->getProductIdsAttribute());
        }

        $query->groupBy('works_packages.id', 'works_packages.name', 'questions.postcode');
        $query->orderBy('works_packages.name', 'asc');

        $items = $query->get();

        if ($user->hasRole(Role::ROLE_USER_SLUG)) {
            $itemsSupplyFitQuery = SupplyFitEnquiryQuote::query()
                ->select(['works_packages.id', 'works_packages.name', 'supply_fit_enquiries.postcode'])
                ->join('supply_fit_enquiries', 'supply_fit_enquiries.id', '=', 'supply_fit_enquiry_quotes.enquiry_id')
                ->join('works_packages', 'works_packages.id', '=', 'supply_fit_enquiries.works_package_id')
                ->whereIn('supply_fit_enquiry_quotes.user_id', $companyUserIds)
                ->whereNotNull('supply_fit_enquiry_quotes.quote_accepted_at');

            if (!empty($projectIds)) {
                $itemsSupplyFitQuery->whereIn('works_packages.project_id', $projectIds);
            }

            $itemsSupplyFitQuery->groupBy('works_packages.id', 'works_packages.name', 'supply_fit_enquiries.postcode')
                ->orderBy('works_packages.name', 'asc');

            $itemsSupplyFit = $itemsSupplyFitQuery->get();

            $items = $items->merge($itemsSupplyFit);
        }

        return $items;
    }

    public function getEmailForQuestion(int $questionId): ?string
    {
        $question = $this->get($questionId);
        if (!$question) {
            return null;
        }

        /** @var User $user */
        $user = $question->users()->first();
        if (!$user) {
            return null;
        }

        return $user->getEmail();
    }

    public function toggleIgnore(Question $question, User $user): ?Ignored
    {
        $item = Ignored::query()->where([
            'ignorable_type' => Question::class,
            'ignorable_id' => $question->id,
            'user_id' => $user->id,
        ])->first();
        if ($item) {
            $item->delete();

            return null;
        }

        return Ignored::create([
            'ignorable_type' => Question::class,
            'ignorable_id' => $question->id,
            'user_id' => $user->id,
        ]);
    }

    public function delete(int $questionId): bool
    {
        $question = $this->get($questionId);
        if (!$question) {
            return false;
        }

        foreach ($question->getAnswers() as $answer) {
            $answer->delete();
        }

        return $question->delete();
    }

    public function toggleAssign(Question $question, User $user): void
    {
        if (!$user->can_assign_to_enquiries) {
            return;
        }

        $ids = $question->assignedUsers()->pluck('questions_assigned_users.user_id')->toArray();
        if (!in_array($user->id, $ids)) {
            $question->assignedUsers()->attach($user->id);

            return;
        }

        $question->assignedUsers()->detach($user->id);
    }

    public function getTotalEnquiriesSendAll(): int
    {
        return Question::join('users', 'users.id', '=', 'questions.user_id')
            ->join('products', 'products.id', '=', 'questions.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->where('questions.id', '>', 25)
            ->where('users.is_test_account', '=', 0)
            ->count();
    }

    public function getTotalEnquiriesSend(User $user = null): int
    {
        return Question::join('users', 'users.id', '=', 'questions.user_id')
            ->join('products', 'products.id', '=', 'questions.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->where(['user_id' => $user->getId()])
            ->where('questions.id', '>', 25)
            ->count();
    }

    public function getTotalEnquiries(User $user = null): int
    {
        return Question::join('users', 'users.id', '=', 'questions.user_id')
            ->join('products', 'products.id', '=', 'questions.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->where('questions.id', '>', 25)
            ->where('users.is_test_account', '=', 0)
            ->count();
    }

    public function getMerchantsReceived(User $user = null): int
    {
        /** get product ids */
        $query = DB::table('questions')->select('product_id')->distinct();
        if ($user) {
            $query->where(['user_id' => $user->getId()]);
        }
        $productIds = $query->pluck('product_id');

        /** count global */
        $qtyGlobal = 0;
        if ($productIds) {
            $query = DB::table('users')->whereRaw('users.is_global');
            $query->leftJoin('product_user', 'product_user.user_id', '=', 'users.id');
            $query->whereIn('product_id', $productIds);

            $qtyGlobal += $query->count();
        };

        $query = DB::table('users')
            ->select('users.lat as user_lat', 'users.long as user_lon', 'users.is_global', 'questions.lat', 'questions.long')
            ->leftJoin('product_user', 'product_user.user_id', '=', 'users.id')
            ->leftJoin('questions', 'product_user.product_id', '=', 'questions.product_id')
            ->whereRaw('users.is_global=false')
            ->where('users.is_test_account', '=', 0)
            ->where('questions.id', '>', 25)
            ->where(function ($q2) {
                $q2->whereNotNull('questions.lat');
                $q2->whereNotNull('questions.long');
                $q2->whereNotNull('users.lat');
                $q2->whereNotNull('users.long');
            });

        if ($user) {
            $query->where(['questions.user_id' => $user->getId()]);
        }

        $items = $query->get();

        $total = $qtyGlobal;
        foreach ($items as $item) {
            $haversine = (0.62137 * (6371 * acos(cos(deg2rad($item->lat))
                        * cos(deg2rad($item->user_lat))
                        * cos(deg2rad($item->user_lon)
                            - deg2rad($item->long))
                        + sin(deg2rad($item->lat))
                        * sin(deg2rad($item->user_lat)))));

            if ($haversine <= self::SEARCH_RADIUS) {
                ++$total;
            }
        }

        return $total;
    }

    public function categoriesPercentagesSelectedMerchant(User $user = null)
    {
        $query = Answer::select('products.name', DB::raw('count(*) as total'))
            ->join('questions', 'questions.id', '=', 'answers.question_id')
            ->join('products', 'products.id', '=', 'questions.product_id')
            ->groupBy('products.name');

        if ($user) {
            $query->where(['answers.user_id' => $user->getId()]);
        }

        return $query->get();
    }

    public function categoriesPercentagesSelectedContractor(User $user = null)
    {
        $query = Question::select('products.name', DB::raw('count(*) as total'))
            ->join('products', 'products.id', '=', 'questions.product_id')
            ->groupBy('products.name');

        if ($user) {
            $query->where(['questions.user_id' => $user->getId()]);
        }

        return $query->get();
    }

    public function getMatchedEnquiries(User $user = null): int
    {
        $query = Question::join('users', 'users.id', '=', 'questions.user_id')
            ->join('products', 'products.id', '=', 'questions.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->where('questions.id', '>', 25)
            ->where('users.is_test_account', '=', 0);

        if ($user) {
            if (!$user->getIsGlobal()) {
                $query->whereNotNull('questions.lat');

                $query->inRadius($user->getLat() ?? 0, $user->getLong() ?? 0, self::SEARCH_RADIUS);
            }

            $query->whereIn('questions.product_id', $user->getProductIdsAttribute());
        }

        return $query->count();
    }

    public function countByProductId(int $productId): int
    {
        return Question::where(['product_id' => $productId])->count();
    }

    public function getPreferredSuppliers(int $questionId): Collection
    {
        $query = User::query()->select('users.*')
            ->join('users_preferred_suppliers', 'users_preferred_suppliers.supplier_id', '=', 'users.id')
            ->join('users as u2', 'u2.billing_user_id', '=', 'users_preferred_suppliers.user_id')
            ->join('questions', 'questions.user_id', '=', 'u2.id')
            ->where('questions.id', '=', $questionId);

        return $query->get();
    }
}
