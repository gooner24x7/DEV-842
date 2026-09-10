<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\QuestionCreated;
use App\Models\Question;
use App\Models\Role;
use App\Models\User;
use App\Repository\QuestionRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class GeocodePostcodeForInquiry extends Command
{
    protected $signature = 'geocode:postcode {question-id}';
    protected $description = 'Decode postcodes into coordinates';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $questionId = $this->argument('question-id');

        $question = Question::where([
            'id' => $questionId,
        ])
            ->whereNull('lat')
            ->whereNotNull('postcode')
            ->where('postcode', '<>', '')
            ->limit(1)
            ->first();

        if ($question) {
            $postcode = $this->preparePostcode($question->postcode);

            echo "$postcode\n";

            $question->lat = 0;
            $question->long = 0;

            $district = DB::table('Postcode_districts')->where('Postcode', $postcode)->first();
            if ($district) {
                $question->lat = $district->Latitude;
                $question->long = $district->Longitude;

                $question->save();

                $this->sendNotifications($question);

                return;
            }

            $question->save();
        }
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

        $users = User::whereNotNull('lat')->
        where(function ($query) use ($question) {
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

            if ($user->hasRole(Role::ROLE_COMPANY_SLUG)) {
                if ($user->billingUser) {
                    $billingUsers[] = $user->billingUser->id;
                }

                if (!in_array($user->id, $sentToUsers)) {
                    Mail::to($user->email)->send(new QuestionCreated($question, $user));
                    $sentToUsers[] = $user->id;
                }
            }
        }

        foreach (array_unique($billingUsers) as $userId) {
            /** @var User $user */
            $user = User::where(['id' => $userId])->first();

            if (!$user->isBranchManager()) {
                continue;
            }

            Mail::to($user->email)->send(new QuestionCreated($question, $user));
        }
    }
}
