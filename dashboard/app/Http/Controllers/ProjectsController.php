<?php

namespace App\Http\Controllers;

use App\Dto\Project\ProjectDto;
use App\Dto\Project\SearchParamsDto;
use App\Exceptions\NotFoundException;
use App\Models\Questionnaire\Project;
use App\Models\Questionnaire\ProjectUserAccess;
use App\Models\Role;
use App\Models\SupplyFitEnquiryQuote;
use App\Models\User;
use App\Repository\ProjectRepository;
use App\Service\UserService;
use App\Service\ProjectService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ProjectsController
{
    private UserService $userService;
    private ProjectRepository $projectRepository;
    private ProjectService $projectService;

    public function __construct(
        UserService $userService,
        ProjectRepository $projectRepository,
        ProjectService $projectService
    ) {
        $this->userService = $userService;
        $this->projectRepository = $projectRepository;
        $this->projectService = $projectService;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $projects = $this->projectRepository->find(SearchParamsDto::createFromRequest($request), $user);

        $grouped = (bool) $request->get('grouped');

        if ($grouped && $projects->count() > 0) {
            $projects = $projects
                ->groupBy('group_id')
                ->map(function ($groupProjects) {
                    $sortedProjects = $groupProjects
                        ->sortByDesc(function ($project) {
                            return sprintf('%s-%010d', $project->created_at, $project->id);
                        })
                        ->values();

                    $latestProject = clone $sortedProjects->first();
                    $latestProject->setAttribute(
                        'lifecycle_projects',
                        $sortedProjects->slice(1)->values()->map(function ($project) {
                            return Arr::only($project->toArray(), [
                                'id',
                                'user_id',
                                'group_id',
                                'name',
                                'postcode',
                                'created_at',
                                'updated_at',
                                'archived_at',
                                'stage',
                                'status',
                                'level',
                                'stage_str',
                            ]);
                        })
                    );

                    return $latestProject;
                })
                ->sortByDesc(function ($project) {
                    return sprintf('%s-%010d', $project->created_at, $project->id);
                })
                ->values();
        }

        return new JsonResponse($projects);
    }

    public function get(int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $project = $this->projectRepository->get($id);

        return new JsonResponse($project);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $dto = ProjectDto::createFromRequest($request);
        $projectId = $request->get('id');
        $ocid = $dto->getOcid();
        $type = $dto->getType();

        if (!$ocid && $type === 2) {
            return new JsonResponse('OCID is required', Response::HTTP_BAD_REQUEST);
        }

        try {
            if (!empty($projectId)) {
                $project = $this->projectRepository->update($dto, $projectId);
            } else {
                $project = $this->projectRepository->create($dto, $user);
            }
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($project);
    }

    public function options(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $projects = $this->projectRepository->getProjectOptions(
            $request->get('search') ?? '',
            $user
        );

        $grouped = (bool) $request->get('grouped');

        if ($grouped && $projects->count() > 0) {
            $projects = $projects
                ->groupBy('group_id')
                ->map(function ($groupProjects) {
                    $sortedProjects = $groupProjects
                        ->sortByDesc(function ($project) {
                            return sprintf('%s-%010d', $project->created_at, $project->id);
                        })
                        ->values();

                    $latestProject = clone $sortedProjects->first();
                    $latestProject->setAttribute('lifecycle_projects', $sortedProjects->values());

                    return $latestProject;
                })
                ->sortByDesc(function ($project) {
                    return sprintf('%s-%010d', $project->created_at, $project->id);
                })
                ->values();
        }

        return new JsonResponse($projects);
    }

    public function restore(int $id, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $project = $this->projectRepository->get($id);

        if (!$project) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        if (!$user->isAdmin() && $user->id !== $project->user_id) {
            return new JsonResponse('Forbidden', Response::HTTP_FORBIDDEN);
        }

        $groupId = $project->getGroupId();
        $groupProjects = Project::where('group_id', $groupId)->get();

        foreach($groupProjects as $gp) {
            $this->projectRepository->setRestored($gp);
        }

        return new JsonResponse(true);
    }

    public function archive(int $id, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $project = $this->projectRepository->get($id);

        if (!$project) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        if (!$user->isAdmin() && $user->id !== $project->user_id) {
            return new JsonResponse('Forbidden', Response::HTTP_FORBIDDEN);
        }

        $groupId = $project->getGroupId();
        $groupProjects = Project::where('group_id', $groupId)->get();

        foreach($groupProjects as $gp) {
            $this->projectRepository->setArchived($gp);
        }

        return new JsonResponse(true);
    }

    public function getReport(int $id, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $project = $this->projectRepository->get($id);
        if (!$project) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->projectRepository->getProjectReport($id));
    }

    public function getProjectUsers(int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $users = $this->projectRepository->getProjectUsers($id);

        return new JsonResponse($users);
    }

    /**
     * @throws NotFoundException
     */
    public function getUsers(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $role = $request->get('role');

        if (empty($role)) {
            return new JsonResponse('Bad request', Response::HTTP_BAD_REQUEST);
        }

        $users = $this->userService->searchUsersByRole($role);

        return new JsonResponse($users);
    }

    public function createUserAccess(int $id, Request $request): JsonResponse
    {
        $currentUser = $this->userService->getCurrentUser();
        if (!$currentUser) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $userId = $request->get('user_id');
        $stage = $request->get('stage');
        $role = $request->get('role');

        if (empty($userId)) {
            return new JsonResponse('Bad request', Response::HTTP_BAD_REQUEST);
        }

        if (!empty($role)) {
            $users = $this->projectRepository->getProjectUsers($id);

            foreach($users as $user) {
                $rolesArr = $user->roles->pluck('slug')->toArray();

                if (in_array($role, $rolesArr)) {
                    return new JsonResponse("User already assigned with role: $role", Response::HTTP_BAD_REQUEST);
                }
            }
        }

        $access = ProjectUserAccess::create([
            'project_id' => $id,
            'user_id' => $userId,
            'stage' => $stage
        ]);

        if (!empty($access) && !empty($role) && ($role === Role::ROLE_FRAMEWORK)) {
            $project = $this->projectRepository->get($id);
            $user = $this->userService->getById($userId);

            if ($project && $user) {
                $project->framework = $user->getFirstName();
                $project->save();
            }
        }

        return new JsonResponse($access);
    }

    public function deleteUserAccess(int $id, int $userId): JsonResponse
    {
        $currentUser = $this->userService->getCurrentUser();
        if (!$currentUser) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (empty($userId)) {
            return new JsonResponse('Bad request', Response::HTTP_BAD_REQUEST);
        }

        $deleted = ProjectUserAccess::where([
            'project_id' => $id,
            'user_id' => $userId
        ])->delete();

        return new JsonResponse($deleted);
    }

    public function getSupplyFitQuotes(int $id, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $quotes = SupplyFitEnquiryQuote::query()
            ->select('supply_fit_enquiry_quotes.*', 'users.first_name as contractor_name', 'works_packages.name as works_package_name')
            ->join('supply_fit_enquiries', 'supply_fit_enquiries.id', '=', 'supply_fit_enquiry_quotes.enquiry_id')
            ->join('users', 'users.id', '=', 'supply_fit_enquiry_quotes.user_id')
            ->join('works_packages', 'works_packages.id', '=', 'supply_fit_enquiries.works_package_id')
            ->where('supply_fit_enquiries.project_id', '=', $id)
            ->whereNotNull('supply_fit_enquiry_quotes.quote_accepted_at')
            ->get();

        return new JsonResponse($quotes);
    }

    public function getRegions() {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $regions = $this->projectRepository->getRegions();

        return new JsonResponse($regions);
    }

    public function updateStage(int $id, Request $request) {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $project = $this->projectRepository->get($id);
        if (!$project) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $params = [
            'copyWorksPackages' => $request->get('copyWorksPackages'),
            'copyProjectInfo' => $request->get('copyProjectInfo'),
            'copyUsers' => $request->get('copyUsers'),
            'copyDocuments' => $request->get('copyDocuments'),
        ];

        if ($project->getStage() === 3) {
            $project = $this->projectRepository->setCompleted($project);
        } else {
            $project = $this->projectRepository->updateStage($project, $params);
            if (!$project) {
                return new JsonResponse('Failed to update project stage', Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        return new JsonResponse($project);
    }

    public function updateType(Request $request, $id)
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $project = $this->projectRepository->get($id);
        if (!$project) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $type = $request->get('type') ? (int) $request->get('type') : null;
        $ocid = $project->getOcid();

        if (!$ocid) {
            return new JsonResponse('OCID is required', Response::HTTP_BAD_REQUEST);
        }

        if (!$type) {
            return new JsonResponse('Bad request', Response::HTTP_BAD_REQUEST);
        }

        try {
            $project = $this->projectRepository->updateType($project, $type);
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($project);
    }

    public function uploadBoqFile(Request $request)
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'project_id' => ['required', 'integer'],
            'boq_file' => ['required', 'file', 'mimes:xlsx', 'max:51200'],
            'template' => ['required', Rule::in(array_keys(ProjectService::getBoqTemplates()))],
        ]);

        $projectId = (int) $validated['project_id'];
        $file = $request->file('boq_file');
        $template = (string) $validated['template'];

        $project = Project::find($projectId);
        if (!$project) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        if (!$this->projectRepository->canUserAccessProject($projectId, $user)) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        try {
            $preview = $this->projectService->createWorksPackagesPreview(
                $file,
                $template,
                $projectId,
                $user->getId()
            );
        } catch (\InvalidArgumentException $exception) {
            return new JsonResponse($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $exception) {
            Log::error('BOQ allocation failed', [
                'project_id' => $projectId,
                'user_id' => $user->getId(),
                'error' => $exception->getMessage(),
            ]);

            return new JsonResponse(
                'The BOQ could not be allocated. Please check the file format and try again.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return new JsonResponse($preview);
    }

    public function createWorksPackages(Request $request)
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'project_id' => ['required', 'integer'],
            'preview_id' => ['required', 'uuid'],
            'selected_keys' => ['required', 'array', 'min:1', 'max:5000'],
            'selected_keys.*' => ['required', 'string', 'max:32', 'distinct'],
        ]);

        $projectId = (int) $validated['project_id'];

        $project = Project::find($projectId);
        if (!$project) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        if (!$this->projectRepository->canUserAccessProject($projectId, $user)) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        try {
            $worksPackages = $this->projectService->storeWorksPackagesFromPreview(
                (string) $validated['preview_id'],
                $validated['selected_keys'],
                $projectId,
                $user
            );
        } catch (AuthorizationException $exception) {
            return new JsonResponse($exception->getMessage(), Response::HTTP_FORBIDDEN);
        } catch (\InvalidArgumentException $exception) {
            return new JsonResponse($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $exception) {
            Log::error('Failed to store BOQ works packages', [
                'project_id' => $projectId,
                'user_id' => $user->getId(),
                'error' => $exception->getMessage(),
            ]);

            return new JsonResponse(
                'The selected BOQ hierarchy could not be saved.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $count = count($worksPackages);

        return new JsonResponse($count . ' works packages created');
    }
}
