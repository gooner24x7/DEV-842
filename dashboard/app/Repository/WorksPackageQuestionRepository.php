<?php

namespace App\Repository;

use App\Dto\SearchParamsDto;
use App\Models\Questionnaire\WorksPackageQuestion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class WorksPackageQuestionRepository
{
    public const string DEFAULT_ORDER_FIELD_NAME = 'works_package_questions.id';
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

    public function find(SearchParamsDto $searchParamsDto, array $worksPackageIds): LengthAwarePaginator
    {
        $query = WorksPackageQuestion::query()
            ->select([
                'works_package_questions.*',
                'users.first_name as user_name',
                'works_packages.name as works_package_name',
                'projects.name as project_name'
            ])
            ->addSelect(DB::raw('(SELECT first_name FROM users WHERE id = works_package_questions.answer_user_id) as answer_user_name'))
            ->join('users', 'users.id', '=', 'works_package_questions.user_id')
            ->join('works_packages', 'works_packages.id', '=', 'works_package_questions.works_package_id')
            ->join('projects', 'projects.id', '=', 'works_packages.project_id')
            ->whereIn('works_package_questions.works_package_id', $worksPackageIds);

        $query = $query->orderBy(
            $searchParamsDto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME,
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        $itemsPerPage = $searchParamsDto->getItemsPerPage() ?? self::ITEMS_PER_PAGE;

        return $query->paginate($itemsPerPage);
    }

    public function store(int $worksPackageId, User $user, string $question): WorksPackageQuestion
    {
        $item = WorksPackageQuestion::create([
            'works_package_id'  => $worksPackageId,
            'user_id'           => $user->getId(),
            'question'          => $question
        ]);

        return $item;
    }

    public function update(int $id, User $user, ?string $answer): WorksPackageQuestion
    {
        $item = $this->getById($id);

        if ($answer !== null) {
            $item->setAnswer($answer);
            $item->setAnsweredAt(Carbon::now());
            $item->setAnswerUserId($user->getId());
        }

        $item->save();

        return $item;
    }

    public function getById(int $id): WorksPackageQuestion
    {
        return WorksPackageQuestion::where(['id' => $id])->first();
    }
}
