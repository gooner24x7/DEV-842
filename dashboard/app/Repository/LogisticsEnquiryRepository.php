<?php
declare(strict_types=1);

namespace App\Repository;

use App\Dto\Logistics\LogisticsEnquiryDto;
use App\Dto\Logistics\SearchParamsDto;
use App\Models\Answer;
use App\Models\Ignored;
use App\Models\LogisticsContact;
use App\Models\LogisticsEnquiry;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class LogisticsEnquiryRepository
 *
 */
class LogisticsEnquiryRepository
{
    const string DEFAULT_ORDER_FIELD_NAME = 'logistics_enquiries.id';
    const int ITEMS_PER_PAGE = 20;
    const int SEARCH_RADIUS = 40;

    private \Redis $redis;
    private UserRepository $userRepository;

    public function __construct(\Redis $redis, UserRepository $userRepository)
    {
        $this->redis = $redis;
        $this->userRepository = $userRepository;
    }

    public function get(int $id): ?LogisticsEnquiry
    {
        $enquiry = LogisticsEnquiry::where(['id' => $id])->first();

        if (!empty($enquiry)) {
            return $enquiry;
        }

        return null;
    }

    public function find(SearchParamsDto $searchParamsDto, User $user): LengthAwarePaginator
    {
        $query = LogisticsEnquiry::query()
            ->select([
                'logistics_enquiries.*',
                'users.first_name',
                'projects.name as project_name',
                'works_packages.name as works_package_name',
            ])
            ->addSelect(DB::raw('(SELECT `lat` FROM logistics_contacts WHERE enquiry_id = logistics_enquiries.id AND type = 0) as collect_lat'))
            ->addSelect(DB::raw('(SELECT `long` FROM logistics_contacts WHERE enquiry_id = logistics_enquiries.id AND type = 0) as collect_long'))
            ->addSelect(DB::raw('(SELECT `lat` FROM logistics_contacts WHERE enquiry_id = logistics_enquiries.id AND type = 1) as delivery_lat'))
            ->addSelect(DB::raw('(SELECT `long` FROM logistics_contacts WHERE enquiry_id = logistics_enquiries.id AND type = 1) as delivery_long'))
            ->selectRaw("DATE_FORMAT(logistics_enquiries.collect_date, '%d-%m-%Y') as collect_date")
            ->selectRaw("DATE_FORMAT(logistics_enquiries.delivery_date, '%d-%m-%Y') as delivery_date")
            ->selectRaw('((select q.id from logistics_quotes q where q.enquiry_id=logistics_enquiries.id and q.user_id=? limit 1) is not null) as is_quoted', [$user->getId()])
            ->selectRaw('((select i.id from ignored i where i.ignorable_id=logistics_enquiries.id and i.ignorable_type=? and i.user_id=? limit 1) is not null) as is_ignored', [LogisticsEnquiry::class, $user->getId()])
            ->leftJoin('users', 'users.id', '=', 'logistics_enquiries.user_id')
            ->leftJoin('projects', 'projects.id', '=', 'logistics_enquiries.project_id')
            ->leftJoin('works_packages', 'works_packages.id', '=', 'logistics_enquiries.works_package_id');

        $worksPackageIds = $searchParamsDto->getWorksPackageIds();
        $projectIds = $searchParamsDto->getProjectIds();
        $archived = $searchParamsDto->getArchived();

        if (is_array($worksPackageIds) && count($worksPackageIds) > 0) {
            $query->whereIn('logistics_enquiries.works_package_id', $worksPackageIds);
        }

        if (is_array($projectIds) && count($projectIds) > 0) {
            $query->whereIn('logistics_enquiries.project_id', $projectIds);
        }

        if ($searchParamsDto->getId()) {
            $query->where([
                'logistics_enquiries.id' => $searchParamsDto->getId(),
            ]);
        } else {
            if ($archived) {
                $query->whereNotNull('logistics_enquiries.archived_at');
            } else {
                $query->whereNull('logistics_enquiries.archived_at');
            }
        }

        if (in_array($searchParamsDto->getIsQuoted(), ['yes', 'no'])) {
            if ($searchParamsDto->getIsQuoted() === 'yes') {
                $query->whereRaw('((select q.id from logistics_quotes q where q.enquiry_id=logistics_enquiries.id and q.user_id=? limit 1) is not null)', [$user->getId()]);
            } else {
                $query->whereRaw('((select q.id from logistics_quotes q where q.enquiry_id=logistics_enquiries.id and q.user_id=? limit 1) is null)', [$user->getId()]);
            }
        }

        if (in_array($searchParamsDto->getIsIgnored(), ['yes', 'no'])) {
            if ($searchParamsDto->getIsIgnored() === 'yes') {
                $query->whereRaw('((select i.id from ignored i where i.ignorable_id=logistics_enquiries.id and i.ignorable_type=? and i.user_id=? limit 1) is not null)', [LogisticsEnquiry::class, $user->getId()]);
            } else {
                $query->whereRaw('((select i.id from ignored i where i.ignorable_id=logistics_enquiries.id and i.ignorable_type=? and i.user_id=? limit 1) is null)', [LogisticsEnquiry::class, $user->getId()]);
            }
        }

        if ($user->hasRole(Role::ROLE_COMPANY_SLUG) || $user->hasRole(Role::ROLE_MANUFACTURER)) {
            $query->where('logistics_enquiries.user_id', '=', $user->getId());
        }

        //filter enquiries for logistics users based on lat/long
        if ($user->hasRole(Role::ROLE_LOGISTICS)) {
            if (!$user->getIsGlobal()) {
                $query->where(function($query) {
                    $query->whereNotNull(DB::raw('(SELECT lat FROM logistics_contacts WHERE enquiry_id = logistics_enquiries.id AND type = 0)'));
                    $query->orWhereNotNull(DB::raw('(SELECT lat FROM logistics_contacts WHERE enquiry_id = logistics_enquiries.id AND type = 1)'));
                });

                $query->where(function ($query) use ($user) {
                    $query->inRadius($user->getLat() ?? 0, $user->getLong() ?? 0, self::SEARCH_RADIUS);
                    $query->orWhereRaw('((select q.id from logistics_quotes q where q.enquiry_id=logistics_enquiries.id and q.user_id=? limit 1) is not null)', [$user->getId()]);
                });
            }
        }

        // filter out draft enquiries unless created by current user
        $query->where(function ($query) use ($user) {
            $query->where('logistics_enquiries.status', '=', 0)
                ->orWhere('logistics_enquiries.user_id', '=', $user->getId());
        });

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

    public function store(LogisticsEnquiryDto $enquiryDto): ?LogisticsEnquiry
    {
        $enquiry = LogisticsEnquiry::create([
            'user_id'           => $enquiryDto->getUserId(),
            'project_id'        => $enquiryDto->getProjectId(),
            'works_package_id'  => $enquiryDto->getWorksPackageId(),
            'type'              => $enquiryDto->getType(),
            'status'            => $enquiryDto->getStatus(),
            'scope'             => $enquiryDto->getScope(),
            'vehicle_type'      => $enquiryDto->getVehicleType(),
            'load_details'      => $enquiryDto->getLoadDetails(),
            'comments'          => $enquiryDto->getComments(),
            'notes'             => $enquiryDto->getNotes(),
            'collect_date'      => $enquiryDto->getCollectDate(),
            'delivery_date'     => $enquiryDto->getDeliveryDate(),
            'published_at'      => $enquiryDto->getStatus() === 0 ? Carbon::now() : null,
        ]);

        $collect_contact = LogisticsContact::create([
            'enquiry_id'    => $enquiry->getId(),
            'type'          => 0,
            'postcode'      => $enquiryDto->getCollectPostcode(),
            'city'          => $enquiryDto->getCollectCity(),
            'address1'      => $enquiryDto->getCollectAddress1(),
            'address2'      => $enquiryDto->getCollectAddress2(),
            'contact_name'  => $enquiryDto->getCollectContactName(),
            'contact_phone' => $enquiryDto->getCollectContactPhone(),
        ]);

        $delivery_contact = LogisticsContact::create([
            'enquiry_id'    => $enquiry->getId(),
            'type'          => 1,
            'postcode'      => $enquiryDto->getDeliveryPostcode(),
            'city'          => $enquiryDto->getDeliveryCity(),
            'address1'      => $enquiryDto->getDeliveryAddress1(),
            'address2'      => $enquiryDto->getDeliveryAddress2(),
            'contact_name'  => $enquiryDto->getDeliveryContactName(),
            'contact_phone' => $enquiryDto->getDeliveryContactPhone(),
        ]);

        if ($enquiryDto->getAttachment()) {
            foreach ($enquiryDto->getAttachment() as $attachment) {
                $enquiry->attach($attachment);
            }
        }

        return $enquiry;
    }

    public function update(LogisticsEnquiryDto $enquiryDto, int $id): ?LogisticsEnquiry
    {
        $enquiry = $this->get($id);

        $enquiry->setProjectId($enquiryDto->getProjectId());
        $enquiry->setWorksPackageId($enquiryDto->getWorksPackageId());
        $enquiry->setType($enquiryDto->getType());
        $enquiry->setStatus($enquiryDto->getStatus());
        $enquiry->setScope($enquiryDto->getScope());
        $enquiry->setVehicleType($enquiryDto->getVehicleType());
        $enquiry->setLoadDetails($enquiryDto->getLoadDetails());
        $enquiry->setComments($enquiryDto->getComments());
        $enquiry->setNotes($enquiryDto->getNotes());
        $enquiry->setCollectDate($enquiryDto->getCollectDate());
        $enquiry->setDeliveryDate($enquiryDto->getDeliveryDate());

        if ($enquiry->getPublishedAt() === null && $enquiryDto->getStatus() === 0) {
            $enquiry->setPublishedAt(Carbon::now());
        }

        $enquirySaved = $enquiry->save();

        $collectContact = LogisticsContact::where([
            'enquiry_id' => $id,
            'type'       => 0
        ])->get()->first();

        $collectContact->setPostcode($enquiryDto->getCollectPostcode());
        $collectContact->setCity($enquiryDto->getCollectCity());
        $collectContact->setAddress1($enquiryDto->getCollectAddress1());
        $collectContact->setAddress2($enquiryDto->getCollectAddress2());
        $collectContact->setContactName($enquiryDto->getCollectContactName());
        $collectContact->setContactPhone($enquiryDto->getCollectContactPhone());

        $collectContactSaved = $collectContact->save();

        $deliveryContact = LogisticsContact::where([
            'enquiry_id' => $id,
            'type'       => 1
        ])->get()->first();

        $deliveryContact->setPostcode($enquiryDto->getDeliveryPostcode());
        $deliveryContact->setCity($enquiryDto->getDeliveryCity());
        $deliveryContact->setAddress1($enquiryDto->getDeliveryAddress1());
        $deliveryContact->setAddress2($enquiryDto->getDeliveryAddress2());
        $deliveryContact->setContactName($enquiryDto->getDeliveryContactName());
        $deliveryContact->setContactPhone($enquiryDto->getDeliveryContactPhone());

        $deliveryContactSaved = $deliveryContact->save();

        if ($enquiryDto->getAttachment()) {
            foreach ($enquiryDto->getAttachment() as $attachment) {
                $enquiry->attach($attachment);
            }
        }

        return $enquirySaved ? $enquiry : null;
    }

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

    public function archive(LogisticsEnquiry $enquiry): ?LogisticsEnquiry
    {
        $enquiry->setArchivedAt(new Carbon());
        $enquiry->save();

        return $enquiry;
    }

    public function restore(LogisticsEnquiry $enquiry): ?LogisticsEnquiry
    {
        $enquiry->setArchivedAt(null);
        $enquiry->save();

        return $enquiry;
    }

    public function toggleIgnore(LogisticsEnquiry $enquiry, User $user): ?Ignored
    {
        $item = Ignored::query()->where([
            'ignorable_type' => LogisticsEnquiry::class,
            'ignorable_id' => $enquiry->id,
            'user_id' => $user->id,
        ])->first();

        if ($item) {
            $item->delete();
            return null;
        }

        return Ignored::create([
            'ignorable_type' => LogisticsEnquiry::class,
            'ignorable_id' => $enquiry->id,
            'user_id' => $user->id,
        ]);
    }

    public function getProjectOptions(string $search, User $user, bool $archived): Collection
    {
        $query = DB::table('logistics_enquiries')
            ->select('projects.id', 'projects.name')
            ->join('projects', 'projects.id', '=', 'logistics_enquiries.project_id');

        if (!empty($search)) {
            $query->where('projects.name', 'like', '%' . $search . '%');
        }

        $query->whereNotNull('projects.name');
        $query->where('projects.name', '!=', '');

        if ($archived) {
            $query->whereNotNull('logistics_enquiries.archived_at');
        } else {
            $query->whereNull('logistics_enquiries.archived_at');
        }

        if ($user->hasRole(Role::ROLE_COMPANY_SLUG) || $user->hasRole(Role::ROLE_MANUFACTURER)) {
            $query->where('logistics_enquiries.user_id', '=', $user->getId());
        }

        if ($user->hasRole(Role::ROLE_LOGISTICS)) {
            $query->where(function($query) {
                $query->whereNotNull(DB::raw('(SELECT lat FROM logistics_contacts WHERE enquiry_id = logistics_enquiries.id AND type = 0)'));
                $query->orWhereNotNull(DB::raw('(SELECT lat FROM logistics_contacts WHERE enquiry_id = logistics_enquiries.id AND type = 1)'));
            });

            if (!$user->getIsGlobal()) {
                $query->inRadius($user->getLat() ?? 0, $user->getLong() ?? 0, self::SEARCH_RADIUS);
            }
        }

        $query->groupBy('projects.id', 'projects.name', 'logistics_enquiries.id');
        $query->orderBy('projects.name', 'asc');

        $items = $query->get();

        if ($user->hasRole(Role::ROLE_COMPANY_SLUG)) {
            $itemsPurchaseHire = Answer::query()->select('projects.id', 'projects.name')
                ->join('questions', 'questions.id', '=', 'answers.question_id')
                ->join('projects', 'projects.id', '=', 'questions.project_id')
                ->where('answers.user_id', '=', $user->getId())
                ->whereNotNull('answers.quote_accepted_at')
                ->whereNull('questions.archived_at')
                ->groupBy('projects.id', 'projects.name')
                ->orderBy('projects.name', 'asc')->get();

            $items = $items->merge($itemsPurchaseHire);
        }

        return $items;
    }

    public function getWorksPackageOptions(string $search, User $user, bool $archived, array $projectIds = []): Collection
    {
        $query = DB::table('logistics_enquiries')
            ->select('works_packages.id', 'works_packages.name')
            ->join('works_packages', 'works_packages.id', '=', 'logistics_enquiries.works_package_id');

        if (!empty($search)) {
            $query->where('works_packages.name', 'like', '%' . $search . '%');
        }

        $query->whereNotNull('works_packages.name');
        $query->where('works_packages.name', '!=', '');

        if ($archived) {
            $query->whereNotNull('logistics_enquiries.archived_at');
        } else {
            $query->whereNull('logistics_enquiries.archived_at');
        }

        if (!empty($projectIds)) {
            $query->whereIn('works_packages.project_id', $projectIds);
        }

        if ($user->hasRole(Role::ROLE_COMPANY_SLUG) || $user->hasRole(Role::ROLE_MANUFACTURER)) {
            $query->where('logistics_enquiries.user_id', '=', $user->getId());
        }

        if ($user->hasRole(Role::ROLE_LOGISTICS)) {
            $query->where(function($query) {
                $query->whereNotNull(DB::raw('(SELECT lat FROM logistics_contacts WHERE enquiry_id = logistics_enquiries.id AND type = 0)'));
                $query->orWhereNotNull(DB::raw('(SELECT lat FROM logistics_contacts WHERE enquiry_id = logistics_enquiries.id AND type = 1)'));
            });

            if (!$user->getIsGlobal()) {
                $query->inRadius($user->getLat() ?? 0, $user->getLong() ?? 0, self::SEARCH_RADIUS);
            }
        }

        $query->groupBy('works_packages.id', 'works_packages.name', 'logistics_enquiries.id');
        $query->orderBy('works_packages.name', 'asc');

        $items = $query->get();

        if ($user->hasRole(Role::ROLE_COMPANY_SLUG)) {
            $itemsPurchaseHire = Answer::query()
                ->select(['works_packages.id', 'works_packages.name'])
                ->join('questions', 'questions.id', '=', 'answers.question_id')
                ->join('works_packages', 'works_packages.id', '=', 'questions.works_package_id')
                ->where('answers.user_id', '=', $user->getId())
                ->whereNotNull('answers.quote_accepted_at')
                ->whereNull('questions.archived_at');

            if (!empty($projectIds)) {
                $itemsPurchaseHire->whereIn('works_packages.project_id', $projectIds);
            }

            $itemsPurchaseHire->groupBy('works_packages.id', 'works_packages.name')
                ->orderBy('works_packages.name', 'asc');

            $itemsPurchaseHire = $itemsPurchaseHire->get();

            $items = $items->merge($itemsPurchaseHire);
        }

        return $items;
    }
}
