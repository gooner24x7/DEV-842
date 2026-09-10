<?php
declare(strict_types=1);

namespace App\Repository;

use App\Dto\SearchParamsDto;
use App\Exceptions\NotFoundException;
use App\Models\Questionnaire\ProjectUserAccess;
use App\Models\Questionnaire\Questionnaire;
use App\Models\Questionnaire\WorksPackage;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorksPackagesRepository
{
    const int ITEMS_PER_PAGE = 10;
    const string DEFAULT_ORDER_FIELD_NAME = 'id';

    private QuestionnaireTemplateRepository $questionnaireTemplateRepository;

    public function __construct(QuestionnaireTemplateRepository $questionnaireTemplateRepository)
    {
        $this->questionnaireTemplateRepository = $questionnaireTemplateRepository;
    }

    public function find(int $projectId, SearchParamsDto $searchParamsDto): Collection
    {
        $query = WorksPackage::query()->where('project_id', $projectId);

        $query->orderBy(
            $searchParamsDto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME,
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        return $query->get();
    }

    /**
     * @throws NotFoundException
     */
    public function create(array $data): ?WorksPackage
    {
        /** @var WorksPackage $wp */
        $wp = WorksPackage::create([
            'name' => $data['name'],
            'user_id' => $data['user_id'],
            'project_id' => $data['project_id'],
            'parent_id' => $data['parent_id'],
            'high_risk' => $data['high_risk'] ?? 0,
        ]);

        if (!$wp) {
            return null;
        }

        if ($data['template_id']) {
            $this->questionnaireTemplateRepository->storeInWorksPackage($data['template_id'], $data['project_id'], $data['user_id'], $wp->getId());
        }

        return $wp;
    }

    /**
     * @throws NotFoundException
     */
    public function update(int $id, array $data): ?WorksPackage
    {
        /** @var WorksPackage $wp */
        $wp = WorksPackage::find($id);

        if (!$wp) {
            return null;
        }

        // if we are editing a top level item (wp->getParentId() = null)
        // AND the selected parents parent_id is NOT NULL
        // THEN set the selected parents parent_id to NULL (parent->setParentId() = null)
        if (is_null($wp->getParentId())) {
            $parent = WorksPackage::find($data['parent_id']);

            if (!is_null($parent?->getParentId())) {
                $parent->setParentId(null);
                $parent->save();
            }
        }

        $wp->setName($data['name']);
        $wp->setHighRisk($data['high_risk']);
        $wp->setTemplateId($data['template_id']);
        $wp->setParentId($data['parent_id']);
        $wp->save();

        if ($data['template_id']) {
            Questionnaire::query()->where('works_package_id', $wp->getId())->delete();

            $this->questionnaireTemplateRepository->storeInWorksPackage($data['template_id'], $wp->getProjectId(), $wp->getUserId(), $wp->getId());
        }

        return $wp;
    }

    public function doWorksPackageBelongToUser(array $names, User $user)
    {
        if (WorksPackage::whereIn('name', $names)->where('user_id', '<>', $user->getId())->first()) {
            return false;
        }

        return true;
    }

    public function isWithQuestions(string $name, User $user): bool
    {
        $query = WorksPackage::where('name', $name);
        $query->join('questionnaires', 'questionnaires.works_package_id', '=', 'works_packages.id');
        $query->where(['works_packages.user_id' => $user->getId()]);
        $query->whereNotNull('questionnaires.id');

        return $query->count() > 0;
    }

    public function getById(int $id)
    {
        return WorksPackage::where(['id' => $id])->first();
    }

    public function getWorksPackageOptions(string $search, User $user, array $projectIds): Collection
    {
        $companyUsers = $user->getCompanyUsers();
        $userProjectIds = ProjectUserAccess::query()->where('user_id', '=', $user->getId())->pluck('project_id');

        $query = WorksPackage::query()->select('works_packages.id', 'works_packages.name')
            ->leftJoin('projects', 'projects.id', '=', 'works_packages.project_id')
            ->leftJoin('questions', 'questions.project_id', '=', 'projects.id')
            ->leftJoin('answers', 'answers.question_id', '=', 'questions.id')
            ->leftJoin('supply_fit_enquiries', 'supply_fit_enquiries.project_id', '=', 'projects.id')
            ->leftJoin('supply_fit_enquiry_quotes', 'supply_fit_enquiry_quotes.enquiry_id', '=', 'supply_fit_enquiries.id')
            ->where(function ($query) use ($companyUsers, $userProjectIds, $user) {
                $query->whereIn('works_packages.user_id', $companyUsers)
                    ->orWhereIn('works_packages.project_id', $userProjectIds)
                    ->orWhere('projects.framework', '=', $user->getFirstName())
                    ->orWhere('projects.client_name', '=', $user->getFirstName());
            })
            ->whereNotNull('works_packages.name')
            ->where('works_packages.name', '!=', '')
            ->whereNull('projects.archived_at');

        if (!empty($search)) {
            $query->where('works_packages.name', 'like', '%' . $search . '%');
        }

        if (!empty($projectIds)) {
            $query->whereIn('works_packages.project_id', $projectIds);
        }

        // subcontractors: include any works packages that are linked to accepted purchase & hire quotes (questions.user_id = current_user_id)
        // subcontractors: include any works packages that are linked to accepted marketplace quotes (supply_fit_enquiry_quotes.user_id = current_user_id)
        if ($user->hasRole(Role::ROLE_USER_SLUG) && !$user->hasRole(Role::ROLE_CONTRACTOR)) {
            $query->where(function($query) use ($user) {
                $query->where('questions.user_id', '=', $user->getId())
                    ->whereNotNull('answers.quote_accepted_at');
            })->orWhere(function($query) use ($user) {
                $query->where('supply_fit_enquiry_quotes.user_id', '=', $user->getId())
                    ->whereNotNull('supply_fit_enquiry_quotes.quote_accepted_at');
            });
        }

        $query->groupBy('works_packages.id', 'works_packages.name');
        $query->orderBy('works_packages.name', 'asc');

        return $query->get();
    }

    public function getTree(int $projectId): array
    {
        $query = WorksPackage::query()
            ->where('project_id', $projectId)
            ->whereNull('parent_id');

        $items = $query->get()->toArray();

        return $items;
    }

    public function storeAssignedUsers(WorksPackage $worksPackage, array $userIds): bool
    {

        DB::table('works_packages_assigned_users')->where('works_package_id', '=', $worksPackage->getId())->delete();

        $data = [];
        $userIds = array_unique($userIds);

        foreach ($userIds as $userId) {
            $data[] = [
                'works_package_id' => $worksPackage->getId(),
                'user_id' => $userId,
            ];
        }

        $result = DB::table('works_packages_assigned_users')->insert($data);

        return (bool) $result;
    }

    public function getAssignedUsers(int $worksPackageId): array
    {
        return DB::table('users')->select('users.id', 'users.first_name', 'users.last_name')
            ->join('works_packages_assigned_users', 'users.id', '=', 'works_packages_assigned_users.user_id')
            ->where('works_packages_assigned_users.works_package_id', '=', $worksPackageId)
            ->get()
            ->toArray();
    }

    public function delete(WorksPackage $worksPackage): void
    {
        DB::transaction(function () use ($worksPackage) {
            $this->deleteWorksPackageTree($worksPackage);
        });
    }

    private function deleteWorksPackageTree(WorksPackage $worksPackage): void
    {
        foreach ($worksPackage->children as $child) {
            $childWorksPackage = WorksPackage::find($child['id'] ?? null);

            if ($childWorksPackage) {
                $this->deleteWorksPackageTree($childWorksPackage);
            }
        }

        DB::table('works_packages_assigned_users')
            ->where('works_package_id', '=', $worksPackage->getId())
            ->delete();

        $worksPackage->questions()->delete();
        $worksPackage->questionnaires()->delete();

        $worksPackage->delete();
    }
}
