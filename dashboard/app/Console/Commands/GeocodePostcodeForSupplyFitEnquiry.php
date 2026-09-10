<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Dto\Questionnaire\SessionDto;
use App\Mail\SupplyFitEnquiryCreated;
use App\Models\Role;
use App\Models\SupplyFitEnquiry;
use App\Models\User;
use App\Repository\QuestionnaireItemRepository;
use App\Repository\QuestionnaireSessionRepository;
use App\Repository\SupplyFitEnquiryRepository;
use App\Repository\WorksPackagesRepository;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class GeocodePostcodeForSupplyFitEnquiry extends Command
{
    protected $signature = 'geocode:postcode-supply-fit {id}';
    protected $description = 'Decode postcodes into coordinates';

    private QuestionnaireSessionRepository $questionnaireSessionRepository;
    private QuestionnaireItemRepository $questionnaireItemRepository;
    private SupplyFitEnquiryRepository $supplyFitEnquiryRepository;
    private WorksPackagesRepository $worksPackageRepository;

    public function __construct(
        QuestionnaireSessionRepository $questionnaireSessionRepository,
        QuestionnaireItemRepository    $questionnaireItemRepository,
        SupplyFitEnquiryRepository     $supplyFitEnquiryRepository,
        WorksPackagesRepository        $worksPackageRepository
    ) {
        parent::__construct();

        $this->questionnaireSessionRepository = $questionnaireSessionRepository;
        $this->questionnaireItemRepository = $questionnaireItemRepository;
        $this->supplyFitEnquiryRepository = $supplyFitEnquiryRepository;
        $this->worksPackageRepository = $worksPackageRepository;
    }

    public function handle(): void
    {
        $id = $this->argument('id');

        $enquiry = SupplyFitEnquiry::where([
            'id' => $id,
        ])
            ->whereNotNull('postcode')
            ->where('postcode', '<>', '')
            ->limit(1)
            ->first();

        if ($enquiry) {
            $postcode = $this->preparePostcode($enquiry->postcode);

            echo "postcode: $postcode\n";
            echo "enquiry id: $id\n";

            $enquiry->lat = 0;
            $enquiry->long = 0;

            $district = DB::table('Postcode_districts')->where('Postcode', $postcode)->first();
            if ($district) {
                $enquiry->lat = $district->Latitude;
                $enquiry->long = $district->Longitude;

                $enquiry->save();

                $this->sendNotificationsSupplyFit($enquiry);

                return;
            }

            $enquiry->save();
        }
    }

    private function preparePostcode($p): string
    {
        return trim(substr(trim($p), 0, -3));
    }

    private function sendNotificationsSupplyFit(SupplyFitEnquiry $enquiry): void
    {
        if (empty($enquiry->lat)) {
            return;
        }

        $prefSubcontractors = $this->supplyFitEnquiryRepository->getPreferredSubcontractors($enquiry->getId());
        $prefSubcontractorIds = $prefSubcontractors->pluck('id')->toArray();

        $worksPackageId = $enquiry->getWorksPackageId();
        $worksPackageAssignedUsers = [];

        if (!empty($worksPackageId)) {
            $worksPackageAssignedUsers = $this->worksPackageRepository->getAssignedUsers($enquiry->getWorksPackageId());
        }

        $query = User::query()->select('users.*')
            ->join('users_roles', 'users.id', '=', 'users_roles.user_id')
            ->join('roles', 'roles.id', '=', 'users_roles.role_id')
            ->where('roles.slug', '=', Role::ROLE_USER_SLUG);
        if ($enquiry->getScope() === 1) {
            $query->whereIn('users.id', $prefSubcontractorIds);
            $query = $this->applyRadiusQuery($query, $enquiry);
        } else if ($enquiry->getScope() === 2) {
            $query->whereIn('users.id', array_column($worksPackageAssignedUsers, 'id'));
        } else {
            $query = $this->applyRadiusQuery($query, $enquiry);
        }

        $users = $query->get();

        $projectId = $enquiry->getProjectId();
        $worksPackageId = $enquiry->getWorksPackageId();

        $sentToUsers = [];

        foreach ($users as $user) {
            if ($enquiry->scope !== 2 && !in_array($enquiry->product_id, $user->product_ids->toArray())) {
                continue;
            }

            if (!in_array($user->id, $sentToUsers)) {
                $questionnaire = null;

                if ($worksPackageId && $this->questionnaireItemRepository->hasByWorksPackageId($worksPackageId)) {
                    $this->questionnaireSessionRepository->deleteForInquiryUser($enquiry->id, $user->getId());

                    $questionnaire = $this->questionnaireSessionRepository->store(new SessionDto(
                        $user->getId(),
                        $enquiry->getId(),
                        $projectId,
                        $worksPackageId,
                    ));
                }

                Mail::to($user->email)->send(new SupplyFitEnquiryCreated($enquiry, $user, $questionnaire));
                $sentToUsers[] = $user->id;
            }
        }
    }

    public function applyRadiusQuery($query, $enquiry) : Builder
    {
        $query->where(function($q) use ($enquiry) {
            $q->where('is_global', '=', true)
                ->orWhere(function ($q) use ($enquiry) {
                    $q->whereNotNull('lat')
                        ->whereNotNull('long')
                        ->inRadius($enquiry->lat, $enquiry->long, SupplyFitEnquiryRepository::SEARCH_RADIUS);
                });
        });

        return $query;
    }
}
