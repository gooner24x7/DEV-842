<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Service\UserService;
use Illuminate\Console\Command;

class UpdateCreditsafeInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:update-creditsafe-info';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates creditsafe info for all users with a valid company reg no';

    private UserService $userService;

    public function __construct(UserService $userService)
    {
        parent::__construct();

        $this->userService = $userService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $users = User::query()->where('company_number', '!=', '')->get();

        $users->each(function ($user) {
            $this->userService->updateCreditsafeInfo($user->getCompanyNumber(), $user->getId());
        });

        return 0;
    }
}
