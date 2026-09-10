<?php

return [
    //prod: https://connect.creditsafe.com/v1
    'url' => env('CREDIT_SAFE_URL', 'https://connect.sandbox.creditsafe.com/v1'),
    'username' => env('CREDIT_SAFE_USERNAME', 'demo@thebuildchain.co.uk'),
    'password' => env('CREDIT_SAFE_PASSWORD', ''),
];
