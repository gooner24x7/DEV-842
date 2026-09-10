<?php
declare(strict_types=1);

namespace App\Repository;

use App\Dto\Project\ProjectDto;
use App\Dto\Project\SearchParamsDto;
use App\Models\Questionnaire\Project;
use App\Models\Questionnaire\ProjectGroup;
use App\Models\Questionnaire\ProjectUserAccess;
use App\Models\Questionnaire\WorksPackage;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProjectRepository
{
    const int ITEMS_PER_PAGE = 10;
    const string DEFAULT_ORDER_FIELD_NAME = 'id';

    private QuestionnaireTemplateRepository $questionnaireTemplateRepository;
    private PostcodesRepository $postcodesRepository;

    public function __construct(
        QuestionnaireTemplateRepository $questionnaireTemplateRepository,
        PostcodesRepository $postcodesRepository
    ) {
        $this->questionnaireTemplateRepository = $questionnaireTemplateRepository;
        $this->postcodesRepository = $postcodesRepository;
    }

    public function find(SearchParamsDto $searchParamsDto, User $user): Collection
    {
        $archived = $searchParamsDto->getArchived();
        $companyUserIds = $user->getCompanyUsers();

        $query = Project::with('assignedUsers');

        if (!$searchParamsDto->getShowAll()) {
            $query->where(function ($query) use ($user, $companyUserIds) {
                $query->whereIn('projects.user_id', $companyUserIds);

                $query->orWhere([
                    'projects.framework' => $user->getFirstName()
                ]);

                $query->orWhere([
                    'projects.client_name' => $user->getFirstName()
                ]);

                $query->orWhereExists(function ($sub) use ($user) {
                    $sub->select(DB::raw(1))
                        ->from('projects_user_access')
                        ->whereColumn('projects_user_access.project_id', 'projects.id')
                        ->where('projects_user_access.user_id', $user->getId());
                });
            });
        } else {
            if ($user->hasRole(Role::ROLE_CONTRACTOR)) {
                $query->whereNotExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('projects_user_access')
                        ->join('users_roles', 'users_roles.user_id', '=', 'projects_user_access.user_id')
                        ->join('roles', 'roles.id', '=', 'users_roles.role_id')
                        ->whereColumn('projects_user_access.project_id', 'projects.id')
                        ->where('roles.slug', Role::ROLE_CONTRACTOR);
                });
            }

            if ($user->hasRole(Role::ROLE_CONSULTANT)) {
                $query->whereNotExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('projects_user_access')
                        ->join('users_roles', 'users_roles.user_id', '=', 'projects_user_access.user_id')
                        ->join('roles', 'roles.id', '=', 'users_roles.role_id')
                        ->whereColumn('projects_user_access.project_id', 'projects.id')
                        ->where('roles.slug', Role::ROLE_CONSULTANT);
                });
            }

            if ($user->hasRole(Role::ROLE_FRAMEWORK)) {
                $query->whereNull('projects.framework');
            }

            if ($user->hasRole(Role::ROLE_CLIENT)) {
                $query->whereNull('projects.client_name');
            }
        }

        if ($archived) {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereNull('archived_at');
        }

        if ($searchParamsDto->getDateStartFrom()) {
            $query->where('projects.date_start', '>=', $searchParamsDto->getDateStartFrom()->format('Y-m-d'));
        }

        if ($searchParamsDto->getDateStartTo()) {
            $query->where('projects.date_start', '<=', $searchParamsDto->getDateStartTo()->format('Y-m-d'));
        }

        if ($searchParamsDto->getSearch()) {
            $searchTerm = '%' . $searchParamsDto->getSearch() . '%';
            $query->where(function ($query) use ($searchTerm) {
                $query->where('projects.name', 'like', $searchTerm)
                    ->orWhere('projects.postcode', 'like', $searchTerm)
                    ->orWhere('projects.client_name', 'like', $searchTerm)
                    ->orWhere('projects.framework', 'like', $searchTerm);
            });
        }

        if ($searchParamsDto->getRegion()) {
            $query->where(function ($query) use ($searchParamsDto) {
                $query->where('projects.region', '=', $searchParamsDto->getRegion())
                    ->orWhere('projects.contractor_region', '=', $searchParamsDto->getRegion());
            });
        }

        if ($searchParamsDto->getType()) {
            $query->where('projects.type', '=', $searchParamsDto->getType());
        }

        $query->orderBy(
            'projects.' . ($searchParamsDto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME),
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        if ($searchParamsDto->getLimit()){
            $query->limit($searchParamsDto->getLimit());
        }

        return $query->get();
    }

    public function get($id): Project|null
    {
        return Project::where(['id' => $id])->first();
    }

    public function doProjectsBelongToUser(array $projectIds, User $user): bool
    {
        if (Project::whereIn('id', $projectIds)->where('user_id', '=', $user->getId())->first()) {
            return true;
        }

        return false;
    }

    /**
     * @throws \Exception
     */
    public function create(ProjectDto $dto, User $user): ?Project
    {
        $ocid = $dto->getOcid();
        $type = $dto->getType();
        $postcode = $dto->getPostcode();

        if ($ocid && $type === 2) {
            $existingProject = Project::where([
                'ocid' => $ocid,
                'type' => 2
            ])->first();

            if ($existingProject) {
                throw new \Exception('Project with the same OCID already exists');
            }
        }

        $group = ProjectGroup::create([
            'user_id' => $user->getId(),
            'name' => $dto->getName(),
        ]);

        /** @var Project $project */
        $project = Project::create([
            'user_id' => $user->getId(),
            'group_id' => $group->getId(),
            'ocid' => $ocid,
            'stage' => $dto->getStage(),
            'status' => $dto->getStatus(),
            'level' => $dto->getLevel(),
            'type' => $dto->getType(),
            'name' => $dto->getName(),
            'postcode' => $postcode,
            'postcode_districts' => $dto->getPostcodeDistricts(),
            'sector' => $dto->getSector(),
            'region' => $dto->getRegion(),
            'contractor_region' => $dto->getContractorRegion(),
            'client_name' => $dto->getClientName(),
            'framework' => $dto->getFramework(),
            'boq_standard' => $dto->getBoqStandard(),
            'frame_type' => $dto->getFrameType(),
            'procurement_route' => $dto->getProcurementRoute(),
            'project_value' => $dto->getProjectValue(),
            'area_sqm' => $dto->getAreaSqm(),
            'target_miles_client' => $dto->getTargetMilesClient(),
            'target_miles_framework' => $dto->getTargetMilesFramework(),
            'target_hours_ap' => $dto->getTargetHoursAp(),
            'target_hours_se' => $dto->getTargetHoursSe(),
            'budget_se' => $dto->getBudgetSe(),
            'perc_services' => $dto->getPercServices(),
            'perc_prelim' => $dto->getPercPrelim(),
            'perc_labour' => $dto->getPercLabour(),
            'perc_materials' => $dto->getPercMaterials(),
            'date_start' => $dto->getDateStart(),
            'date_end' => $dto->getDateEnd(),
            'date_end_tender' => $dto->getDateEndTender(),
            'description' => $dto->getDescription()
        ]);

        if (!empty($postcode)) {
            $this->updateProjectCoords($project, $postcode);
        }

        if ($user->hasRole(Role::ROLE_CONTRACTOR) || $user->hasRole(Role::ROLE_CONSULTANT)) {
            $access = ProjectUserAccess::create([
                'project_id' => $project->getId(),
                'user_id' => $user->getId(),
            ]);
        }

        return $project;
    }

    /**
     * @throws \Exception
     */
    public function update(ProjectDto $dto, int $id): ?Project
    {
        /** @var Project $project */
        $project = Project::find($id);

        if (!$project) {
            return null;
        }

        $postcode = $dto->getPostcode();
        $ocid = $dto->getOcid();
        $type = $project->getType();

        if ($ocid && $ocid !== $project->getOcid() && $type === 2) {
            $existingProject = Project::where([
                'ocid' => $ocid,
                'type' => 2
            ])->first();

            if ($existingProject) {
                throw new \Exception('Project with the same OCID already exists');
            }
        }

        // update coords if postcode was changed
        if (!empty($postcode) && $postcode !== $project->getPostcode()) {
            $this->updateProjectCoords($project, $postcode);
        }

        $project->stage = $dto->getStage();
        $project->status = $dto->getStatus();
        $project->level = $dto->getLevel();
        $project->ocid = $dto->getOcid();
        $project->name = $dto->getName();
        $project->postcode = $dto->getPostcode();
        $project->postcode_districts = $dto->getPostcodeDistricts();
        $project->sector = $dto->getSector();
        $project->region = $dto->getRegion();
        $project->contractor_region = $dto->getContractorRegion();
        $project->client_name = $dto->getClientName();
        $project->framework = $dto->getFramework();
        $project->boq_standard = $dto->getBoqStandard();
        $project->frame_type = $dto->getFrameType();
        $project->procurement_route = $dto->getProcurementRoute();
        $project->project_value = $dto->getProjectValue();
        $project->area_sqm = $dto->getAreaSqm();
        $project->target_miles_client = $dto->getTargetMilesClient();
        $project->target_miles_framework = $dto->getTargetMilesFramework();
        $project->target_hours_ap = $dto->getTargetHoursAp();
        $project->target_hours_se = $dto->getTargetHoursSe();
        $project->budget_se = $dto->getBudgetSe();
        $project->perc_services = $dto->getPercServices();
        $project->perc_prelim = $dto->getPercPrelim();
        $project->perc_labour = $dto->getPercLabour();
        $project->perc_materials = $dto->getPercMaterials();
        $project->date_start = $dto->getDateStart();
        $project->date_end = $dto->getDateEnd();
        $project->date_end_tender = $dto->getDateEndTender();
        $project->description = $dto->getDescription();

        $project->save();

        return $project;
    }

    public function isWithQuestions(int $projectId, User $user): bool
    {
        $query = Project::where('id', $projectId);
        $query->join('questionnaires', 'questionnaires.project_id', '=', 'projects.id');
        $query->where(['projects.user_id' => $user->getId()]);
        $query->whereNotNull('questionnaires.id');

        return $query->count() > 0;
    }

    public function getProjectOptions(string $search, User $user): Collection
    {
        $companyUsers = $user->getCompanyUsers();
        $userProjectIds = ProjectUserAccess::query()->where('user_id', '=', $user->getId())->pluck('project_id');

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

        $query = Project::query()->select($columns)
            ->leftJoin('questions', 'questions.project_id', '=', 'projects.id')
            ->leftJoin('answers', 'answers.question_id', '=', 'questions.id')
            ->leftJoin('supply_fit_enquiries', 'supply_fit_enquiries.project_id', '=', 'projects.id')
            ->leftJoin('supply_fit_enquiry_quotes', 'supply_fit_enquiry_quotes.enquiry_id', '=', 'supply_fit_enquiries.id')
            ->where(function ($query) use ($companyUsers, $userProjectIds, $user) {
                $query->whereIn('projects.user_id', $companyUsers)
                    ->orWhereIn('projects.id', $userProjectIds)
                    ->orWhere('projects.framework', '=', $user->getFirstName())
                    ->orWhere('projects.client_name', '=', $user->getFirstName());
            })
            ->whereNotNull('projects.name')
            ->where('projects.name', '!=', '')
            ->whereNull('projects.archived_at');

        if (!empty($search)) {
            $query->where('projects.name', 'like', '%' . $search . '%');
        }

        // subcontractors: include any projects that are linked to accepted purchase & hire quotes (questions.user_id = current_user_id)
        // subcontractors: include any projects that are linked to accepted marketplace quotes (supply_fit_enquiry_quotes.user_id = current_user_id)
        if ($user->hasRole(Role::ROLE_USER_SLUG) && !$user->hasRole(Role::ROLE_CONTRACTOR)) {
            $query->where(function($query) use ($user) {
                $query->where('questions.user_id', '=', $user->getId())
                    ->whereNotNull('answers.quote_accepted_at');
            })->orWhere(function($query) use ($user) {
                $query->where('supply_fit_enquiry_quotes.user_id', '=', $user->getId())
                    ->whereNotNull('supply_fit_enquiry_quotes.quote_accepted_at');
            });
        }

        $query->groupBy($columns);
        $query->orderBy('projects.name', 'asc');

        return $query->get();
    }

    public function setCompleted(Project $project): ?Project
    {
        $project->setCompletedAt(Carbon::now());
        $project->save();

        return $project;
    }

    public function setArchived(Project $project): ?Project
    {
        $project->setArchivedAt(Carbon::now());
        $project->save();

        return $project;
    }

    public function setRestored(Project $project): ?Project
    {
        $project->setArchivedAt(null);
        $project->save();

        return $project;
    }

    public function getProjectReport(int $projectId): array
    {
        $sql = "SELECT
            u.id AS user_id,
            u.first_name AS user_name,
            COUNT(DISTINCT q.id) AS total_questions,
            COUNT(DISTINCT a.id) AS total_answers,
            COUNT(DISTINCT wp.id) AS total_works_packages
        FROM users u
        LEFT JOIN users_roles ur ON u.id = ur.user_id
        LEFT JOIN questions q ON q.user_id = u.id
        LEFT JOIN answers a ON a.question_id = q.id
        LEFT JOIN projects p ON p.id = q.project_id
        LEFT JOIN works_packages wp ON wp.id = q.works_package_id
        WHERE p.id = ?
        AND ur.role_id = 2
        GROUP BY u.id, u.first_name";

        $users = DB::select($sql, [$projectId]);

        return $users ?? [];
    }

    public function getProjectUsers(int $projectId): Collection
    {
        return User::query()
            ->select([
                'users.*',
                'projects_user_access.stage'
            ])
            ->join('projects_user_access', 'users.id', '=', 'projects_user_access.user_id')
            ->where('projects_user_access.project_id', $projectId)
            ->get();
    }

    public function getRegions(): array
    {
        $rows = Project::query()
            ->select('region', 'contractor_region')
            ->get();

        return $rows->flatMap(fn($row) => [$row->region, $row->contractor_region])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function updateProjectCoords(Project $project, ?string $postcode): void
    {
        if (empty($postcode)) {
            return;
        }

        $coords = $this->postcodesRepository->getCoords([$postcode]);

        if (!empty($coords)) {
            $project->lat = $coords[0]['latitude'];
            $project->long = $coords[0]['longitude'];
        } else {
            $districtStr = $this->postcodesRepository->preparePostcode($postcode);
            $district = $this->postcodesRepository->getDistrict($districtStr);

            if ($district) {
                $project->lat = $district->Latitude;
                $project->long = $district->Longitude;
            }
        }

        $project->save();
    }

    public function updateStage(Project $project, array $params): ?Project
    {
        $nextStage = $project->getStage() + 1;

        if (!isset(Project::STAGES[$nextStage])) {
            return null;
        }

        $data = [
            'user_id' => $project->getUserId(),
            'group_id' => $project->getGroupId(),
            'ocid' => $project->getOcid(),
            'tender_notice_id' => $project->getTenderNoticeId(),
            'stage' => $nextStage,
            'status' => $project->getStatus(),
            'level' => $project->getLevel(),
            'type' => $project->getType(),
            'name' => $project->getName(),
        ];

        $extraData = [
            'postcode' => $project->getPostcode(),
            'postcode_districts' => $project->getPostcodeDistricts(),
            'sector' => $project->getSector(),
            'region' => $project->getRegion(),
            'contractor_region' => $project->getContractorRegion(),
            'client_name' => $project->getClientName(),
            'framework' => $project->getFramework(),
            'boq_standard' => $project->getBoqStandard(),
            'frame_type' => $project->getFrameType(),
            'procurement_route' => $project->getProcurementRoute(),
            'project_value' => $project->getProjectValue(),
            'area_sqm' => $project->getAreaSqm(),
            'target_miles_client' => $project->getTargetMilesClient(),
            'target_miles_framework' => $project->getTargetMilesFramework(),
            'target_hours_ap' => $project->getTargetHoursAp(),
            'target_hours_se' => $project->getTargetHoursSe(),
            'budget_se' => $project->getBudgetSe(),
            'perc_services' => $project->getPercServices(),
            'perc_prelim' => $project->getPercPrelim(),
            'perc_labour' => $project->getPercLabour(),
            'perc_materials' => $project->getPercMaterials(),
            'date_start' => $project->getDateStart(),
            'date_end' => $project->getDateEnd(),
            'date_end_tender' => $project->getDateEndTender(),
            'description' => $project->getDescription()
        ];

        if ($params['copyProjectInfo']) {
            $data = array_merge($data, $extraData);
        }

        /** @var Project $newProject */
        $newProject = Project::create($data);

        if ($params['copyWorksPackages']) {
            $this->copyWorksPackages($project, $newProject);
        }

        if ($params['copyUsers']) {
            $this->copyUsers($project, $newProject);
        }

        return $newProject;
    }

    /**
     * @throws \Exception
     */
    public function updateType(Project $project, int $type): ?Project
    {
        $ocid = $project->getOcid();

        if ($ocid) {
            $existingProject = Project::where([
                'ocid' => $ocid,
                'type' => 2
            ])->first();

            if ($existingProject && $type === 2) {
                throw new \Exception('Project with the same OCID already exists');
            }
        }

        $project->type = $type;
        $project->save();

        return $project;
    }

    private function copyWorksPackages(Project $project, Project $newProject): void
    {
        // get top level wps
        $wps = WorksPackage::where('project_id', '=', $project->getId())
            ->whereNull('parent_id')->get()->toArray();

        foreach($wps as $wp) {
            $this->copyWorksPackage($wp, $newProject->getId());
        }
    }

    private function copyWorksPackage(array $wp, int $projectId, ?int $parentId = null): void
    {
        $parent = WorksPackage::create([
            'parent_id' => $parentId,
            'project_id' => $projectId,
            'user_id' => $wp['user_id'],
            'name' => $wp['name'],
            'cpv_code' => $wp['cpv_code'],
        ]);

        // copy works_packages_assigned_users
        $assignedUsers = DB::table('works_packages_assigned_users')
            ->where('works_package_id', '=', $wp['id'])
            ->get();

        if ($assignedUsers->isNotEmpty()) {
            $data = $assignedUsers->map(fn($assignedUser) => [
                'works_package_id' => $parent->getId(),
                'user_id' => $assignedUser->user_id,
            ])->toArray();

            DB::table('works_packages_assigned_users')->insert($data);
        }

        if (!empty($wp['children'])) {
            foreach($wp['children'] as $child) {
                $this->copyWorksPackage($child, $projectId, $parent->getId());
            }
        }
    }

    private function copyUsers(Project $project, Project $newProject): void
    {
        $projectUsers = ProjectUserAccess::where('project_id', '=', $project->getId())->get()->toArray();

        foreach($projectUsers as $projectUser) {
            ProjectUserAccess::create([
                'project_id' => $newProject->getId(),
                'user_id' => $projectUser['user_id'],
            ]);
        }
    }
}
