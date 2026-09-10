<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Dto\Questionnaire\SessionDto;
use App\Mail\ResendQuestionnaire;
use App\Models\Role;
use App\Models\SupplyFitEnquiry;
use App\Models\User;
use App\Repository\QuestionnaireItemRepository;
use App\Repository\QuestionnaireSessionRepository;
use App\Repository\SupplyFitEnquiryRepository;
use App\Repository\WorksPackagesRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class QuestionnaireResend extends Command
{
    protected $signature = 'questionnaire:resend {works-package-id}';
    protected $description = 'Resends questionnaire to merchants';

    private QuestionnaireSessionRepository $questionnaireSessionRepository;
    private QuestionnaireItemRepository $questionnaireItemRepository;
    private WorksPackagesRepository $worksPackagesRepository;
    private int $worksPackageId;

    public function __construct(
        QuestionnaireSessionRepository $questionnaireSessionRepository,
        QuestionnaireItemRepository    $questionnaireItemRepository,
        WorksPackagesRepository        $worksPackagesRepository,
    )
    {
        parent::__construct();

        $this->questionnaireSessionRepository = $questionnaireSessionRepository;
        $this->questionnaireItemRepository = $questionnaireItemRepository;
        $this->worksPackagesRepository = $worksPackagesRepository;
    }

    public function handle(): void
    {
        $this->worksPackageId = (int)$this->argument('works-package-id');
        $worksPackage = $this->worksPackagesRepository->getById($this->worksPackageId);

        $questions = SupplyFitEnquiry::whereNotNull('lat')->where([
            'project_id' => $worksPackage->getProjectId(),
            'works_package_id' => $worksPackage->getId(),
        ])->whereNull('archived_at')->get();

        foreach ($questions as $question) {
            $this->sendNotification($question);
        }
    }

    private function sendNotification(SupplyFitEnquiry $enquiry): void
    {
        $users = User::whereNotNull('lat')->where(function ($query) use ($enquiry) {
            if ($enquiry->lat === null || $enquiry->long === null) {
                $query->where('is_global', '=', true);
                return;
            }

            $query->inRadius($enquiry->lat, $enquiry->long, SupplyFitEnquiryRepository::SEARCH_RADIUS)
                ->orWhere('is_global', '=', true);
        })->select('users.*')->get();

        $sentToUsers = [];
        foreach ($users as $user) {
            if (!$user->hasRole(Role::ROLE_USER_SLUG)) {
                continue;
            }

            if (!in_array($user->id, $sentToUsers)) {
                if (!($enquiry->getWorksPackageId() && $this->questionnaireItemRepository->hasByWorksPackageId($this->worksPackageId))) {
                    continue;
                }

                $questionnaire = $this->questionnaireSessionRepository->store(new SessionDto(
                    $user->getId(),
                    $enquiry->getId(),
                    $enquiry->getProjectId(),
                    $this->worksPackageId
                ));

                Mail::to($user->email)->send(new ResendQuestionnaire($enquiry, $user, $questionnaire));
                $sentToUsers[] = $user->id;
            }
        }
    }
}
