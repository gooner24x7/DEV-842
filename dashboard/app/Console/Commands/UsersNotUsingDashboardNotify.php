<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\DataProvider\ActionLogDataProvider;
use App\Mail\UsersDidntLogin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class UsersNotUsingDashboardNotify extends Command
{
    const string REPORT_EMAIL = 'neilsheld@googlemail.com';
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:notify-no-use-dashboard {days}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    private ActionLogDataProvider $actionLogDataProvider;

    public function __construct(ActionLogDataProvider $actionLogDataProvider)
    {
        parent::__construct();

        $this->actionLogDataProvider = $actionLogDataProvider;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = $this->argument('days') ?? 7;

        $users = $this->actionLogDataProvider->getNotActiveList($days);

        Mail::to(self::REPORT_EMAIL)->send(new UsersDidntLogin($users));

        return 0;
    }
}
