<?php
declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('geocode:postcodes')->everyMinute();
        $schedule->command('geocode:postcode-supply-fit-batch')->everyMinute();
        $schedule->command('zoho:generate-spreadsheet-report')->daily();
        $schedule->command('user:notify-no-use-dashboard 7')->daily();
        $schedule->command('switch:tender-to-live')->everyMinute();
        $schedule->command('enquiry:archive')->daily();
        //$schedule->command('enquiry:notify-overdue')->dailyAt('09:00');
        $schedule->command('user:update-creditsafe-info')->monthly();

//        if (config('app.env') === 'production' || config('app.env') === 'live') {
//            $schedule->command('tenders:sync')->daily();
//        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
