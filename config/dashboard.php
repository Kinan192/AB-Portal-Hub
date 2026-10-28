<?php
declare(strict_types=1);

// Navbar and dashboard greeting. The name of the signed-in admin comes from the users table
// (current_profile()); 'name' is only shown when no name is available.
// Every form opens /form-dinamis?id=… and its recap /form-dinamis/respons?id=….
return [
    'profile' => [
        'name' => 'IT Botania',
        'initials' => 'IB',
        'subtitle' => 'IT Division',
    ],
];
