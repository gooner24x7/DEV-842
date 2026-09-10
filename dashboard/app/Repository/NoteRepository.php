<?php

namespace App\Repository;

use App\Dto\Note\NoteDto;
use App\Dto\Note\SearchParamsDto;
use App\Models\Note;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class NoteRepository
{
    public const string DEFAULT_ORDER_FIELD_NAME = 'notes.id';
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

    public function find(SearchParamsDto $searchParamsDto, int $parentId, int $type): LengthAwarePaginator
    {
        $query = Note::query()->select('notes.*', 'users.first_name')
            ->join('users', 'users.id', '=', 'notes.user_id')
            ->where([
                'notes.parent_id'  => $parentId,
                'notes.type'      => $type
            ]);

        $query = $query->orderBy(
            $searchParamsDto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME,
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        $itemsPerPage = $searchParamsDto->getItemsPerPage() ?? self::ITEMS_PER_PAGE;

        return $query->paginate($itemsPerPage);
    }

    public function store(NoteDto $dto, User $user): Note
    {
        $item = Note::create([
            'parent_id'  => $dto->getParentId(),
            'user_id'   => $user->getId(),
            'type'      => $dto->getType(),
            'message'   => $dto->getMessage(),
        ]);

        return $item;
    }

    public function update(int $id, string $message): Note
    {
        $item = $this->getById($id);

        $item->setMessage($message);
        $item->save();

        return $item;
    }

    public function getById(int $id): Note
    {
        return Note::where(['id' => $id])->first();
    }
}
