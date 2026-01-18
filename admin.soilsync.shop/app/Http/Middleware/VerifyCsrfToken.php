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
        'oauth*', // Exclude all OAuth routes from CSRF for proper OAuth flow
        'admin/login', // Exclude admin login from CSRF to allow login after logout redirect
        'admin/ai-helper/*', // Exclude AI helper routes from CSRF for AJAX functionality
    ];
}