<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Builder\Response\UserResponseBuilder;
use App\Dto\ActionLog\ActionLogDto;
use App\Mail\PreferredUploaded;
use App\Mail\UserIpLocked;
use App\Mail\UserRegister;
use App\Mail\UserResetPassword;
use App\Models\Answer;
use App\Models\IpAuthAttempt;
use App\Models\Message;
use App\Models\Question;
use App\Models\Role;
use App\Models\User;
use App\Models\UserAuthCheck;
use App\Repository\ActionLogRepository;
use App\Repository\IpAuthAttemptsRepository;
use App\Repository\QuestionRepository;
use App\Service\SubscriptionService;
use App\Service\UserService;
use App\Service\ZohoCrmService;
use App\Service\ZohoOauthCustom;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Redis;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class AuthController extends Controller
{
    private Redis $redis;
    private UserService $userService;
    private ActionLogRepository $actionLogRepository;
    private IpAuthAttemptsRepository $ipAuthRepository;
    private SubscriptionService $subscriptionService;

    public function __construct(
        Redis                    $redis,
        UserService              $userService,
        ActionLogRepository      $actionLogRepository,
        IpAuthAttemptsRepository $ipAuthRepository,
        SubscriptionService      $subscriptionService
    ) {
        $this->redis = $redis;
        $this->userService = $userService;
        $this->actionLogRepository = $actionLogRepository;
        $this->ipAuthRepository = $ipAuthRepository;
        $this->subscriptionService = $subscriptionService;
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $values = $request->validate([
            'username' => 'required|string',
        ]);

        $username = $values['username'] ?? null;

        if (empty($username)) {
            return response()->json(['error' => 'Bad request'], 400);
        }

        $user = $this->userService->getByUsername($username);

        if (!$user) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $user->refresh();

        if(empty($user->getEmail())) {
            return response()->json(['error' => 'Email address not found'], 500);
        }

        $randomPassword = $this->userService->generateNewPassword($user);

        UserAuthCheck::updateOrCreate(
            ['user_id' => $user->getId()],
            ['status' => 1]
        );

        try {
            Mail::to($user->getEmail())->send(new UserResetPassword($user, $randomPassword));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json(['success' => 'Password reset email sent to: ' . $user->getEmail()]);
    }

    public function loginAs(Request $request): JsonResponse
    {
        $id = $request->get('id');
        $user = $this->userService->getById((int)$id);
        $currentUser = $this->userService->getCurrentUser();

        if (!$user) {
            return response()->json(['error' => 'Not found'], 404);
        }

        if (!$currentUser || !$currentUser->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $token = (string)Auth::login($user);

        $rememberMeToken = null;
        $rememberMe = $request->request->get('rememberMe', false);
        if ($rememberMe) {
            $rememberMeToken = md5($token . $user->username);
        }

        return $this->respondWithToken($token, $rememberMe, $rememberMeToken);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $ttl = 3600 * 24;
        if ($credentials['username'] == 'jack') {
            $ttl = 3600 * 24 * 10;
        }

        $ip = $request->ip();

        /** @var IpAuthAttempt $currentAttempts */
        $currentAttempts = $this->ipAuthRepository->getByIp($ip);
        if ($currentAttempts && $currentAttempts->attempts > 4) {
            Mail::to(Config::get('mail.from')['address'])->send(new UserIpLocked($credentials['username'], $ip));

            return response()->json(['error' => 'Too many failed attempts, please contact ' . Config::get('mail.from')['address']], 401);
        }

        if (!$token = auth()->setTTL($ttl)->attempt($credentials)) {
            if (!$currentAttempts) {
                $this->ipAuthRepository->create($ip);
            } else {
                $this->ipAuthRepository->incrementAttempts($currentAttempts);
            }

            return response()->json(['error' => 'Wrong login or password'], 401);
        }

        $currentAttempts?->delete();

        /** @var User $user */
        $user = Auth::user();

        // check if password reset is required
        $userAuthCheck = UserAuthCheck::where('user_id', '=', $user->getId())->first();

        if (!$userAuthCheck || $userAuthCheck->status == 0) {
            return response()->json(['reset_required' => true]);
        }

        try {
            $this->subscriptionService->ensureSubscribed($user);
        } catch (Exception $e) {
            Auth::logout();
            return response()->json(['error' => 'You don\'t have an active subscription'], 401);
        }

        $rememberMeToken = null;
        $rememberMe = $request->request->get('rememberMe', false);
        if ($rememberMe) {
            $rememberMeToken = md5($token . $user->username);
        }

        $user->remember_api_token = $rememberMeToken;
        $user->save();

        $this->actionLogRepository->create(new ActionLogDto(
            ActionLogDto::ACTION_LOGIN,
            '/login',
            'user logged in',
            $user->getId()
        ));

        return $this->respondWithToken($token, $rememberMe ?? false, $rememberMeToken);
    }

    /**
     * Log the user out (Invalidate the token).
     */
    public function logout(): JsonResponse
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out'])->withCookie(cookie('remember-me', '', 0));
    }

    /**
     * Get the token array structure.
     */
    protected function respondWithToken(string $token, bool $rememberMe = false, ?string $rememberMeToken = null): JsonResponse
    {
        $ttl = auth()->factory()->getTTL();
        $expiresIn = $ttl * 60;

        $user = $this->userService->getCurrentUser();
        $user = User::find($user->getId());

        $response = response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $expiresIn,
            'user' => ($user) ? UserResponseBuilder::getResponseForUser($user, '') : null,
        ]);

        if ($rememberMe) {
            $response->withCookie(cookie('remember-me', $rememberMeToken, 60 * 24 * 30 * 24));
        }

        return $response;
    }

    /**
     * Get the authenticated User.
     * @throws \RedisException
     */
    public function me(): JsonResponse
    {
        $user = $this->userService->getCurrentUser();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $questionsIndicatorKey = sprintf(Question::NEW_ENQUIRIES_INDICATOR_CACHE, $user->id);
        $questionsLastReadDateTime = $this->redis->get($questionsIndicatorKey);

        $newEnquiries = 0;
        $newChatMessages = 0;
        if ($user->hasRole(Role::ROLE_COMPANY_SLUG) && $questionsLastReadDateTime) {
            $query = Question::query()
                ->whereNull('archived_at');
            if (!$user->is_global) {
                $query->inRadius($user->lat ?? 0, $user->long ?? 0, QuestionRepository::SEARCH_RADIUS);
            }

            $newEnquiries = $query->whereIn('questions.product_id', $user->product_ids)
                ->where('created_at', '>', $questionsLastReadDateTime)
                ->count('id');
        }

        $newMsgIndicatorKey = sprintf(Question::NEW_ENQUIRIES_INDICATOR_CACHE, $user->getId());
        $newChatMessages = Message::query()
            ->where('interlocutor_id', '=', $user->getId())
            ->where('created_at', '>', $this->redis->get($newMsgIndicatorKey))
            ->count('id');

        $newChatMessages += Answer::query()
            ->join('questions', 'questions.id', '=', 'answers.question_id')
            ->where('questions.user_id', '=', $user->getId())
            ->where('answers.created_at', '>', $this->redis->get($newMsgIndicatorKey))
            ->count('answers.id');

        return response()->json([
            'user' => $user,
            'updates' => [
                'newEnquiries' => $newEnquiries,
                'newEnquiriesChatMessages' => $newChatMessages,
            ]
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'username' => 'required|unique:users',
            'email' => 'required|email',
            'postcode' => 'required',
            'product_id' => 'required',
            'days' => 'required',
            'comment' => 'required',
            'phone' => '',
            'country' => '',
            'city' => '',
            'addr_line_1' => '',
            'addr_line_2' => '',
        ]);

        $userData = Arr::only($validatedData, [
            'first_name',
            'last_name',
            'username',
            'email',
            'postcode',
            'phone',
            'country',
            'city',
            'addr_line_1',
            'addr_line_2',
        ]);

        $questionData = Arr::only($validatedData, [
            'postcode',
            'product_id',
            'days',
            'comment'
        ]);

        $user = $this->userService->getByUsername($userData['username'] ?? '');
        if ($user) {
            throw new BadRequestException('User already exists. Please log in.');
        }

        DB::beginTransaction();

        //generate random password
        $randomPassword = Str::random(12);

        $userData['password'] = Hash::make($randomPassword);

        //TODO: use repository
        $user = User::create($userData);
        if ($user) {
            $userRole = Role::where(['slug' => 'user'])->first();
            if ($userRole) {
                $user->roles()->sync($userRole->id);
            }

            $question = Question::create(array_merge($questionData, [
                'user_id' => $user->id,
            ]));

            if (!$question) {
                DB::rollBack();
            }

            Mail::to($user->email)->send(new UserRegister($user, $randomPassword));

            DB::commit();

            return response()->json($question);
        }

        DB::rollBack();

        return response()->json([]);
    }

    /**
     * @throws Exception
     */
    public function saveProfile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
            'postcode' => 'required',
            'phone' => '',
            'country' => '',
            'city' => '',
            'addr_line_1' => '',
            'addr_line_2' => '',
            'password' => '',
            'product_ids' => '',
            'locations' => '',
            'head_office_address' => '',
            'customer_service' => '',
            'description' => '',
            'logo_url' => '',
        ]);

        /** @var User $user */
        $user = $this->userService->getCurrentUser();

        $user->email = $data['email'] ?? '';
        $user->first_name = $data['first_name'] ?? '';
        $user->last_name = $data['last_name'] ?? '';
        $user->postcode = $data['postcode'] ?? '';
        $user->phone = $data['phone'] ?? null;
        $user->country = $data['country'] ?? null;
        $user->city = $data['city'] ?? null;
        $user->addr_line_1 = $data['addr_line_1'] ?? null;
        $user->addr_line_2 = $data['addr_line_2'] ?? null;
        $user->locations = $data['locations'] ?? null;
        $user->head_office_address = $data['head_office_address'] ?? null;
        $user->customer_service = $data['customer_service'] ?? null;
        $user->description = $data['description'] ?? null;
        $user->logo_url = $data['logo_url'] ?? null;

        $data['password'] = trim($data['password'] ?? '');

        if (!empty($data['password'])) {
            if(!$this->userService->validatePassword($data['password'])) {
                return response()->json(['error' => 'Invalid password'], 400);
            }

            $user->password = Hash::make($data['password']);
        }

        $fileUploaded = false;
        if ($request->hasFile('preferred_suppliers_file')) {
            $file = $this->uploadFile($user, $request);

            // upload file to zoho and link to user account
            $zohoOauthCustom = new ZohoOauthCustom($request);
            $zohoCrmService = new ZohoCrmService($zohoOauthCustom);

            $zohoUpload = $zohoCrmService->uploadFile($file);
            $zohoAccount = $zohoCrmService->searchAccounts($user->first_name);

            if (!empty($zohoAccount['id']) && !empty($zohoAccount['Merchants_Received'])) {
                $zohoFileData = [
                    'Merchants_Received' => [
                        [
                            'attachment_id' => $zohoAccount['Merchants_Received'][0]['attachment_Id'],
                            '_delete' => null
                        ]
                    ]
                ];

                $zohoCrmService->updateAccount($zohoAccount['id'], $zohoFileData);
            }

            if (!empty($zohoAccount['id']) && !empty($zohoUpload['details']['id'])) {
                $zohoFileData = [
                    'Merchants_Received' => [
                        ['file_id' => $zohoUpload['details']['id']],
                    ],
                    'Merchants_Received2' => true
                ];

                $zohoCrmService->updateAccount($zohoAccount['id'], $zohoFileData);
            }

            if ($file) {
                $user->preferred_suppliers_file = $file;
                $user->onboardingChecklist()->update(['preferred_supplier' => true]);

                $fileUploaded = true;
            }
        }

        if (!$user->save()) {
            return response()->json(['error' => 'Error saving user'], 500);
        }

        if ($fileUploaded) {
            $users = User::select(['*'])->leftJoin('users_roles', 'users.id', '=', 'users_roles.user_id')->
            leftJoin('roles', 'roles.id', '=', 'users_roles.role_id')->where(['roles.slug' => 'admin'])->get();

            $users = array_filter(array_map(function ($item) {
                return $item['email'] ?? null;
            }, $users->toArray()));

            Mail::to($users)->send(new PreferredUploaded($user));
        }

        if (!empty($data['product_ids'])) {
            $user->products()->sync($data['product_ids']);
        }

        return response()->json($user);
    }

    private function uploadFile(User $user, Request $request): ?string
    {
        $file = $request->file('preferred_suppliers_file');

        return $file?->storeAs($user->id . "/preferred-suppliers-uploads", $file->getClientOriginalName());
    }

    /**
     * Refresh a token.
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            /** @var User|null $user */
            $user = $this->userService->getCurrentUser();
            if (!$user) {
                $rememberMeToken = $request->cookie('remember-me');
                if ($rememberMeToken) {
                    $user = User::where(['remember_api_token' => $rememberMeToken])->first();
                    if ($user) {
                        Auth::login($user);

                        $token = auth()->refresh();

                        return $this->respondWithToken($token);
                    }
                }
            }
        } catch (Exception $e) {
        }

        return new JsonResponse('');
    }
}
