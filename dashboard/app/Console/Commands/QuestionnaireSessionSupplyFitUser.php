<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Dto\Questionnaire\SessionDto;
use App\Models\SupplyFitEnquiry;
use App\Models\User;
use App\Repository\QuestionnaireItemRepository;
use App\Repository\QuestionnaireSessionRepository;
use App\Repository\SupplyFitEnquiryRepository;
use Illuminate\Console\Command;

class QuestionnaireSessionSupplyFitUser extends Command
{
    protected $signature = 'questionnaire:update-session-supply-fit-user {id}';
    protected $description = 'Update questionnaire session for supply fit enquiries and given user id';

    private QuestionnaireSessionRepository $questionnaireSessionRepository;
    private QuestionnaireItemRepository $questionnaireItemRepository;

    public function __construct(
        QuestionnaireSessionRepository $questionnaireSessionRepository,
        QuestionnaireItemRepository    $questionnaireItemRepository,
    ) {
        parent::__construct();

        $this->questionnaireSessionRepository = $questionnaireSessionRepository;
        $this->questionnaireItemRepository = $questionnaireItemRepository;
    }

    public function handle(): void
    {
        $userId = (int) $this->argument('id');

        if (empty($userId)) {
            return;
        }

        $sfEnquiries = SupplyFitEnquiry::query()
            ->whereNotNull('lat')
            ->whereNotNull('long')
            ->get();

        foreach($sfEnquiries as $enquiry) {
            $this->updateQuestionnaireSession($enquiry, $userId);
        }
    }

    private function updateQuestionnaireSession(SupplyFitEnquiry $enquiry, int $userId): void
    {
        $users = User::query()->select('users.*')
            ->join('users_roles', 'users.id', '=', 'users_roles.user_id')
            ->where('users.id', '=', $userId)
            ->where('users_roles.role_id', '=', 2)
            ->whereNotNull('lat')
            ->where(function ($query) use ($enquiry) {
                if ($enquiry->lat === null || $enquiry->long === null) {
                    $query->where('is_global', '=', true);
                    return;
                }

                $query->inRadius($enquiry->lat, $enquiry->long, SupplyFitEnquiryRepository::SEARCH_RADIUS)
                    ->orWhere('is_global', '=', true);
            })->get();

        $projectId = $enquiry->getProjectId();
        $worksPackageId = $enquiry->getWorksPackageId();
        $user = $users->first();

        if (empty($user)) {
            return;
        }

        if (!in_array($enquiry->product_id, $user->product_ids->toArray())) {
            return;
        }

        $this->questionnaireSessionRepository->deleteForInquiryUser($enquiry->id, $user->getId());

        if ($worksPackageId && $this->questionnaireItemRepository->hasByWorksPackageId($worksPackageId)) {
            $this->questionnaireSessionRepository->store(new SessionDto(
                $user->getId(),
                $enquiry->getId(),
                $projectId,
                $worksPackageId,
            ));
        }
    }
}
