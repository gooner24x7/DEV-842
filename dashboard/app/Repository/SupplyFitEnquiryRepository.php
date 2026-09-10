<?php
declare(strict_types=1);

namespace App\Repository;

use App\Dto\Question\SearchParamsDto;
use App\Dto\Questionnaire\SessionDto;
use App\Dto\SupplyFitEnquiry\SupplyFitEnquiryDto;
use App\Http\Controllers\MessagesController;
use App\Mail\SupplyFitEnquiryCreated;
use App\Models\Message;
use App\Models\Role;
use App\Models\SupplyFitEnquiry;
use App\Models\SupplyFitEnquiryBatch;
use App\Models\SupplyFitEnquiryQuote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class QuestionRepository.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SupplyFitEnquiryRepository
{
    public const string DEFAULT_ORDER_FIELD_NAME = 'supply_fit_enquiries.id';
    public const int ITEMS_PER_PAGE = 20;
    public const int SEARCH_RADIUS = 40;

    private \Redis $redis;
    private UserRepository $userRepository;
    private WorksPackagesRepository $worksPackagesRepository;

    public function __construct(\Redis $redis, UserRepository $userRepository, WorksPackagesRepository $worksPackagesRepository)
    {
        $this->redis = $redis;
        $this->userRepository = $userRepository;
        $this->worksPackagesRepository = $worksPackagesRepository;
    }

    public function find(SearchParamsDto $searchParamsDto, User $user): LengthAwarePaginator
    {
        //$companyUserIds = $user->getCompanyUsers();

        $query = SupplyFitEnquiry::join('users', 'users.id', '=', 'supply_fit_enquiries.user_id')
            ->join('products', 'products.id', '=', 'supply_fit_enquiries.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('projects', 'projects.id', '=', 'supply_fit_enquiries.project_id')
            ->leftJoin('works_packages', 'works_packages.id', '=', 'supply_fit_enquiries.works_package_id')
            ->leftJoin('questionnaire_sessions', function ($join) use ($user) {
                $join->on('questionnaire_sessions.inquiry_id', '=', 'supply_fit_enquiries.id')
                    ->where('questionnaire_sessions.user_id', '=', $user->getId());
            })
            ->select([
                'supply_fit_enquiries.id',
                'supply_fit_enquiries.type',
                'supply_fit_enquiries.user_id',
                'supply_fit_enquiries.project_id',
                'supply_fit_enquiries.works_package_id',
                'supply_fit_enquiries.postcode',
                'supply_fit_enquiries.days',
                'supply_fit_enquiries.actual_starting_date',
                'supply_fit_enquiries.assumed_end_date',
                'supply_fit_enquiries.actual_end_date',
                'supply_fit_enquiries.comment',
                'supply_fit_enquiries.created_at',
                'supply_fit_enquiries.ref_archived_enquiry_id',
                'supply_fit_enquiries.status',
                'supply_fit_enquiries.scope',
                'supply_fit_enquiries.published_at',
                'supply_fit_enquiries.archived_at',
                'supply_fit_enquiries.lat',
                'supply_fit_enquiries.long',
                'products.name',
                'supply_fit_enquiries.product_id',
                'users.first_name',
                'users.last_name',
                'categories.type as product_type',
                'questionnaire_sessions.hash as session_hash',
                'questionnaire_sessions.is_answered as session_answered',
                'projects.name as project_name',
                'works_packages.name as works_package_name',
            ]);

        $projectIds = $searchParamsDto->getProjectIds();
        $worksPackageIds = $searchParamsDto->getWorksPackageIds();
        $productIds = $searchParamsDto->getProductIds();
        $archived = $searchParamsDto->getArchived();
        $isAnswered = $searchParamsDto->getIsAnswered();

        if ($isAnswered !== null) {
            $query->where('questionnaire_sessions.is_answered', $isAnswered);
        }

        if (!empty($projectIds)) {
            $query->whereIn('supply_fit_enquiries.project_id', $projectIds);
        }
        if (!empty($worksPackageIds)) {
            $query->whereIn('supply_fit_enquiries.works_package_id', $worksPackageIds);
        }

        if (!empty($productIds)) {
            $query->whereIn('supply_fit_enquiries.product_id', $productIds);
        }

        if ($searchParamsDto->getId()) {
            $query->where([
                'supply_fit_enquiries.id' => $searchParamsDto->getId(),
            ]);
        } else {
            if ($archived) {
                $query->whereNotNull('supply_fit_enquiries.archived_at');
            } else {
                $query->whereNull('supply_fit_enquiries.archived_at');
            }
        }

        // filter out draft enquiries unless created by current user
        $query->where(function ($query) use ($user) {
            $query->where('supply_fit_enquiries.status', '=', 1)
                ->orWhere('supply_fit_enquiries.user_id', '=', $user->getId());
        });

        // filter by radius/products/scope
        if ($user->hasRole(Role::ROLE_USER_SLUG)) {
            $productIds = $user->getProductIdsAttribute()->toArray();
            $currentUserId = $user->getId();

            $query->where(function ($query) use ($user, $productIds, $currentUserId) {
                // scope 1/other and scope 2 both require the user's product and to be in radius
                // scope 2 additionally requires the user to be a preferred subcontractor
                $query->where(function ($query) use ($user, $productIds, $currentUserId) {
                    $query->whereIn('supply_fit_enquiries.product_id', $productIds);
                    $this->applyRadiusFilter($query, $user);
                    $query->where(function ($query) use ($currentUserId) {
                        $query->whereNotIn('supply_fit_enquiries.scope', [2, 3])
                            ->orWhere(function ($query) use ($currentUserId) {
                                $query->where('supply_fit_enquiries.scope', '=', 2)
                                    ->whereExists(function ($query) use ($currentUserId) {
                                        $query->selectRaw('1')
                                            ->from('users_preferred_subcontractors as ups')
                                            ->join('users as u2', 'u2.billing_user_id', '=', 'ups.user_id')
                                            ->join('supply_fit_enquiries as sfe', 'sfe.user_id', '=', 'u2.id')
                                            ->where('ups.subcontractor_id', '=', $currentUserId)
                                            ->whereColumn('sfe.id', 'supply_fit_enquiries.id');
                                    });
                            });
                    });
                })
                    // scope 3: the user must be assigned to the works package
                    ->orWhere(function ($query) use ($currentUserId) {
                        $query->where('supply_fit_enquiries.scope', '=', 3)
                            ->whereIn('supply_fit_enquiries.works_package_id', function ($query) use ($currentUserId) {
                                $query->select('works_package_id')
                                    ->from('works_packages_assigned_users')
                                    ->where('user_id', '=', $currentUserId);
                            });
                    });
            });
        } else if (!$user->hasRole(Role::ROLE_ADMIN_SLUG)) {
            $query->whereIn('supply_fit_enquiries.user_id', $user->getCompanyUsers());
        }

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

        $response->getCollection()->transform(static function (SupplyFitEnquiry $enquiry) use ($user, $redis): SupplyFitEnquiry {
            $newMsgIndicatorKey = sprintf(Message::NEW_MESSAGE_INQUIRY_INDICATOR_CACHE, $enquiry->getId(), $user->getId(), MessagesController::MESSAGE_TYPE_SUPPLY_FIT_ENQUIRY);
            $qty = Message::query()
                ->where('supply_fit_enquiry_id', '=', $enquiry->getId())
                ->where('interlocutor_id', '=', $user->getId())
                ->where('created_at', '>', $redis->get($newMsgIndicatorKey))
                ->count('id');

            $enquiry->setNewMsgQty($qty);

            return $enquiry;
        });

        return $response;
    }

    /**
     * Constrain the query to enquiries within the user's search radius, mirroring the previous
     * per-row logic: global users are always considered in radius, and a user without coordinates
     * matches nothing.
     */
    private function applyRadiusFilter($query, User $user): void
    {
        if ($user->getIsGlobal()) {
            return;
        }

        if ($user->getLat() === null) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->inRadius($user->getLat(), $user->getLong() ?? 0, self::SEARCH_RADIUS, false);
    }

    public function get(int $id): ?SupplyFitEnquiry
    {
        $enquiry = SupplyFitEnquiry::where(['id' => $id])->with('product')->first();
        if ($enquiry) {
            return $enquiry;
        }

        return null;
    }

    public function setArchived(SupplyFitEnquiry $enquiry): ?SupplyFitEnquiry
    {
        $enquiry->setArchivedAt(Carbon::now());
        $enquiry->save();

        return $enquiry;
    }

    public function duplicate(int $id, SupplyFitEnquiryDto $dto): ?SupplyFitEnquiry
    {
        $enquiry = $this->get($id)->replicate();

        $enquiry->comment = $dto->getComment();
        $enquiry->days = $dto->getDays();
        $enquiry->setCreatedAt(Carbon::now());
        $enquiry->ref_archived_enquiry_id = $id;
        $enquiry->archived_at = null;
        $enquiry->actual_starting_date = $dto->getActualStartingDate();
        $enquiry->assumed_end_date = $dto->getAssumedEndDate();
        $enquiry->actual_end_date = $dto->getActualEndDate();
        $enquiry->type = $dto->getType();
        $enquiry->status = $dto->getStatus();
        $enquiry->scope = $dto->getScope();
        $enquiry->save();

        return $enquiry;
    }

    public function restore(SupplyFitEnquiry $enquiry): ?SupplyFitEnquiry
    {
        $enquiry->setArchivedAt(null);
        $enquiry->save();

        return $enquiry;
    }

    /**
     * @throws \Exception
     */
    public function delete(int $enquiryId): bool
    {
        $enquiry = $this->get($enquiryId);
        if (!$enquiry) {
            return false;
        }

        foreach ($enquiry->getQuotes() as $quote) {
            $quote->delete();
        }

        return $enquiry->delete();
    }

    public function doProjectsBelongToUser(array $projectIds, User $user): bool
    {
        if (SupplyFitEnquiry::whereIn('project_id', $projectIds)->where('user_id', '=', $user->getId())->first()) {
            return true;
        }

        return false;
    }

    /**
     * @throws \Exception
     */
    public function storeBatch(SupplyFitEnquiryDto $dto): array
    {
        DB::beginTransaction();

        $batch = SupplyFitEnquiryBatch::create([
            'user_id' => $dto->getUserId()
        ]);

        $enquiries = [];
        $worksPackages = $dto->getWorksPackages();
        try {
            foreach ($worksPackages as $wp) {
                $enquiry = SupplyFitEnquiry::create([
                    'user_id' => $dto->getUserId(),
                    'batch_id' => $batch->getId(),
                    'project_id' => $dto->getProjectId(),
                    'works_package_id' => $wp->id,
                    'comment' => $dto->getComment(),
                    'postcode' => $dto->getPostcode(),
                    'product_id' => $wp->product->id ?? null,
                    'days' => $dto->getDays(),
                    'actual_starting_date' => $dto->getActualStartingDate(),
                    'assumed_end_date' => $dto->getAssumedEndDate(),
                    'actual_end_date' => $dto->getActualEndDate(),
                    'type' => $dto->getType(),
                    'status' => $dto->getStatus(),
                    'scope' => $dto->getScope(),
                    'published_at' => $dto->getStatus() === 1 ? Carbon::now() : null,
                ]);

                if (!$enquiry) {
                    throw new \Exception('failed to create marketplace enquiry');
                }

                if ($dto->getAttachment()) {
                    foreach ($dto->getAttachment() as $attachment) {
                        $enquiry->attach($attachment);
                    }
                }

                // attach project files
                $project_attachment = $dto->getProjectAttachment();
                if (!empty($project_attachment)) {
                    foreach ($project_attachment as $group) {
                        $path = storage_path('app/public/projects/' . $group['documents'][0]['project_id'] . '/' . $group['documents'][0]['filename']);
                        $enquiry->attach($path);
                    }
                }

                $enquiries[] = $enquiry;
            }
        } catch (\Exception $e) {
            DB::rollBack();

            throw new \Exception('failed to create marketplace enquiry ' . $e->getMessage());
        }

        DB::commit();

        return $enquiries;
    }

    public function getByBatchId(int $batchId) : Collection
    {
        return SupplyFitEnquiry::where(['batch_id' => $batchId])->get();
    }

    public function getLatestBatch() : ?Collection
    {
        $batch = SupplyFitEnquiryBatch::whereNull('processed_at')
            ->whereDoesntHave('enquiries', function ($query) {
                $query->where('status', '=', 2);
            })
            ->orderBy('id', 'desc')
            ->first();

        return $batch?->enquiries()->get();
    }

    public function setBatchProcessed(int $batchId) : bool
    {
        $batch = SupplyFitEnquiryBatch::where(['id' => $batchId])->first();
        $batch->setProcessedAt(Carbon::now());

        return $batch->save();
    }

    /**
     * @throws \Exception
     */
    public function store(SupplyFitEnquiryDto $dto): ?SupplyFitEnquiry
    {
        $worksPackage = $dto->getWorksPackages()[0] ?? null;

        $enquiry = SupplyFitEnquiry::create([
            'comment' => $dto->getComment(),
            'postcode' => $dto->getPostcode(),
            'product_id' => $worksPackage->product->id ?? null,
            'days' => $dto->getDays(),
            'actual_starting_date' => $dto->getActualStartingDate(),
            'assumed_end_date' => $dto->getAssumedEndDate(),
            'actual_end_date' => $dto->getActualEndDate(),
            'project_id' => $dto->getProjectId(),
            'works_package_id' => $worksPackage->id ?? null,
            'user_id' => $dto->getUserId(),
            'type' => $dto->getType(),
            'status' => $dto->getStatus(),
            'scope' => $dto->getScope(),
            'published_at' => $dto->getStatus() === 1 ? Carbon::now() : null,
        ]);

        if (!$enquiry) {
            return null;
        }

        if ($dto->getAttachment()) {
            foreach ($dto->getAttachment() as $attachment) {
                $enquiry->attach($attachment);
            }
        }

        return $enquiry;
    }

    public function update(SupplyFitEnquiryDto $dto, int $id): ?SupplyFitEnquiry
    {
        $enquiry = $this->get($id);

        if (!$enquiry) {
            return null;
        }

        $worksPackageId = $dto->getWorksPackages()[0]->id ?? null;

        $enquiry->setPostcode($dto->getPostcode());
        $enquiry->setProductId($dto->getProductId());
        $enquiry->setDays($dto->getDays());
        $enquiry->setComment($dto->getComment());
        $enquiry->setProjectId($dto->getProjectId());
        $enquiry->setWorksPackageId($worksPackageId);
        $enquiry->setType($dto->getType());
        $enquiry->setActualStartingDate($dto->getActualStartingDate());
        $enquiry->setAssumedEndDate($dto->getAssumedEndDate());
        $enquiry->setActualEndDate($dto->getActualEndDate());
        $enquiry->setStatus($dto->getStatus());
        $enquiry->setScope($dto->getScope());

        if ($enquiry->published_at === null && $dto->getStatus() === 1) {
            $enquiry->setPublishedAt(Carbon::now());
        }

        if ($dto->getAttachment()) {
            foreach ($dto->getAttachment() as $attachment) {
                $enquiry->attach($attachment);
            }
        }

        $enquiry->save();

        return $enquiry;
    }

    public function getOverview(int $id): array
    {
        $enquiry = SupplyFitEnquiry::where(['id' => $id])->first();

        $query = SupplyFitEnquiryQuote::query()
            ->select([
                DB::raw('supply_fit_enquiry_quotes.price'),
                'supply_fit_enquiry_quotes.quote_accepted_at',
            ])
            ->join('users', 'users.id', '=', 'supply_fit_enquiry_quotes.user_id')
            ->selectRadius($enquiry)
            ->where([
                'supply_fit_enquiry_quotes.enquiry_id' => $id,
            ]);

        $items = $query->get();

        $actualPrice = 0;
        $minPrice = PHP_INT_MAX;
        $maxPrice = 0;
        $actualEsg = 0;
        $minEsg = PHP_INT_MAX;
        $maxEsg = 0;
        foreach ($items as $item) {
            $minPrice = min($minPrice, $item->price);
            $maxPrice = max($maxPrice, $item->price);
            $esg = ($item->max_distance > 0) ? (100 - ($item->distance * 4206.8) * 100 / ($item->max_distance * 4206.8)) : 0;
            $minEsg = min($minEsg, $esg);
            $maxEsg = max($maxEsg, $esg);
            if ($item->quote_accepted_at) {
                $actualPrice = max($actualPrice, $item->price);
                $actualEsg = $esg;
            }
        }

        return [
            'actualPrice' => $actualPrice,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            'totalSavedPrice' => max(0, $maxPrice - $actualPrice),
            'actualEsg' => $actualEsg,
            'minEsg' => $minEsg,
            'maxEsg' => $maxEsg,
            'totalSavedEsg' => max(0, $maxEsg - $actualEsg),
        ];
    }

    public function getProjectOptions(string $search, User $user, bool $archived): Collection
    {
        $companyUsers = $user->getCompanyUsers();

        $columns = [
            'projects.id',
            'projects.user_id',
            'projects.group_id',
            'projects.stage',
            'projects.name',
            'projects.postcode',
            'projects.date_start',
            'projects.date_end',
            'projects.created_at',
        ];

        $query = SupplyFitEnquiry::query()->select($columns)
            ->join('projects', 'projects.id', '=', 'supply_fit_enquiries.project_id')
            ->whereIn('projects.user_id', $companyUsers)
            ->whereNotNull('projects.name')
            ->where('projects.name', '!=', '');

        if (!empty($search)) {
            $query->where('projects.name', 'like', '%' . $search . '%');
        }

        if ($archived) {
            $query->whereNotNull('projects.archived_at');
        } else {
            $query->whereNull('projects.archived_at');
        }

        if ($user->hasRole(Role::ROLE_USER_SLUG)) {
            $query->whereNotNull('supply_fit_enquiries.lat');
            $query->inRadius($user->getLat() ?? 0, $user->getLong() ?? 0, self::SEARCH_RADIUS, false);
            $query->whereIn('supply_fit_enquiries.product_id', $user->getProductIdsAttribute());
        }

        $query->groupBy($columns);
        $query->orderBy('projects.name', 'asc');

        return $query->get();
    }

    public function getWorksPackageOptions(string $search, User $user, array $projectIds): Collection
    {
        $companyUsers = $user->getCompanyUsers();

        $query = SupplyFitEnquiry::query()->select('works_packages.id', 'works_packages.name', 'supply_fit_enquiries.postcode')
            ->join('works_packages', 'works_packages.id', '=', 'supply_fit_enquiries.works_package_id')
            ->whereIn('works_packages.user_id', $companyUsers)
            ->whereNotNull('works_packages.name')
            ->where('works_packages.name', '!=', '');

        if (!empty($search)) {
            $query->where('works_packages.name', 'like', '%' . $search . '%');
        }

        if (!empty($projectIds)) {
            $query->whereIn('works_packages.project_id', $projectIds);
        }

        if ($user->hasRole(Role::ROLE_USER_SLUG)) {
            $query->whereNotNull('supply_fit_enquiries.lat');
            $query->inRadius($user->getLat() ?? 0, $user->getLong() ?? 0, self::SEARCH_RADIUS, false);
            $query->whereIn('supply_fit_enquiries.product_id', $user->getProductIdsAttribute());
        }

        $query->groupBy('works_packages.id', 'works_packages.name', 'supply_fit_enquiries.postcode');
        $query->orderBy('works_packages.name', 'asc');

        return $query->get();
    }

    public function getEmailForEnquiry(int $id): ?string
    {
        $enquiry = $this->get($id);
        if (!$enquiry) {
            return null;
        }

        /** @var User $user */
        $user = $enquiry->users()->first();
        return $user?->getEmail();
    }

    public function getPreferredSubcontractors(int $enquiryId): Collection
    {
        $query = User::query()->select('users.*')
            ->join('users_preferred_subcontractors', 'users_preferred_subcontractors.subcontractor_id', '=', 'users.id')
            ->join('users as u2', 'u2.billing_user_id', '=', 'users_preferred_subcontractors.user_id')
            ->join('supply_fit_enquiries', 'supply_fit_enquiries.user_id', '=', 'u2.id')
            ->where('supply_fit_enquiries.id', '=', $enquiryId);

        return $query->get();
    }
}
