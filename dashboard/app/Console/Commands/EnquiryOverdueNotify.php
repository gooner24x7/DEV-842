<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\EnquiryOverdue;
use App\Models\Question;
use App\Models\QuestionAccessToken;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Random\RandomException;

class EnquiryOverdueNotify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enquiry:notify-overdue';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends an email notification when an enquiry is overdue by 7 days';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * @throws RandomException
     */
    public function handle(): int
    {
        // query db to get list of active overdue enquiries where notify-overdue = 0
        $questions = Question::where(['archived_at' => null, 'notify_overdue' => 0])
            ->whereRaw('DATE(days) IS NOT NULL')
            ->whereRaw('DATE(days + INTERVAL 7 DAY) < CURDATE()')
            ->get();

        foreach($questions as $question) {
            $user = User::find($question->user_id);

            if (!$user) {
                continue;
            }

            // generate an access token and store it in the database against the enquiry id
            $token = bin2hex(random_bytes(20));

            QuestionAccessToken::create([
                'question_id' => $question->id,
                'token' => $token,
//                'expires_at' => Carbon::now()->addDays(7)
            ]);

            // send email to the user that created the enquiry
            Mail::to($user->email)->send(new EnquiryOverdue($user, $question, $token));

            // set notify-overdue to 1 to prevent repeat emails
            $question->notify_overdue = 1;
            $question->save();
        }

        return 0;
    }
}
