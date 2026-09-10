<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataProvider\SupplyFitEnquiryDataProvider;
use App\Dto\Question\SearchParamsDto;
use App\Dto\SupplyFitEnquiry\SupplyFitEnquiryBatchDto;
use App\Dto\SupplyFitEnquiry\SupplyFitEnquiryDto;
use App\Mail\SupplyFitEnquiryDuplicated;
use App\Models\SupplyFitEnquiryQuote;
use App\Repository\ProjectRepository;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use RedisException;
use Symfony\Component\HttpFoundation\Response;

class SupplyFitEnquiriesController extends Controller
{
    private UserService $userService;
    private SupplyFitEnquiryDataProvider $supplyFitEnquiryDataProvider;
    private ProjectRepository $projectRepository;

    public function __construct(
        UserService $userService,
        SupplyFitEnquiryDataProvider $supplyFitEnquiryDataProvider,
        ProjectRepository $projectRepository
    ) {
        $this->userService = $userService;
        $this->supplyFitEnquiryDataProvider = $supplyFitEnquiryDataProvider;
        $this->projectRepository = $projectRepository;
    }

    /**
     * @throws RedisException
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse($this->supplyFitEnquiryDataProvider->find(
            SearchParamsDto::createFromRequest($request),
            $user,
            false
        ));
    }

    /**
     * @throws RedisException
     */
    public function archive(int $id): JsonResponse
    {
        $enquiry = $this->supplyFitEnquiryDataProvider->get($id);
        if (!$enquiry) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(
            $this->supplyFitEnquiryDataProvider->setArchived($enquiry)
        );
    }

    /**
     * @throws RedisException
     */
    public function get(int $id): JsonResponse
    {
        $enquiry = $this->supplyFitEnquiryDataProvider->get($id);
        if (!$enquiry) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($enquiry);
    }

    /**
     * @throws RedisException
     */
    public function restore(int $id): JsonResponse
    {
        $enquiry = $this->supplyFitEnquiryDataProvider->get($id);
        if (!$enquiry) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(
            $this->supplyFitEnquiryDataProvider->restore($enquiry)
        );
    }

    /**
     * @throws RedisException
     */
    public function duplicate(int $id, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $enquiry = $this->supplyFitEnquiryDataProvider->get($id);
        if (!$enquiry) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        if ($enquiry->user_id !== $user->getId() && !$user->isAdmin()) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $dto = SupplyFitEnquiryDto::createFromRequest($request, $user->getId());

        $duplicated = $this->supplyFitEnquiryDataProvider->duplicate($id, $dto);

        /** @var SupplyFitEnquiryQuote $quote */
        foreach ($enquiry->quotes as $quote) {
            $user = $this->userService->getById($quote->user_id);
            if ($user) {
                Mail::to($user->getEmail())->queue(new SupplyFitEnquiryDuplicated($enquiry, $duplicated, $user));
            }
        }

        return new JsonResponse(
            $duplicated
        );
    }

    /**
     * @throws RedisException
     */
    public function delete(int $id): JsonResponse
    {
        $enquiry = $this->supplyFitEnquiryDataProvider->get($id);
        if (!$enquiry) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(
            $this->supplyFitEnquiryDataProvider->delete($id)
        );
    }

    /**
     * @throws RedisException
     * @throws \Exception
     */
    public function store(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if ($user->supplyFitEnquiries()->get()->isEmpty()) {
            $user->onboardingChecklist()->update(['first_enquiry' => true]);
        }

        $dto = SupplyFitEnquiryDto::createFromRequest($request, $user->getId());
        $enquiries = $this->supplyFitEnquiryDataProvider->storeBatch($dto);

        if (empty($enquiries)) {
            return new JsonResponse('failed to create the enquiry', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($enquiries);
    }

    /**
     * @throws RedisException
     */
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        if (empty($id)) {
            return new JsonResponse('Bad request', Response::HTTP_BAD_REQUEST);
        }

        $dto = SupplyFitEnquiryDto::createFromRequest($request, $user->getId());
        $enquiry = $this->supplyFitEnquiryDataProvider->update($dto, $id);

        if (!$enquiry) {
            return new JsonResponse('failed to update the enquiry', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($enquiry);
    }

    /**
     * @throws RedisException
     */
    public function projectOptionsArchived(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $options = $this->supplyFitEnquiryDataProvider->getProjectOptions(
            $request->get('search') ?? '',
            $user,
            true
        );

        return new JsonResponse($options);
    }

    /**
     * @throws RedisException
     */
    public function projectOptions(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $projects = $this->supplyFitEnquiryDataProvider->getProjectOptions(
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

    /**
     * @throws RedisException
     */
    public function worksPackageOptions(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $collection = $this->supplyFitEnquiryDataProvider->getWorksPackageOptions(
            $request->get('search') ?? '',
            $user,
            $request->get('projectIds') ?? [],
        );

        return new JsonResponse($collection);
    }
}
