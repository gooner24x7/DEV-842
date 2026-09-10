<?php

return [
    'scopes' => env('ZOHO_DESK_SCOPES', 'Desk.tickets.ALL, '),
    'redirect_after_authorization_url' => '/',
    'redirect_url' => env('ZOHO_DESK_REDIRECT_URL'),
    'client_id' => env('ZOHO_DESK_CLIENT_ID'),
    'client_secret' => env('ZOHO_DESK_CLIENT_SECRET'),
    'access_type' => env('ZOHO_DESK_ACCESS_TYPE', 'offline'),
    'organisation_id' => env('ZOHO_DESK_ORGANISATION_ID'),
    'base_url' => env('ZOHO_DESK_BASE_URL', 'https://desk.zoho.eu'),
    'accounts_server' => env('ZOHO_DESK_ACCOUNTS_SERVER', 'https://accounts.zoho.eu'),
    'department_id' => env('ZOHO_DESK_DEPARTMENT_ID'),
];
