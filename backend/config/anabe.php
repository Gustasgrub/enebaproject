<?php

return [

    // Users with these email addresses can use the admin pages (comma-separated in ADMIN_EMAILS).
    // Configured per environment, so no admin credentials are ever published with the demo.
    'admin_emails' => array_values(array_filter(array_map(
        fn (string $email) => strtolower(trim($email)),
        explode(',', (string) env('ADMIN_EMAILS', '')),
    ))),

];
