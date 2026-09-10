<?php

namespace App\Http\Controllers;

use App\Service\ZohoOauthCustom;

class ZohoOAuthController extends Controller
{
    public function __invoke(ZohoOauthCustom $zohoOAuth)
    {
        return $zohoOAuth->flow();
    }
}
