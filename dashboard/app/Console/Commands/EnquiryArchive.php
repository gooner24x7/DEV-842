<?php
declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Question;
use Carbon\Carbon;

class EnquiryArchive extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enquiry:archive';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archive enquiries that are overdue by 1 month';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $app_url = env('APP_URL') ?? '';

        if ($app_url !== 'https://live.thebuildchain.co.uk/') {
            return;
        }

        $questions = Question::whereNull('archived_at')->get();

        foreach($questions as $question) {
            $dueDate = \DateTime::createFromFormat('d-m-Y', $question->days);

            if (!$dueDate) {
                continue;
            } else {
                $dueDate = Carbon::createFromFormat('d-m-Y', $question->days);
            }

            $overdueDate = $dueDate->addMonth();
            $today = Carbon::now();

            if ($overdueDate < $today) {
                $question->setArchivedAt($today);
                $question->save();
            }
        }
    }
}
