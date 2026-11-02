<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Documentation Access
    |--------------------------------------------------------------------------
    |
    | The email addresses allowed to view the API documentation, as a comma
    | separated list. When the list is empty, nobody can view the docs.
    |
    */

    'allowed_emails' => array_values(array_filter(array_map(
        fn (string $email): string => strtolower(trim($email)),
        explode(',', (string) env('DOCS_ALLOWED_EMAILS', ''))
    ))),

];
