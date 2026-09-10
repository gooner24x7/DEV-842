<?php

namespace App\Repository;

use App\Dto\QuoteProgress\QuoteProgressDto;
use App\Dto\QuoteProgress\SearchParamsDto;
use App\Models\QuoteProgress;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class QuoteProgressRepository
{
    public const string DEFAULT_ORDER_FIELD_NAME = 'quote_progress.id';
    public const int ITEMS_PER_PAGE = 20;

    private \Redis $redis;
    private UserRepository $userRepository;

    public function __construct(
        \Redis $redis,
        UserRepository $userRepository,
    ) {
        $this->redis = $redis;
        $this->userRepository = $userRepository;
    }

    public function find(SearchParamsDto $searchParamsDto, int $quoteId, int $type): LengthAwarePaginator
    {
        $query = QuoteProgress::query()->where([
            'quote_id'  => $quoteId,
            'type'      => $type
        ]);

        $query = $query->orderBy(
            $searchParamsDto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME,
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        $itemsPerPage = $searchParamsDto->getItemsPerPage() ?? self::ITEMS_PER_PAGE;

        return $query->paginate($itemsPerPage);
    }

    public function store(QuoteProgressDto $dto, User $user): QuoteProgress
    {
        $item = QuoteProgress::create([
            'quote_id'  => $dto->getQuoteId(),
            'user_id'   => $user->getId(),
            'type'      => $dto->getType(),
            'comment'   => $dto->getComment(),
            'date'      => $dto->getDate(),
        ]);

        if ($dto->getUploadedFile()) {
            foreach ($dto->getUploadedFile() as $attachment) {
                $item->attach($attachment);
            }
        }

        return $item;
    }

    public function update(QuoteProgressDto $dto, int $id): QuoteProgress
    {
        $item = $this->getById($id);

        $item->setComment($dto->getComment());
        $item->setDate($dto->getDate());
        $item->save();

        return $item;
    }

    public function complete(QuoteProgress $item): QuoteProgress
    {
        $item->setCompletedAt(Carbon::now());
        $item->save();

        return $item;
    }

    public function uncomplete(QuoteProgress $item): QuoteProgress
    {
        $item->setCompletedAt(null);
        $item->save();

        return $item;
    }

    public function getById(int $id): QuoteProgress
    {
        return QuoteProgress::where(['id' => $id])->first();
    }
}
