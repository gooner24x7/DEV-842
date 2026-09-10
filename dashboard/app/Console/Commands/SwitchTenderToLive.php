<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SupplyFitEnquiry;
use Illuminate\Console\Command;

class SwitchTenderToLive extends Command
{
    protected $signature = 'switch:tender-to-live';
    protected $description = 'Switches tender to live enquiries';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $items = SupplyFitEnquiry::where(['type' => 'tender'])->whereRaw('(actual_starting_date is not null or days is not null)')->limit(100)->get();
        foreach ($items as $item) {
            $assumedStartDate = $item->days ? \DateTime::createFromFormat('d-m-Y', $item->days) : null;
            $actualStartDate = $item->actual_starting_date ? \DateTime::createFromFormat('d-m-Y', $item->actual_starting_date) : null;

            if (($actualStartDate && $actualStartDate <= new \DateTime()) || ($assumedStartDate && $assumedStartDate <= new \DateTime())) {
                $item->type = 'live';
                $item->save();
            }
        }
    }
}
