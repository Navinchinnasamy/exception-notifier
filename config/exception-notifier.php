<?php

return [
    'enabled' => env('EXCEPTION_NOTIFIER_ENABLED', true),
    
    'mail' => [
        'to' => array_filter(array_map('trim', explode(',', env('EXCEPTION_NOTIFIER_TO', 'admin@example.com')))),
        'cc' => array_filter(array_map('trim', explode(',', env('EXCEPTION_NOTIFIER_CC', '')))),
        'from' => env('EXCEPTION_NOTIFIER_FROM', env('MAIL_FROM_ADDRESS', 'no-reply@example.com')),
        'from_name' => env('EXCEPTION_NOTIFIER_FROM_NAME', env('MAIL_FROM_NAME', 'Exception Notifier')),
        'subject_prefix' => 'Exception Alert - {ENV}',
    ],
    
    'rate_limiting' => [
        'enabled' => env('EXCEPTION_NOTIFIER_RATE_LIMITING', true),
        'max_emails' => env('EXCEPTION_NOTIFIER_MAX_EMAILS', 5),
        'time_window' => env('EXCEPTION_NOTIFIER_TIME_WINDOW', 3600),
    ],
    
    'grouping' => [
        'enabled' => env('EXCEPTION_NOTIFIER_GROUPING', true),
        'time_window' => env('EXCEPTION_NOTIFIER_GROUPING_WINDOW', 300),
    ],
    
    'database_logging' => [
        'enabled' => false,
        'table' => 'exception_logs',
    ],
    
    'excluded_exceptions' => [
        'Illuminate\Auth\Access\AuthorizationException',
        'Illuminate\Database\Eloquent\ModelNotFoundException',
        'Illuminate\Validation\ValidationException',
        'Symfony\Component\HttpKernel\Exception\HttpException',
        'Symfony\Component\HttpKernel\Exception\NotFoundHttpException',
        'Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException',
    ],
    
    'environments' => [
        'enabled' => ['production', 'staging', 'testing', 'local'],
        'disabled' => [],
    ],
];
