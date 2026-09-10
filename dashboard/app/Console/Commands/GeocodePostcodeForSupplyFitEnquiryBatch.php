<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Dto\Questionnaire\SessionDto;
use App\Mail\SupplyFitEnquiryCreated;
use App\Mail\SupplyFitEnquiryCreatedBatch;
use App\Models\Role;
use App\Models\SupplyFitEnquiry;
use App\Models\User;
use App\Repository\PostcodesRepository;
use App\Repository\QuestionnaireItemRepository;
use App\Repository\QuestionnaireSessionRepository;
use App\Repository\SupplyFitEnquiryRepository;
use App\Repository\WorksPackagesRepository;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Collection;

class GeocodePostcodeForSupplyFitEnquiryBatch extends Command
{
    protected $signature = 'geocode:postcode-supply-fit-batch';
    protected $description = 'Decode postcodes into coordinates and send email notifications';

    private QuestionnaireSessionRepository $questionnaireSessionRepository;
    private QuestionnaireItemRepository $questionnaireItemRepository;
    private SupplyFitEnquiryRepository $supplyFitEnquiryRepository;
    private WorksPackagesRepository $worksPackageRepository;
    private PostcodesRepository $postcodesRepository;

    public function __construct(
        QuestionnaireSessionRepository $questionnaireSessionRepository,
        QuestionnaireItemRepository    $questionnaireItemRepository,
        SupplyFitEnquiryRepository     $supplyFitEnquiryRepository,
        WorksPackagesRepository        $worksPackageRepository,
        PostcodesRepository            $postcodesRepository,
    ) {
        parent::__construct();

        $this->questionnaireSessionRepository = $questionnaireSessionRepository;
        $this->questionnaireItemRepository = $questionnaireItemRepository;
        $this->supplyFitEnquiryRepository = $supplyFitEnquiryRepository;
        $this->worksPackageRepository = $worksPackageRepository;
        $this->postcodesRepository = $postcodesRepository;
    }

    public function handle(): void
    {
        $enquiries = $this->supplyFitEnquiryRepository->getLatestBatch();

        if (empty($enquiries)) {
            return;
        }

        echo 'batch id: ' . $enquiries->first()->batch_id . "\n";

        foreach($enquiries as $enquiry) {
            $postcode = $enquiry->postcode;

            if (empty($postcode)) {
                continue;
            }

            echo "enquiry id: $enquiry->id\n";
            echo "postcode: $postcode\n";

            $coords = $this->postcodesRepository->getCoords([$postcode]);

            if (!empty($coords)) {
                $enquiry->lat = $coords[0]['latitude'];
                $enquiry->long = $coords[0]['longitude'];
            } else {
                $districtStr = $this->postcodesRepository->preparePostcode($postcode);
                $district = $this->postcodesRepository->getDistrict($districtStr);

                if ($district) {
                    $enquiry->lat = $district->Latitude;
                    $enquiry->long = $district->Longitude;
                }
            }

            $enquiry->save();
        }

        $this->sendNotificationsSupplyFit($enquiries);
        $this->supplyFitEnquiryRepository->setBatchProcessed($enquiries->first()->batch_id);
    }

    private function preparePostcode($p): string
    {
        return trim(substr(trim($p), 0, -3));
    }

    private function sendNotificationsSupplyFit(Collection $enquiries): void
    {
        // group enquiries by users
        $groupedEnquiries = [];

        foreach($enquiries as $enquiry) {
            if (empty($enquiry->lat)) {
                continue;
            }

            echo "enquiry id: {$enquiry->id}\n";
            echo "enquiry scope: {$enquiry->scope}\n";

            $prefSubcontractors = $this->supplyFitEnquiryRepository->getPreferredSubcontractors($enquiry->getId());
            $prefSubcontractorIds = $prefSubcontractors->pluck('id')->toArray();

            $worksPackageId = $enquiry->getWorksPackageId();
            $worksPackageAssignedUsers = [];

            if (!empty($worksPackageId)) {
                $worksPackageAssignedUsers = $this->worksPackageRepository->getAssignedUsers($enquiry->getWorksPackageId());
            }

            $query = User::query()
                ->select('users.*');

            if ($enquiry->lat !== null && $enquiry->long !== null) {
                $query->selectRaw(
                    '(0.62137 * (6371 * acos(cos(radians(?))
                        * cos(radians(`users`.`lat`))
                        * cos(radians(`users`.`long`)
                        - radians(?))
                        + sin(radians(?))
                        * sin(radians(`users`.`lat`))))) as distance',
                    [$enquiry->lat, $enquiry->long, $enquiry->lat]
                );
            }

            $query->join('users_roles', 'users.id', '=', 'users_roles.user_id')
                ->join('roles', 'roles.id', '=', 'users_roles.role_id')
                ->where('roles.slug', '=', Role::ROLE_USER_SLUG);
            if ($enquiry->getScope() === 2) {
                $query->whereIn('users.id', $prefSubcontractorIds);
                $query = $this->applyRadiusQuery($query, $enquiry);
            } else if ($enquiry->getScope() === 3) {
                $query->whereIn('users.id', array_column($worksPackageAssignedUsers, 'id'));
            } else {
                $query = $this->applyRadiusQuery($query, $enquiry);
            }

            $users = $query->get();

            echo "enquiry total users: " . count($users) . "\n";

            $projectId = $enquiry->getProjectId();
            $worksPackageId = $enquiry->getWorksPackageId();

            $sentToUsers = [];

            foreach ($users as $user) {
                if ($enquiry->scope !== 3 && !in_array($enquiry->product_id, $user->product_ids->toArray())) {
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

                    $distance = $user->distance ?? null;

                    echo "user id: {$user->id}\n";
                    echo "user roles: {$user->role_ids}\n";
                    echo "user isGlobal: {$user->is_global}\n";
                    echo "distance: {$distance}\n";

                    $groupedEnquiries[$user->id][] = [
                        'data' => $enquiry,
                        'questionnaire' => $questionnaire,
                    ];

                    $sentToUsers[] = $user->id;
                }
            }
        }

        foreach($groupedEnquiries as $userId => $enquiries) {
            $user = User::where(['id' => $userId])->first();

            Mail::to($user->email)->send(new SupplyFitEnquiryCreatedBatch($enquiries, $user));
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
