<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Dto\User\SearchParamsDto;
use App\Repository\CertificateRepository;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CertificatesController extends Controller
{
    private CertificateRepository $certificateRepository;
    private UserService $userService;

    public function __construct(CertificateRepository $certificateRepository, UserService $userService) {
        $this->certificateRepository = $certificateRepository;
        $this->userService = $userService;
    }

    public function get(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $userIds = $request->get('user_ids', []);

        if (empty($userIds)) {
            return new JsonResponse('Bad request', Response::HTTP_BAD_REQUEST);
        }

        $certificates = $this->certificateRepository->get($userIds);

        return new JsonResponse($certificates);
    }

    public function getTypes(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $types = $this->certificateRepository->getTypes();

        return new JsonResponse($types);
    }

    public function getStats(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $stats = $this->certificateRepository->getStats($user);

        return new JsonResponse($stats);
    }

    public function getDashboardStats(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $stats = $this->certificateRepository->getDashboardStats($user);

        return new JsonResponse($stats);
    }
}
