<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\Answer\SearchParamsDto;
use App\Dto\SupplyFitEnquiryQuote\SupplyFitEnquiryQuoteDto;
use App\Models\Role;
use App\Models\SupplyFitEnquiry;
use App\Models\SupplyFitEnquiryQuote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SupplyFitEnquiryQuoteRepository
{
    public const string DEFAULT_ORDER_FIELD_NAME = 'supply_fit_enquiry_quotes.id';
    public const int ITEMS_PER_PAGE = 20;

    private \Redis $redis;
    private UserRepository $userRepository;

    public function __construct(
        \Redis $redis,
        UserRepository $userRepository
    ) {
        $this->redis = $redis;
        $this->userRepository = $userRepository;
    }

    public function find(SearchParamsDto $searchParamsDto, User $user, int $enquiryId): LengthAwarePaginator
    {
        $companyUserIds = $user->getCompanyUsers();

        $query = SupplyFitEnquiryQuote::query()
            ->select([
                'supply_fit_enquiry_quotes.id',
                'supply_fit_enquiry_quotes.enquiry_id',
                'supply_fit_enquiry_quotes.price',
                'supply_fit_enquiry_quotes.local_material_spend',
                'supply_fit_enquiry_quotes.comment',
                'supply_fit_enquiry_quotes.offers',
                'supply_fit_enquiry_quotes.quote_accepted_at',
                'supply_fit_enquiry_quotes.supplier_invoice_no',
                'supply_fit_enquiry_quotes.user_id',
                'supply_fit_enquiry_quotes.type',
                'supply_fit_enquiry_quotes.resources_available',
                'supply_fit_enquiry_quotes.is_competent',
                'users.first_name',
                'users.last_name',
                'users.is_sme',
                'supply_fit_enquiries.project_id',
                'supply_fit_enquiries.works_package_id',
                's.id as questionnaire_session_id',
                's.is_answered as questionnaire_is_answered',
            ])
            ->selectRaw('(select (count(*)>0) from users_preferred_subcontractors where subcontractor_id = supply_fit_enquiry_quotes.user_id and (user_id = supply_fit_enquiries.user_id or user_id = (select billing_user_id from users where id = supply_fit_enquiries.user_id))) is_preferred_subcontractor')
            ->selectRaw("(select (count(*)>0) from users_preferred_subcontractors where subcontractor_id = supply_fit_enquiry_quotes.user_id and (user_id = (select id FROM users WHERE first_name = projects.client_name) OR user_id = (select billing_user_id FROM users WHERE first_name = projects.client_name))) is_client_preferred_subcontractor")
            ->leftJoin('questionnaire_sessions as s', function ($q) {
                $q->on('s.id', '=',
                    DB::raw('(select max(a.id) from questionnaire_sessions a where a.inquiry_id=supply_fit_enquiry_quotes.enquiry_id and
                        a.user_id=supply_fit_enquiry_quotes.user_id)'));
            })
            ->selectRaw('DATE_FORMAT(supply_fit_enquiry_quotes.`created_at`, "%Y-%m-%dT%H:%i:%S.000000Z") created_at')
            ->selectRaw('(select coalesce(sum(cr.score), 0) from questionnaire_replies cr left join
            questionnaire_sessions cs on cr.session_id=cs.id
            where cs.inquiry_id=supply_fit_enquiry_quotes.enquiry_id and user_id=supply_fit_enquiry_quotes.user_id)
            as total_score')
            ->where([
                'supply_fit_enquiry_quotes.enquiry_id' => $enquiryId,
            ]);

        $query->join('supply_fit_enquiries', 'supply_fit_enquiries.id', '=', 'supply_fit_enquiry_quotes.enquiry_id');
        $query->join('users', 'users.id', '=', 'supply_fit_enquiry_quotes.user_id');
        $query->leftJoin('projects', 'projects.id', '=', 'supply_fit_enquiries.project_id');

        $enquiry = SupplyFitEnquiry::where(['id' => $enquiryId])->first();
        $query->selectRadius($enquiry);

        if (!empty($searchParamsDto->getRadius())) {
            $query->where(static function ($query) use ($searchParamsDto, $enquiry) {
                $query->inRadius($enquiry->getLat() ?? 0, $enquiry->getLong() ?? 0, $searchParamsDto->getRadius());
            });
        }

        if (!$user->hasRole(Role::ROLE_ADMIN_SLUG)) {
            $query->where(function ($query) use ($user, $companyUserIds) {
                $query->whereIn('supply_fit_enquiry_quotes.user_id', $companyUserIds)
                    ->orWhereIn('supply_fit_enquiries.user_id', $companyUserIds);
            });
        }

        if ($searchParamsDto->getQuoteId()) {
            $query->where('supply_fit_enquiry_quotes.id', '=', $searchParamsDto->getQuoteId());
        }

        $query = $query->orderBy(
            $searchParamsDto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME,
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        $itemsPerPage = $searchParamsDto->getItemsPerPage() ?? self::ITEMS_PER_PAGE;
        if (!$searchParamsDto->isPaginationEnabled()) {
            $itemsPerPage = PHP_INT_MAX;
        }

        $newAnswerIndicatorKey = sprintf(SupplyFitEnquiryQuote::NEW_QUOTE_TO_ENQUIRY_INDICATOR_CACHE, $enquiryId, $user->getId());
        $this->redis->set($newAnswerIndicatorKey, Carbon::now()->format('Y-m-d H:i:s'));

        return $this->populateWithIndicator($query->paginate($itemsPerPage), $user);
    }

    private function populateWithIndicator(LengthAwarePaginator $response, User $user): LengthAwarePaginator
    {
        $userRepository = $this->userRepository;
        $response->getCollection()->transform(function (object $quote) use ($user, $userRepository) {
            $user = $userRepository->getUserById($quote->getUserId());
            if ($user && $user->getBillingUserId()) {
                $billingUser = $userRepository->getUserById($user->getBillingUserId());

                if ($billingUser->credit_application_form_url) {
                    $quote->setCreditApplicationFormUrl($billingUser->credit_application_form_url);
                }
            }

            $quote->sessionId = $quote->questionnaire_session_id;

            return $quote;
        });

        return $response;
    }

    public function store(SupplyFitEnquiryQuoteDto $dto, SupplyFitEnquiry $enquiry, User $user): ?SupplyFitEnquiryQuote
    {
        $quote = SupplyFitEnquiryQuote::create([
            'price' => $dto->getPrice(),
            'comment' => $dto->getComment(),
            'offers' => $dto->getOffers(),
            'user_id' => $user->getId(),
            'enquiry_id' => $enquiry->getId(),
            'type' => $dto->getType(),
            'resources_available' => $dto->getResourcesAvailable(),
            'is_competent' => $dto->getIsCompetent()
        ]);

        if ($dto->getUploadedFile()) {
            foreach ($dto->getUploadedFile() as $attachment) {
                $quote->attach($attachment);
            }
        }

        return $quote;
    }

    public function update(SupplyFitEnquiryQuoteDto $dto, int $id): ?SupplyFitEnquiryQuote
    {
        $quote = $this->getById($id);
        if (!$quote) {
            return null;
        }

        $quote->setPrice($dto->getPrice());
        $quote->setComment($dto->getComment());
        $quote->setOffers($dto->getOffers());
        $quote->setType($dto->getType());
        $quote->setResourcesAvailable($dto->getResourcesAvailable());
        $quote->setIsCompetent($dto->getIsCompetent());

        if ($quote->save()) {
            return $quote;
        }

        return null;
    }

    public function getById(int $id): ?SupplyFitEnquiryQuote
    {
        return SupplyFitEnquiryQuote::where(['id' => $id])->first();
    }

    public function accept(int $id): bool
    {
        $quote = $this->getById($id);
        if (!$quote) {
            return false;
        }
        $quote->quote_accepted_at = new Carbon();

        return $quote->save();
    }

    public function unaccept(int $id): bool
    {
        $quote = $this->getById($id);
        if (!$quote) {
            return false;
        }
        $quote->quote_accepted_at = null;

        return $quote->save();
    }

    public function delete(int $id): bool
    {
        $quote = $this->getById($id);
        if (!$quote) {
            return false;
        }

        return $quote->delete();
    }

    public function storeSupplierInvoiceNo(string $supplierInvoiceNo, int $quoteId): ?SupplyFitEnquiryQuote
    {
        $quote = $this->getById($quoteId);
        if (!$quote) {
            return null;
        }
        $quote->setSupplierInvoiceNo($supplierInvoiceNo);

        if ($quote->save()) {
            return $quote;
        }

        return null;
    }

    public function storeLocalMaterialSpend(?float $localMaterialSpend, int $quoteId): ?SupplyFitEnquiryQuote
    {
        $quote = $this->getById($quoteId);
        if (!$quote) {
            return null;
        }

        $quote->setLocalMaterialSpend($localMaterialSpend);

        if ($quote->save()) {
            return $quote;
        }

        return null;
    }

    public function isLastAnswerOlderThanADay(int $enquiryId, int $excludeId): bool
    {
        $lastQuote = SupplyFitEnquiryQuote::where(['enquiry_id' => $enquiryId])->where('id', '<>', $excludeId)->orderBy('id', 'desc')->first();
        if ($lastQuote && $lastQuote->created_at->diffInSeconds(Carbon::now()) < 3600 * 24) {
            return false;
        }

        return true;
    }
}
