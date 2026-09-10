<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\LogisticsEnquiryCreated;
use App\Models\LogisticsEnquiry;
use App\Models\Role;
use App\Models\User;
use App\Repository\LogisticsEnquiryRepository;
use App\Repository\PostcodesRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class GeocodePostcodeForLogisticsEnquiry extends Command
{
    protected $signature = 'geocode:postcode-logistics {enquiry-id}';
    protected $description = 'Decode postcodes into coordinates';

    private PostcodesRepository $postcodesRepository;

    public function __construct(PostcodesRepository $postcodesRepository)
    {
        parent::__construct();

        $this->postcodesRepository = $postcodesRepository;
    }

    public function handle(): void
    {
        $enquiryId = $this->argument('enquiry-id');

        $enquiry = LogisticsEnquiry::query()
            ->select([
                'logistics_enquiries.id',
                'logistics_enquiries.user_id',
                'logistics_enquiries.comments'
            ])
            ->join('logistics_contacts', 'logistics_contacts.enquiry_id', '=', 'logistics_enquiries.id')
            ->where(['logistics_enquiries.id' => $enquiryId])
            ->whereNull('logistics_contacts.lat')
            ->whereNotNull('logistics_contacts.postcode')
            ->where('logistics_contacts.postcode', '<>', '')
            ->where('status', '=', 0)
            ->groupBy(
                'logistics_enquiries.id',
                'logistics_enquiries.user_id',
                'logistics_enquiries.comments'
            )
            ->limit(1)
            ->get()->first();

        if ($enquiry) {
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
        }
    }

    private function preparePostcode($p): string
    {
        return trim(substr(trim($p), 0, -3));
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
