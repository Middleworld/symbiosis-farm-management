<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'pos/*', // Exclude all POS routes from CSRF protection for API-like functionality
        'admin/companies-house/accounts/generate', // Exclude accounts generation from CSRF
        'webhooks/*', // Exclude all webhook routes from CSRF protection
        'webhooks/stripe-orders', // Explicit exclusion for Stripe order webhooks
    ];
}