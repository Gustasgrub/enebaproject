<?php

return [

    // The public demo admin (credentials in the README), so reviewers can try the admin pages on the
    // live site. Always an admin, in every environment; DemoAccountSeeder creates the account.
    'demo_admin_email' => 'admin@example.com',

    // Further admins, kept out of the repository: comma-separated emails in ADMIN_EMAILS.
    'admin_emails' => array_values(array_filter(array_map(
        fn (string $email) => strtolower(trim($email)),
        explode(',', (string) env('ADMIN_EMAILS', '')),
    ))),

];
