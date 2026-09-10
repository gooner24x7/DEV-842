<?php

namespace App\Http\Controllers;

use App\Dto\TenderNotices\SearchParamsDto;
use App\Service\ContractsFinderService;
use App\Service\TenderNoticeService;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenderNoticesController
{
    private UserService $userService;
    private ContractsFinderService $contractsFinderService;
    private TenderNoticeService $tenderNoticeService;

    public function __construct(
        UserService $userService,
        ContractsFinderService $contractsFinderService,
        TenderNoticeService $tenderNoticeService
    ) {
        $this->userService = $userService;
        $this->contractsFinderService = $contractsFinderService;
        $this->tenderNoticeService = $tenderNoticeService;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $searchParams = SearchParamsDto::createFromRequest($request);
        $searchParamsArray = $searchParams->toArray();
        $searchParamsArray['cpvCodes'] = [44000000, 45000000, 71000000];

        $size = $request->get('size') ?? 10;

        try {
            $notices = $this->contractsFinderService->searchNotices2($searchParamsArray, $size);
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($notices);
    }

    public function get(string $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        try {
            $notice = $this->contractsFinderService->getNotice($id);
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($notice);
    }

    public function convert(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $data = $request->all();
        $name = $data['tender']['title'] ?? null;
        $classification = $data['tender']['classification'] ?? null;
        $additionalClassifications = $data['tender']['additionalClassifications'] ?? null;

        if (empty($name)) {
            return new JsonResponse('Bad request', Response::HTTP_BAD_REQUEST);
        }

        try {
            $project = $this->tenderNoticeService->createProject($data, $user);
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (!$project) {
            return new JsonResponse('Failed to create project', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $worksPackages = [];

        if (!empty($classification) && $classification['scheme'] === 'CPV') {
            $worksPackages[] = $classification;

            if (!empty($additionalClassifications)) {
                $worksPackages = array_merge($worksPackages, $additionalClassifications);
            }
        }

        $worksPackages = $this->tenderNoticeService->buildWorksPackagesTree($worksPackages);

        $this->tenderNoticeService->storeWorksPackages($worksPackages, $project->getId(), $user->getId());

        return new JsonResponse($project);
    }
}
