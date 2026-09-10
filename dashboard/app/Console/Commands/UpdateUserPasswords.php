<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserAuthCheck;
use App\Service\UserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class UpdateUserPasswords extends Command
{
    protected $signature = 'users:set-password {password}';
    protected $description = 'Update all user passwords (for dev/staging/demo only)';

    private UserService $userService;

    public function __construct(UserService $userService)
    {
        parent::__construct();

        $this->userService = $userService;
    }

    public function handle(): void
    {
        $password = $this->argument('password');

        if (config('app.env') == 'live' || config('app.env') == 'production') {
            echo "This command should not be run in live environment \n";
            return;
        }

        if (!$this->userService->validatePassword($password)) {
            echo "Invalid password \n";
            return;
        }

        $hash = Hash::make($password);

        User::query()->update(['password' => $hash]);

        $users = User::whereNotNull('id')->get();

        foreach ($users as $user) {
            UserAuthCheck::create([
                'user_id' => $user->getId(),
                'status' => 1,
            ]);
        }

        echo "Password updated for all users. Entries added in user_auth_check \n";
    }
}
