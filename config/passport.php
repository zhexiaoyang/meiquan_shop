<?php

return [
    'users' => [
        'client_id' => env('USERS_CLIENT_ID'),
        'client_secret' => env('USERS_CLIENT_SECRET'),
    ],
    'admins' => [
        'client_id' => env('ADMINS_CLIENT_ID'),
        'client_secret' => env('ADMINS_CLIENT_SECRET'),
    ],
    'scopes' => [
        'user' => 'user',
        'admin' => 'admin',
        '*' => 'all scopes',
    ],
];
