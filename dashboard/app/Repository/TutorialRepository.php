<?php
declare(strict_types=1);

namespace App\Repository;

use App\Dto\SearchParamsDto;
use App\Dto\Tutorial\TutorialDto;
use App\Exceptions\NotFoundException;
use App\Models\Tutorial;
use Illuminate\Pagination\LengthAwarePaginator;

class TutorialRepository
{
    const int ITEMS_PER_PAGE = 10;
    const string DEFAULT_ORDER_FIELD_NAME = 'id';

    public function find(SearchParamsDto $searchParamsDto): LengthAwarePaginator
    {
        $query = Tutorial::orderBy(
            $searchParamsDto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME,
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        $itemsPerPage = $searchParamsDto->getItemsPerPage() ?? self::ITEMS_PER_PAGE;
        if (!$searchParamsDto->isPaginationEnabled()) {
            $itemsPerPage = PHP_INT_MAX;
        }

        return $query->paginate($itemsPerPage);
    }

    /**
     * @param int $id
     * @return bool
     * @throws NotFoundException
     */
    public function delete(int $id): bool
    {
        $tutorial = $this->get($id);
        if ($tutorial === null) {
            throw new NotFoundException();
        }

        return (bool)$tutorial->delete();
    }

    public function get(int $id): ?Tutorial
    {
        return Tutorial::where(['id' => $id])->first();
    }

    public function create(TutorialDto $dto): ?Tutorial
    {
        if ($tutorial = Tutorial::create([
            'title' => $dto->getTitle(),
            'embedCode' => $dto->getEmbedCode(),
        ])) {
            return $tutorial;
        }

        return null;
    }

    /**
     * @param int $id
     * @param TutorialDto $dto
     * @return Tutorial|null
     * @throws NotFoundException
     */
    public function update(int $id, TutorialDto $dto): ?Tutorial
    {
        $tutorial = $this->get($id);
        if ($tutorial === null) {
            throw new NotFoundException();
        }

        $tutorial->title = $dto->getTitle();
        $tutorial->embedCode = $dto->getEmbedCode();
        $tutorial->save();

        return $tutorial;
    }
}
