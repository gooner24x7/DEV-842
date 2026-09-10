<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Dto\Questionnaire\SessionDto;
use App\Mail\LogisticsEnquiryCreated;
use App\Mail\QuestionCreated;
use App\Mail\QuestionCreatedNational;
use App\Mail\SupplyFitEnquiryCreated;
use App\Models\LogisticsEnquiry;
use App\Models\Question;
use App\Models\Questionnaire\Project;
use App\Models\Role;
use App\Models\SupplyFitEnquiry;
use App\Models\User;
use App\Repository\LogisticsEnquiryRepository;
use App\Repository\PostcodesRepository;
use App\Repository\QuestionnaireItemRepository;
use App\Repository\QuestionnaireSessionRepository;
use App\Repository\QuestionRepository;
use App\Repository\SupplyFitEnquiryRepository;
use App\Repository\WorksPackagesRepository;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class GeocodePostcodes extends Command
{
    protected $signature = 'geocode:postcodes';
    protected $description = 'Decode postcodes into coordinates';

    private QuestionnaireSessionRepository $questionnaireSessionRepository;
    private QuestionnaireItemRepository $questionnaireItemRepository;
    private PostcodesRepository $postcodesRepository;
    private QuestionRepository $questionRepository;
    private SupplyFitEnquiryRepository $supplyFitEnquiryRepository;
    private WorksPackagesRepository $worksPackageRepository;

    public function __construct(
        QuestionnaireSessionRepository $questionnaireSessionRepository,
        QuestionnaireItemRepository    $questionnaireItemRepository,
        PostcodesRepository            $postcodesRepository,
        QuestionRepository             $questionRepository,
        SupplyFitEnquiryRepository     $supplyFitEnquiryRepository,
        WorksPackagesRepository        $worksPackageRepository
    ) {
        parent::__construct();

        $this->questionnaireSessionRepository = $questionnaireSessionRepository;
        $this->questionnaireItemRepository = $questionnaireItemRepository;
        $this->postcodesRepository = $postcodesRepository;
        $this->questionRepository = $questionRepository;
        $this->supplyFitEnquiryRepository = $supplyFitEnquiryRepository;
        $this->worksPackageRepository = $worksPackageRepository;
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        echo "geocode users \n";
        $users = DB::table('users')
            ->select('id', 'postcode', 'lat', 'long')
            ->whereNull('lat')
            ->where('postcode', '!=', '')
            ->get()->toArray();

        $userPostcodes = array_column($users, 'postcode');
        $coords = $this->postcodesRepository->getCoords($userPostcodes);

        foreach ($users as $key => $user) {
            $userLat = null;
            $userLong = null;

            $searchKey = array_search($user->postcode, array_column($coords, 'postcode'));

            if($searchKey === false) {
                $postcode = $this->preparePostcode($user->postcode);
                $district = $this->postcodesRepository->getDistrict($postcode);

                $userLat = $district->Latitude ?? null;
                $userLong = $district->Longitude ?? null;
            } else {
                $userLat = $coords[$searchKey]['latitude'] ?? null;
                $userLong = $coords[$searchKey]['longitude'] ?? null;
            }

            echo "User ID: {$user->id} {$user->postcode} {$userLat} {$userLong}\n";

            $user->lat = $userLat;
            $user->long = $userLong;

            $users[$key] = $user;

            DB::table('users')->where('id', '=', $user->id)->update(['lat' => $userLat, 'long' => $userLong]);
        }

        // purchase & hire enquiries
        echo "geocode purchase & hire enquiries \n";
        $questions = Question::query()
            ->whereNull('lat')
            ->whereNotNull('postcode')
            ->where('postcode', '!=', '')
            ->where('status', '=', 0)
            ->limit(10)
            ->get();

        foreach ($questions as $question) {
            $nationals = $question->nationals()->get();
            $postcode = $this->preparePostcode($question->postcode);

            echo "$postcode\n";

            $question->lat = 0;
            $question->long = 0;

            $district = $this->postcodesRepository->getDistrict($postcode);
            if ($district) {
                $question->lat = $district->Latitude;
                $question->long = $district->Longitude;

                $this->sendNotifications($question);

                foreach ($nationals as $national) {
                    Mail::to($national->email)->send(new QuestionCreatedNational($question, $national));
                }
            }

            $question->save();
        }

        // supply & fit enquiries
        // dev-699 moved to sf enquiry repo
//        echo "geocode supply & fit enquiries \n";
//        $enquiries = SupplyFitEnquiry::query()
//            ->whereNull('lat')
//            ->whereNotNull('postcode')
//            ->where('postcode', '!=', '')
//            ->where('status', '=', 0)
//            ->limit(10)
//            ->get();
//
//        foreach ($enquiries as $enquiry) {
//            $postcode = $this->preparePostcode($enquiry->postcode);
//
//            echo "$postcode\n";
//
//            $enquiry->lat = 0;
//            $enquiry->long = 0;
//
//            $district = $this->postcodesRepository->getDistrict($postcode);
//            if ($district) {
//                $enquiry->lat = $district->Latitude;
//                $enquiry->long = $district->Longitude;
//
//                $enquiry->save();
//
//                $this->sendNotificationsSupplyFit($enquiry);
//            }
//
//            $enquiry->save();
//        }

        // logistics enquiries
        echo "geocode logistics enquiries \n";
        $enquiries = LogisticsEnquiry::query()
            ->select([
                'logistics_enquiries.id',
                'logistics_enquiries.user_id',
                'logistics_enquiries.comments'
            ])
            ->join('logistics_contacts', 'logistics_contacts.enquiry_id', '=', 'logistics_enquiries.id')
            ->whereNull('logistics_contacts.lat')
            ->whereNotNull('logistics_contacts.postcode')
            ->where('logistics_contacts.postcode', '!=', '')
            ->where('status', '=', 0)
            ->groupBy(
                'logistics_enquiries.id',
                'logistics_enquiries.user_id',
                'logistics_enquiries.comments'
            )
            ->limit(10)
            ->get();

        $enquiries->each(function($enquiry) {
            $contacts = $enquiry->contacts;
            $coords = [];

            $contacts->each(function($contact) use (&$coords) {
                $postcode = $this->preparePostcode($contact->postcode);
                echo "$postcode\n";

                $contact->lat = 0;
                $contact->long = 0;

                $district = $this->postcodesRepository->getDistrict($postcode);

                if ($district) {
                    $contact->lat = $district->Latitude;
                    $contact->long = $district->Longitude;

                    $contact->save();
                }

                $coords[] = ['lat' => $contact->lat, 'long' => $contact->long];
            });

            $this->sendNotificationsLogistics($enquiry, $coords);
        });

        // projects
        echo "geocode projects \n";
        $projects = Project::whereNull('lat')
            ->whereNotNull('postcode')
            ->where('postcode', '!=', '')
            ->limit(100)
            ->get();

        $projects->each(function($project) {
            $postcode = $project->getPostcode();

            if (empty($postcode)) {
                return true;
            }

            echo "$postcode\n";

            $coords = $this->postcodesRepository->getCoords([$postcode]);

            if (!empty($coords)) {
                $project->lat = $coords[0]['latitude'];
                $project->long = $coords[0]['longitude'];
            } else {
                $districtStr = $this->postcodesRepository->preparePostcode($postcode);
                $district = $this->postcodesRepository->getDistrict($districtStr);

                if ($district) {
                    $project->lat = $district->Latitude;
                    $project->long = $district->Longitude;
                }
            }

            $project->save();
        });
    }

    private function preparePostcode($p): string
    {
        return trim(substr(trim($p), 0, -3));
    }

    private function sendNotifications(Question $question): void
    {
        if (empty($question->lat)) {
            return;
        }

        $prefSuppliers = $this->questionRepository->getPreferredSuppliers($question->getId());
        $prefSupplierIds = $prefSuppliers->pluck('id')->toArray();

        $users = User::whereNotNull('lat')->where(function ($query) use ($question) {
            if ($question->lat == null || $question->long == null) {
                $query->where('is_global', '=', true);
                return;
            }

            $query->inRadius($question->lat, $question->long, QuestionRepository::SEARCH_RADIUS)
                ->orWhere('is_global', '=', true);
        })->select('users.*')->get();

        $billingUsers = [];
        $sentToUsers = [];

        foreach ($users as $user) {
            if (!in_array($question->product_id, $user->product_ids->toArray())) {
                continue;
            }

            // only send closed enquiries to preferred suppliers
            if ($question->getScope() === 1 && !in_array($user->getId(), $prefSupplierIds)) {
                continue;
            }

            if ($user->hasRole(Role::ROLE_COMPANY_SLUG)) {
                if ($user->billing_user_id) {
                    $billingUsers[] = $user->billing_user_id;
                }

                if (!in_array($user->id, $sentToUsers)) {
                    Mail::to($user->email)->send(new QuestionCreated($question, $user));
                    $sentToUsers[] = $user->id;
                }
            }
        }

        foreach (array_unique($billingUsers) as $userId) {
            echo "notification to billing user $userId\n";

            /** @var User $user */
            $user = User::where(['id' => $userId])->first();

            if (!$user) {
                continue;
            }

            if (!$user->isBranchManager() || $question->getScope() === 1) {
                continue;
            }

            Mail::to($user->email)->send(new QuestionCreated($question, $user));
        }
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
            $query->where(function ($query) use ($enquiry) {
                $query = $this->applyRadiusQuery($query, $enquiry);
            });
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

    private function sendNotificationsLogistics(LogisticsEnquiry $enquiry, array $coords): void
    {
        if (empty($coords)) {
            return;
        }

        $users = User::whereNotNull('lat')->where(function ($query) use ($coords) {
            foreach($coords as $item) {
                if (is_null($item['lat']) || is_null($item['long'])) {
                    $query->where('is_global', '=', true);
                    return;
                }
            }

            $query->inRadiusArray($coords, LogisticsEnquiryRepository::SEARCH_RADIUS)
                ->orWhere('is_global', '=', true);
        })->select('users.*')->get();

        $sentToUsers = [];

        foreach ($users as $user) {
            if ($user->hasRole(Role::ROLE_LOGISTICS)) {
                if (!in_array($user->id, $sentToUsers)) {
                    Mail::to($user->email)->send(new LogisticsEnquiryCreated($enquiry, $user));
                    $sentToUsers[] = $user->id;
                }
            }
        }
    }
}
