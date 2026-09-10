<?php


namespace App\Http\Controllers;

use App\Dto\Questionnaire\ReplyDto;
use App\Dto\Questionnaire\SessionDto;
use App\Repository\QuestionnaireItemRepository;
use App\Repository\QuestionnaireReplyRepository;
use App\Repository\QuestionnaireSessionRepository;
use App\Repository\SupplyFitEnquiryRepository;
use App\Repository\WorksPackagesRepository;
use App\Service\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class QuestionnaireSessionsController
{
    private QuestionnaireItemRepository $questionnaireItemRepository;
    private QuestionnaireReplyRepository $questionnaireReplyRepository;
    private QuestionnaireSessionRepository $questionnaireSessionRepository;
    private UserService $userService;
    private SupplyFitEnquiryRepository $supplyFitEnquiryRepository;

    public function __construct(
        UserService                    $userService,
        QuestionnaireItemRepository    $questionnaireItemRepository,
        QuestionnaireReplyRepository   $questionnaireReplyRepository,
        QuestionnaireSessionRepository $questionnaireSessionRepository,
        SupplyFitEnquiryRepository     $supplyFitEnquiryRepository,
    ) {
        $this->userService = $userService;
        $this->questionnaireItemRepository = $questionnaireItemRepository;
        $this->questionnaireReplyRepository = $questionnaireReplyRepository;
        $this->questionnaireSessionRepository = $questionnaireSessionRepository;
        $this->supplyFitEnquiryRepository = $supplyFitEnquiryRepository;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $worksPackageId = $request->query->get('worksPackageId');

        return new JsonResponse($this->questionnaireSessionRepository->find(
            \App\Dto\SearchParamsDto::createFromRequest($request),
            $worksPackageId,
            $user,
        ));
    }

    public function get(int $id): JsonResponse
    {
        $session = $this->questionnaireSessionRepository->getById($id);
        if (!$session) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($session);
    }

    public function delete(int $id): JsonResponse
    {
        $session = $this->questionnaireSessionRepository->getById($id);
        if (!$session) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(
            $this->questionnaireSessionRepository->delete($id)
        );
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $dto = SessionDto::createFromRequest($request, $user->getId());
        $item = $this->questionnaireSessionRepository->getById($id);
        if (!$item) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        if ($this->questionnaireSessionRepository->update($id, $dto)) {
            return new JsonResponse($item);
        }

        return new JsonResponse('failed to update', Response::HTTP_BAD_GATEWAY);
    }

    public function getScores(Request $request): JsonResponse
    {
        return new JsonResponse($this->questionnaireSessionRepository->getScores($request->post('ids')));
    }

    public function getAnswersWithScores(Request $request): JsonResponse
    {
        return new JsonResponse(
            $this->questionnaireSessionRepository->getReplyWithScore($request->post('ids'))
        );
    }

    public function accept(int $id): JsonResponse
    {
        $item = $this->questionnaireSessionRepository->getById($id);
        if (!$item) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $item->is_accepted = true;

        return new JsonResponse($item->save());
    }

    public function decline(int $id): JsonResponse
    {
        $item = $this->questionnaireSessionRepository->getById($id);
        if (!$item) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $item->is_declined = true;

        return new JsonResponse($item->save());
    }

    public function setScore(Request $request, int $id): JsonResponse
    {
        $reply = $this->questionnaireReplyRepository->get($id);
        if (!$reply) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $reply->score = $request->post('score');
        $reply->save();

        return new JsonResponse($reply);
    }

    public function byHash(string $hash): JsonResponse
    {
        $hash = urldecode($hash);
        $item = $this->questionnaireSessionRepository->getByHash($hash);
        if (!$item) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($item);
    }

    public function byHashSubmit(string $hash, Request $request): JsonResponse
    {
        $session = $this->questionnaireSessionRepository->getByHash($hash);
        if (!$session) {
            return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
        }

        $replies = $request->post('replies');

        foreach ($replies as $reply) {
            $item = $this->questionnaireItemRepository->get($reply['item_id']);
            if (!$item) {
                return new JsonResponse('Not found', Response::HTTP_NOT_FOUND);
            }
            $reply['user_id'] = $session->user_id;
            if ($reply['type'] === 'yes_no') {
                $reply['score'] = $reply['is_yes'] == 'Y' ? $item->score_yes : $item->score_no;
            }

            $dto = ReplyDto::createFromArray($reply);

            $this->questionnaireReplyRepository->store($dto);
        }

        $session->is_answered = 1;
        $session->save();

        $enquiry = $this->supplyFitEnquiryRepository->get($session->inquiry_id);
        $companyUser = $this->userService->getById($enquiry->user_id);

        return new JsonResponse([
            'session' => $session,
            'companyName' => $companyUser->first_name,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $dto = SessionDto::createFromRequest($request, $user->getId());
        $company = $this->questionnaireSessionRepository->store($dto);

        if ($company) {
            //send email?
            return new JsonResponse($company);
        }

        return new JsonResponse('failed to create', Response::HTTP_BAD_GATEWAY);
    }

    public function getHash(Request $request): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return new JsonResponse('Not authorized', Response::HTTP_FORBIDDEN);
        }

        $inquiryId = $request->get('inquiry_id');

        $inquiry = $this->supplyFitEnquiryRepository->get($inquiryId);
        $projectId = $inquiry->getProjectId();
        $worksPackageId = $inquiry->getWorksPackageId();

        if (!$inquiryId || !$projectId || !$worksPackageId) {
            return new JsonResponse('Bad request', Response::HTTP_BAD_REQUEST);
        }

        if (!$user->isSubContractor()) {
            return new JsonResponse([]);
        }

        $session = $this->questionnaireSessionRepository->getSession($user->id, $inquiryId, $projectId, $worksPackageId);

        return new JsonResponse($session);
    }
}
