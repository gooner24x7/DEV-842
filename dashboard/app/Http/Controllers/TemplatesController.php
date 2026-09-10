<?php

namespace App\Http\Controllers;

use App\Dto\Questionnaire\TemplateDto;
use App\Dto\SearchParamsDto;
use App\Exceptions\NotFoundException;
use App\Repository\QuestionnaireItemRepository;
use App\Repository\QuestionnaireTemplateRepository;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Response;

class TemplatesController
{
    private UserService $userService;
    private QuestionnaireItemRepository $questionnaireItemRepository;
    private QuestionnaireTemplateRepository $questionnaireTemplateRepository;

    public function __construct(
        UserService                     $userService,
        QuestionnaireItemRepository     $questionnaireItemRepository,
        QuestionnaireTemplateRepository $questionnaireTemplateRepository
    ) {
        $this->userService = $userService;
        $this->questionnaireItemRepository = $questionnaireItemRepository;
        $this->questionnaireTemplateRepository = $questionnaireTemplateRepository;
    }

    public function create(int $projectId, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $name = $request->get('name');
        if (!$name) {
            throw new BadRequestException('Name is required');
        }

        $questionnaireItems = $this->questionnaireItemRepository->getByProjectId($projectId);

        $template = $this->questionnaireTemplateRepository->create($name, $questionnaireItems, $user->getId());

        return new JsonResponse($template);
    }

    public function newTemplate(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $name = $request->get('name');
        if (!$name) {
            return new JsonResponse('Bad request', Response::HTTP_BAD_REQUEST);
        }

        $templateDto = TemplateDto::createFromRequest($request);

        $this->questionnaireTemplateRepository->newTemplate($name, $templateDto, $user->getId());

        return new JsonResponse([]);
    }

    /**
     * @throws NotFoundException
     */
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $templateDto = TemplateDto::createFromRequest($request);

        $this->questionnaireTemplateRepository->update($id, $templateDto, $user->getId());

        return new JsonResponse([]);
    }

    public function options(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $collection = $this->questionnaireTemplateRepository->getOptions(
            $request->get('search') ?? '',
            $user
        );

        return new JsonResponse($collection);
    }

    /**
     * @throws NotFoundException
     */
    public function applyTemplate(int $projectId, int $worksPackageId, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();

        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $templateId = $request->get('id');

        if (!$templateId) {
            throw new BadRequestException('Template ID is required');
        }

        // delete existing questions?
        DB::table('questionnaires')
            ->join('works_packages', 'works_packages.id', '=', 'questionnaires.works_package_id')
            ->where('works_packages.id', '=', $worksPackageId)->delete();

        $this->questionnaireTemplateRepository->storeInWorksPackage($templateId, $projectId, $user->getId(), $worksPackageId);

        $questionnaireItems = $this->questionnaireItemRepository->getByWorksPackage($worksPackageId);

        return new JsonResponse($questionnaireItems);
    }

    /**
     * @throws NotFoundException
     */
    public function delete(int $id, Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $this->questionnaireTemplateRepository->delete($id);

        return new JsonResponse([]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $searchParams = SearchParamsDto::createFromRequest($request);
        $result = $this->questionnaireTemplateRepository->find($searchParams, $user->getId());

        return new JsonResponse($result);
    }
}
