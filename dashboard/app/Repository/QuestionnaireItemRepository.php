<?php
declare(strict_types=1);

namespace App\Repository;

use App\Dto\Question\SearchParamsDto;
use App\Dto\Questionnaire\ItemDto;
use App\Models\Questionnaire\Questionnaire;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class QuestionnaireItemRepository
{
    const string DEFAULT_ORDER_FIELD_NAME = 'questionnaires.id';
    const int ITEMS_PER_PAGE = 20;
    const int SEARCH_RADIUS = 40;

    public function __construct()
    {
    }

    public function hasByWorksPackageId(int $worksPackageId): bool
    {
        return !$this->getByWorksPackageId($worksPackageId)->isEmpty();
    }

    public function getByWorksPackageId(int $worksPackageId): Collection
    {
        return Questionnaire::where(['works_package_id' => $worksPackageId])->get();
    }

    public function get(int $id): ?Questionnaire
    {
        $item = Questionnaire::where(['id' => $id])->first();
        if ($item) {
            return $item;
        }

        return null;
    }

    public function hasByProjectId(int $projectId): bool
    {
        return !$this->getByProjectId($projectId)->isEmpty();
    }

    public function getByProjectId(int $projectId): Collection
    {
        return Questionnaire::query()
            ->join('works_packages', 'works_packages.id', '=', 'questionnaires.works_package_id')
            ->where(['works_packages.project_id' => $projectId])->get();
    }

    public function getByWorksPackage(int $worksPackageId): Collection
    {
        return Questionnaire::where(['works_package_id' => $worksPackageId])->get();
    }

    public function find(SearchParamsDto $searchParamsDto, ?int $worksPackageId = null): LengthAwarePaginator
    {
        $query = Questionnaire::query()
            ->select([
                'questionnaires.id',
                'questionnaires.user_id',
                'questionnaires.text',
                'questionnaires.type',
                'questionnaires.score_yes',
                'questionnaires.score_no',
                'questionnaires.deleted_at'
            ])->whereNull('deleted_at');

        if ($worksPackageId) {
            $query->where(['questionnaires.works_package_id' => $worksPackageId]);
        }

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

    public function delete(int $itemId): bool
    {
        $item = $this->get($itemId);
        if (!$item) {
            return false;
        }

        $item->deleted_at = new Carbon();

        return $item->update();
    }

    public function update(ItemDto $questionDto, int $id): ?Questionnaire
    {
        $questionnaireItem = $this->get($id);

        $questionnaireItem->setText($questionDto->getText());
        $questionnaireItem->setType($questionDto->getType());
        $questionnaireItem->setScoreYes($questionDto->getScoreYes());
        $questionnaireItem->setScoreNo($questionDto->getScoreNo());
        $questionnaireItem->setWorksPackageId($questionDto->getWorksPackageId());

        if ($questionnaireItem->save()) {
            return $questionnaireItem;
        }

        return null;
    }

    public function store(ItemDto $itemDto): ?Questionnaire
    {
        /** @var Questionnaire $item */
        return Questionnaire::create([
            'text' => $itemDto->getText(),
            'type' => $itemDto->getType(),
            'score_yes' => $itemDto->getScoreYes(),
            'score_no' => $itemDto->getScoreNo(),
            'user_id' => $itemDto->getUserId(),
            'works_package_id' => $itemDto->getWorksPackageId(),
        ]);
    }
}
