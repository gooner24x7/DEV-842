<?php
declare(strict_types=1);

namespace App\Repository;

use App\Dto\Questionnaire\SessionDto;
use App\Dto\SearchParamsDto;
use App\Models\Questionnaire\Reply;
use App\Models\Questionnaire\Session;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class QuestionnaireSessionRepository
{
    const string DEFAULT_ORDER_FIELD_NAME = 'id';
    const int ITEMS_PER_PAGE = 20;

    public function find(SearchParamsDto $searchParamsDto, string $worksPackageId, User $user): LengthAwarePaginator
    {

        $query = Session::select(
            'questionnaire_sessions.id',
            'questionnaire_sessions.user_id',
            'questionnaire_sessions.created_at',
            'questionnaire_sessions.hash',
            'questionnaire_sessions.is_answered',
            'questionnaire_sessions.is_accepted',
            'questionnaire_sessions.is_declined',
            'questionnaire_sessions.inquiry_id',
            DB::raw('users.first_name as company_name'),
            DB::raw('SUM(questionnaire_replies.score) as totalScore')
        )
            ->leftJoin('questionnaire_replies', 'questionnaire_replies.session_id', '=', 'questionnaire_sessions.id')
            ->leftJoin('users', 'users.id', '=', 'questionnaire_sessions.user_id')
            ->whereNull('questionnaire_sessions.deleted_at')
            ->where(['questionnaire_sessions.works_package_id' => $worksPackageId])
            ->groupBy([
                'questionnaire_sessions.id',
                'questionnaire_sessions.user_id',
                'questionnaire_sessions.created_at',
                'questionnaire_sessions.hash',
                'questionnaire_sessions.is_answered',
                'questionnaire_sessions.is_accepted',
                'questionnaire_sessions.is_declined',
                'questionnaire_sessions.inquiry_id',
                'users.first_name',
            ])
            ->distinct();

        $orderBy = $searchParamsDto->getOrderBy() ?? self::DEFAULT_ORDER_FIELD_NAME;
        $query = $query->orderBy(
            $orderBy,
            $searchParamsDto->getOrderSortDesc() ? 'desc' : 'asc'
        );

        if ($user->hasRole(Role::ROLE_CONTRACTOR)) {
            $query->where('questionnaire_sessions.is_answered', '=', true);
        }

        $itemsPerPage = $searchParamsDto->getItemsPerPage() ?? self::ITEMS_PER_PAGE;
        if (!$searchParamsDto->isPaginationEnabled()) {
            $itemsPerPage = PHP_INT_MAX;
        }

        return $query->paginate($itemsPerPage);
    }

    public function getHash(int $userId, int $inquiryId, int $projectId, int $worksPackageId): string|null
    {
        $record = $this->getSession($userId, $inquiryId, $projectId, $worksPackageId);
        return $record->hash;
    }

    public function getSession(int $userId, int $inquiryId, int $projectId, int $worksPackageId): Session
    {
        $session = Session::where(['user_id' => $userId])
            ->where([
                'inquiry_id' => $inquiryId,
                'works_package_id' => $worksPackageId,
                'project_id' => $projectId
            ])->first();
        if ($session) {
            return $session;
        }

        return $this->store(new SessionDto(
            $userId,
            $inquiryId,
            $projectId,
            $worksPackageId,
        ));
    }

    public function store(SessionDto $companyQuestionnaireDto): ?Session
    {
        return Session::create([
            'hash' => Hash::make(sprintf("%s_%s_%s", $companyQuestionnaireDto->getUserId(), $companyQuestionnaireDto->getInquiryId(), time())),
            'user_id' => $companyQuestionnaireDto->getUserId(),
            'inquiry_id' => $companyQuestionnaireDto->getInquiryId(),
            'project_id' => $companyQuestionnaireDto->getProjectId(),
            'works_package_id' => $companyQuestionnaireDto->getWorksPackageId(),
        ]);
    }

    public function deleteForInquiryUser(int $enquiryId, int $userId): void
    {
        Session::where(['inquiry_id' => $enquiryId, 'user_id' => $userId])->delete();
    }

    public function delete(int $id): bool
    {
        $item = $this->getById($id);

        $item->deleted_at = new Carbon();

        return $item->update();
    }

    public function getById(int $id): ?Session
    {
        return Session::where(['id' => $id])->first();
    }

    public function update(int $id, SessionDto $companyQuestionnaireDto): ?Session
    {
        $item = $this->getById($id);
        if (!$item) {
            return null;
        }

        $item->inquiry_id = $companyQuestionnaireDto->getInquiryId();
        $item->save();

        return $item;
    }

    public function getByHash(string $hash): ?Session
    {
        return Session::where(['hash' => $hash])->where(['is_answered' => 0])->first();
    }

    public function getTotalScoreForUserEnquiry(int $userId, int $inquiryId): int
    {
        $session = Session::where([
            'user_id' => $userId,
            'inquiry_id' => $inquiryId
        ])->first();
        if (!$session) {
            return 0;
        }

        $total = 0;
        $replies = Reply::where(['session_id' => $session->id])->get();
        foreach ($replies as $reply) {
            $total += $reply->score;
        }

        return $total;
    }

    public function getScores(array $ids)
    {
        return Session::select([
            'questionnaire_sessions.id',
            'questionnaire_sessions.user_id',
            DB::raw('SUM(questionnaire_replies.score) as totalScore')
        ])
            ->leftJoin('questionnaire_replies', 'questionnaire_replies.session_id', '=', 'questionnaire_sessions.id')
            ->whereIn('questionnaire_sessions.user_id', $ids)
            ->groupBy('questionnaire_sessions.id', 'questionnaire_sessions.user_id')
            ->orderBy('totalScore', 'desc')
            ->get();
    }

    public function getReplyWithScore(array $ids): array
    {
        $response = Reply::select([
            'questionnaire_replies.id',
            'questionnaire_sessions.user_id',
            'questionnaires.id as question_id',
            'questionnaires.text as question_text',
            'questionnaires.type as question_type',
            'questionnaire_replies.id as answer_id',
            'questionnaire_replies.is_yes',
            'questionnaire_replies.score',
            'questionnaire_replies.text as answer_text',
            'questionnaire_sessions.id as sessionId',
            'q.quote_accepted_at',
            'q.price',
            'q.id as quote_id',
        ])
            ->join('questionnaire_sessions', 'questionnaire_replies.session_id', '=', 'questionnaire_sessions.id')
            ->join('questionnaires', 'questionnaires.id', '=', 'questionnaire_replies.item_id')
            ->leftJoin('supply_fit_enquiry_quotes as q', function ($q) {
                $q->on('q.id', '=',
                    DB::raw('(select max(a.id) from supply_fit_enquiry_quotes a where a.enquiry_id=q.enquiry_id and
                        a.user_id=q.user_id)'))
                    ->where('q.user_id', '=', 'questionnaire_sessions.user_id');
            })
            ->whereIn('questionnaire_sessions.id', $ids)
            ->get();

        $items = [];
        foreach ($response as $item) {
            $items[$item->sessionId][] = $item;
        }

        return $items;
    }
}
