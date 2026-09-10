<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataProvider\UserDataProvider;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;
use RedisException;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends CashierController
{
    private UserDataProvider $userDataProvider;

    public function __construct(UserDataProvider $userDataProvider)
    {
        parent::__construct();

        $this->userDataProvider = $userDataProvider;
    }

    /**
     * @throws RedisException
     */
    public function handleWebhook(Request $request): Response
    {
        $result = parent::handleWebhook($request);

        $payload = json_decode($request->getContent(), true);
        /** @var User $user */
        $user = $this->getUserByStripeId($payload['data']['object']['customer']);
        if ($user) {
            $this->userDataProvider->touch($user->getId());
        }

        return $result;
    }
}
