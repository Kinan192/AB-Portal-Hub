<?php
declare(strict_types=1);

// Initial frontend values; replace with account and submission data during backend integration.
return [
    // Shown on the dashboard ("/")
    'profile' => [
        'name' => 'IT Botania',
        'initials' => 'IB',
        'subtitle' => 'IT Division',
    ],
    'daily_progress' => [
        'completed' => 5,
        'target' => 6,
    ],
    // Shown in the navbar of the form, rekap and account pages
    'account' => [
        'name' => 'IT botania',
        'initials' => 'IB',
        'role' => 'Superadmin',
        'email' => 'it.btc@awalbros.com',
    ],
];
