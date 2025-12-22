<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Subscription Grace Period
    |--------------------------------------------------------------------------
    |
    | Number of days a subscription remains in grace period after payment failure.
    | During this period, automatic retries will be attempted.
    |
    */
    'grace_period_days' => env('SUBSCRIPTION_GRACE_PERIOD_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Maximum Retry Attempts
    |--------------------------------------------------------------------------
    |
    | Maximum number of automatic payment retry attempts before subscription
    | is automatically cancelled.
    |
    */
    'max_retry_attempts' => env('SUBSCRIPTION_MAX_RETRY_ATTEMPTS', 3),

    /*
    |--------------------------------------------------------------------------
    | Retry Delays (in days)
    |--------------------------------------------------------------------------
    |
    | Array of delays between retry attempts. Each element represents the
    | number of days to wait before the next retry attempt.
    | Format: [first_retry, second_retry, third_retry, ...]
    |
    */
    'retry_delays' => array_map('intval', explode(',', env('SUBSCRIPTION_RETRY_DELAYS', '2,4,6'))),

    /*
    |--------------------------------------------------------------------------
    | Admin Email for Notifications
    |--------------------------------------------------------------------------
    |
    | Email address to receive admin notifications about subscription issues.
    |
    */
    'admin_email' => env('ADMIN_EMAIL', 'middleworldfarms@gmail.com'),
];
