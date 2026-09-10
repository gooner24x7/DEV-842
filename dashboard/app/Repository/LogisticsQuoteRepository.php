<?php
declare(strict_types=1);

namespace App\Repository;

use App\Dto\Logistics\LogisticsQuoteDto;
use App\Dto\Logistics\SearchParamsDto;
use App\Models\LogisticsEnquiry;
use App\Models\LogisticsQuote;
use App\Models\Role;
use App\Models\User;
use App\Service\UserService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class LogisticsQuoteRepository
{
    public const string DEFAULT_ORDER_FIELD_NAME = 'id';
    public const int ITEMS_PER_PAGE = 20;

    private \Redis $redis;
    private UserRepository $userRepository;
    private UserService $userService;

    public function __construct(
        \Redis $redis,
        UserRepository $userRepository,
        UserService $userService
    ) {
        $this->redis = $redis;
        $this->userRepository = $userRepository;
        $this->userService = $userService;
    }

    public function getById(int $id): ?LogisticsQuote
    {
        return LogisticsQuote::where(['id' => $id])->first();
    }

    public function getQuotesByIds(array $ids)
    {
        return LogisticsQuote::whereIn('id', $ids)->get();
    }

    public function find(SearchParamsDto $searchParamsDto, User $user, int $enquiryId): LengthAwarePaginator
    {
        $enquiry = LogisticsEnquiry::where(['id' => $enquiryId])->first();

        $query = LogisticsQuote::query()
            ->select(['logistics_quotes.*', 'users.first_name'])
            ->join('logistics_enquiries', 'logistics_enquiries.id', '=', 'logistics_quotes.enquiry_id')
            ->join('users', 'users.id', '=', 'logistics_quotes.user_id')
            ->where('logistics_quotes.enquiry_id', '=', $enquiryId);

        //$query->selectRadius($enquiry);

        if ($user->hasRole(Role::ROLE_COMPANY_SLUG)) {
            $query->where('logistics_enquiries.user_id', '=', $user->getId());
        } else {
            $query->where('logistics_quotes.user_id', '=', $user->getId());
        }

        $orderBy = (!$searchParamsDto->getOrderBy() || $searchParamsDto->getOrderBy() === 'id') ? self::DEFAULT_ORDER_FIELD_NAME : $searchParamsDto->getOrderBy();
        $sortDir = $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc';

        $query = $query->orderBy($orderBy, $sortDir);

        $itemsPerPage = $searchParamsDto->getItemsPerPage() ?? self::ITEMS_PER_PAGE;

        if (!$searchParamsDto->isPaginationEnabled()) {
            $itemsPerPage = PHP_INT_MAX;
        }

        return $query->paginate($itemsPerPage);
    }

    public function store(LogisticsQuoteDto $quoteDto, LogisticsEnquiry $enquiry, User $user): ?LogisticsQuote
    {
        $quote = LogisticsQuote::create([
            'user_id' => $user->getId(),
            'enquiry_id' => $enquiry->getId(),
            'type' => $quoteDto->getType(),
            'price' => $quoteDto->getPrice(),
            'comments' => $quoteDto->getComments(),
            'offers' => $quoteDto->getOffers(),
        ]);

        if ($quoteDto->getUploadedFile()) {
            foreach ($quoteDto->getUploadedFile() as $attachment) {
                $quote->attach($attachment);
            }
        }

        return $quote;
    }

    public function update(LogisticsQuoteDto $quoteDto, int $id): ?LogisticsQuote
    {
        $quote = $this->getById($id);

        if (!$quote) {
            return null;
        }

        $quote->setType($quoteDto->getType());
        $quote->setPrice($quoteDto->getPrice());
        $quote->setComments($quoteDto->getComments());
        $quote->setOffers($quoteDto->getOffers());

        $quote->save();

        if ($quoteDto->getUploadedFile()) {
            foreach ($quoteDto->getUploadedFile() as $attachment) {
                $quote->attach($attachment);
            }
        }

        return $quote;
    }

    public function accept(int $id): bool
    {
        $quote = $this->getById($id);

        if (!$quote) {
            return false;
        }

        $quote->accepted_at = new Carbon();

        return $quote->save();
    }

    public function unaccept(int $id): bool
    {
        $quote = $this->getById($id);

        if (!$quote) {
            return false;
        }

        $quote->accepted_at = null;

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


}
