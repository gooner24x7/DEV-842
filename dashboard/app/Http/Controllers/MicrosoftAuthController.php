<?php

namespace App\Http\Controllers;

use App\Builder\Response\UserResponseBuilder;
use App\Dto\ActionLog\ActionLogDto;
use App\Mail\MicrosoftUserNotFound;
use App\Models\Role;
use App\Models\User;
use App\Repository\ActionLogRepository;
use App\Service\SubscriptionService;
use App\Service\UserService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;

class MicrosoftAuthController extends Controller
{
    private ActionLogRepository $actionLogRepository;
    private SubscriptionService $subscriptionService;
    private UserService $userService;

    public function __construct(
        ActionLogRepository $actionLogRepository,
        SubscriptionService $subscriptionService,
        UserService $userService
    ) {
        $this->actionLogRepository = $actionLogRepository;
        $this->subscriptionService = $subscriptionService;
        $this->userService = $userService;
    }

    /**
     * Redirect the user to the Microsoft authentication page.
     *
     * @return RedirectResponse
     */
    public function redirectToMicrosoft(): RedirectResponse
    {
        return Socialite::driver('microsoft')->redirect();
    }

    /**
     * Obtain the user information from Microsoft.
     *
     * @return JsonResponse|RedirectResponse
     */
    public function handleMicrosoftCallback()
    {
        try {
            $microsoftUser = Socialite::driver('microsoft')->user();
        } catch (Exception $e) {
            Log::error('Microsoft authentication failed: ' . $e->getMessage());
            return redirect('/login?error=Microsoft authentication failed');
        }

        $user = User::query()
            ->select('users.*')
            ->join('users_roles', 'users.id', '=', 'users_roles.user_id')
            ->join('roles', 'users_roles.role_id', '=', 'roles.id')
            ->where('users.email', $microsoftUser->getEmail())
            ->where('roles.slug', '!=', Role::ROLE_BILLING_USER_SLUG)
            ->orderBy('users.created_at', 'desc')
            ->get()->first();

        if (empty($user)) {
            Mail::to('support@thebuildchain.co.uk')->send(new MicrosoftUserNotFound($microsoftUser->getEmail()));

            return redirect("/login?error=No account found for this email address ({$microsoftUser->getEmail()}). Please contact support@thebuildchain.co.uk for assistance");
        }

//        if (!$user) {
//            $username = $microsoftUser->getNickname() ?: $microsoftUser->getEmail();
//            // Check if username already exists
//            if (User::where('username', $username)->exists()) {
//                $username = $microsoftUser->getEmail();
//            }
//
//            $user = User::create([
//                'first_name' => $microsoftUser->user['givenName'] ?? '',
//                'last_name' => $microsoftUser->user['surname'] ?? '',
//                'email' => $microsoftUser->getEmail(),
//                'username' => $username,
//                'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(24)),
//            ]);
//
//            $userRole = \App\Models\Role::where(['slug' => 'user'])->first();
//            if ($userRole) {
//                $user->roles()->sync($userRole->id);
//            }
//        }

        $token = Auth::guard('api')->login($user);

        try {
            $this->subscriptionService->ensureSubscribed($user);
        } catch (Exception $e) {
            Auth::guard('api')->logout();
            return redirect('/login?error=You don\'t have an active subscription');
        }

        $this->actionLogRepository->create(new ActionLogDto(
            ActionLogDto::ACTION_LOGIN,
            '/microsoft/callback',
            'user logged in via Microsoft',
            $user->getId()
        ));

        $subsName = $this->subscriptionService->getUserStripeSubscriptionName($user->getId());

        $userData = UserResponseBuilder::getResponseForUser($user, $subsName);

        $loginResponse = [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            'user' => $userData,
        ];

        $encodedData = base64_encode(json_encode($loginResponse));

        return redirect('/login?ms_data=' . $encodedData);
    }
}
