<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserPaymentMethod;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ImportWooStripeCustomers extends Command
{
    protected $signature = 'vegbox:import-woo-stripe
        {--user= : Limit import to a specific Laravel user ID}
        {--chunk=100 : Number of users to process per chunk}
        {--dry-run : Output proposed changes without persisting them}';

    protected $description = 'Sync Stripe customer/payment method data from WooCommerce into the admin app';

    public function handle(): int
    {
        $query = User::query()
            ->whereNotNull('woo_customer_id')
            ->orderBy('id');

        if ($userId = $this->option('user')) {
            $query->where('id', $userId);
        }

        $chunkSize = (int) $this->option('chunk');
        $dryRun = $this->option('dry-run');

        $summary = [
            'users_processed' => 0,
            'users_updated' => 0,
            'methods_synced' => 0,
            'default_methods' => 0,
        ];

        $query->chunkById($chunkSize, function ($users) use (&$summary, $dryRun) {
            foreach ($users as $user) {
                $summary['users_processed']++;

                $wpUserId = $user->woo_customer_id;
                if (!$wpUserId) {
                    $this->warn("User {$user->id} missing woo_customer_id, skipping");
                    continue;
                }

                $stripeCustomerId = $this->fetchStripeCustomerId($wpUserId);
                $paymentTokens = $this->fetchPaymentTokens($wpUserId);

                if (!$stripeCustomerId && $paymentTokens->isEmpty()) {
                    $this->line("No Stripe data for user {$user->id} (wp {$wpUserId})");
                    continue;
                }

                $summary['users_updated']++;

                if ($dryRun) {
                    $this->outputDryRunReport($user, $stripeCustomerId, $paymentTokens);
                    continue;
                }

                $this->persistStripeData($user, $stripeCustomerId, $paymentTokens, $summary);
            }
        });

        $this->info('Import complete');
        $this->table(array_keys($summary), [$summary]);

        return self::SUCCESS;
    }

    protected function fetchStripeCustomerId(int $wpUserId): ?string
    {
        $metaKeys = [
            'D6sPMX__wcpay_customer_id_live',
            'D6sPMX__stripe_customer_id',
            'wcpay_customer_id',
            'stripe_customer_id',
        ];

        $meta = DB::connection('wordpress')
            ->table('usermeta')
            ->where('user_id', $wpUserId)
            ->whereIn('meta_key', $metaKeys)
            ->get();

        foreach ($metaKeys as $key) {
            $entry = $meta->firstWhere('meta_key', $key);
            if ($entry && $entry->meta_value) {
                return trim($entry->meta_value);
            }
        }

        return null;
    }

    protected function fetchPaymentTokens(int $wpUserId): Collection
    {
        $tokens = DB::connection('wordpress')
            ->table('woocommerce_payment_tokens')
            ->where('user_id', $wpUserId)
            ->whereIn('gateway_id', ['woocommerce_payments', 'stripe'])
            ->orderByDesc('is_default')
            ->get();

        if ($tokens->isEmpty()) {
            return collect();
        }

        $tokenMeta = DB::connection('wordpress')
            ->table('woocommerce_payment_tokenmeta')
            ->whereIn('payment_token_id', $tokens->pluck('token_id'))
            ->get()
            ->groupBy('payment_token_id');

        return $tokens->map(function ($token) use ($tokenMeta) {
            $meta = $tokenMeta->get($token->token_id, collect());

            return [
                'token_id' => $token->token_id,
                'token' => $token->token,
                'is_default' => (bool) $token->is_default,
                'gateway' => $token->gateway_id,
                'type' => $token->type,
                'meta' => $meta->pluck('meta_value', 'meta_key')->toArray(),
            ];
        })->filter(fn ($token) => !empty($token['token']));
    }

    protected function outputDryRunReport(User $user, ?string $stripeCustomerId, Collection $tokens): void
    {
        $this->line("User {$user->id}: {$user->email}");
        $this->line('  Woo ID: ' . $user->woo_customer_id);
        $this->line('  Stripe customer: ' . ($stripeCustomerId ?: 'not found'));
        $this->line('  Payment methods: ' . $tokens->count());

        foreach ($tokens as $token) {
            $meta = $token['meta'];
            $card = Arr::get($meta, '_stripe_card_brand', Arr::get($meta, 'card_type', 'card'));
            $last4 = Arr::get($meta, '_stripe_card_last4', Arr::get($meta, 'last4'));
            $expMonth = Arr::get($meta, '_stripe_card_exp_month');
            $expYear = Arr::get($meta, '_stripe_card_exp_year');

            $this->line(sprintf(
                '    %s (%s) ****%s exp %s/%s %s',
                $token['token'],
                $card,
                $last4,
                $expMonth,
                $expYear,
                $token['is_default'] ? '[default]' : ''
            ));
        }
    }

    protected function persistStripeData(User $user, ?string $stripeCustomerId, Collection $tokens, array &$summary): void
    {
        if ($stripeCustomerId && $user->stripe_customer_id !== $stripeCustomerId) {
            $metadata = $user->stripe_metadata ?? [];
            $metadata['woo_last_imported_at'] = now()->toIso8601String();
            $metadata['woo_gateway'] = 'woocommerce_payments';

            $user->forceFill([
                'stripe_customer_id' => $stripeCustomerId,
                'stripe_metadata' => $metadata,
            ])->save();

            $this->info("Updated Stripe customer for user {$user->id}: {$stripeCustomerId}");
        }

        $defaultStripeId = null;

        foreach ($tokens as $token) {
            $meta = $token['meta'];
            $brand = Arr::get($meta, '_stripe_card_brand', Arr::get($meta, 'card_type'));
            $last4 = Arr::get($meta, '_stripe_card_last4', Arr::get($meta, 'last4'));
            $expMonth = (int) Arr::get($meta, '_stripe_card_exp_month', Arr::get($meta, 'exp_month')) ?: null;
            $expYear = (int) Arr::get($meta, '_stripe_card_exp_year', Arr::get($meta, 'exp_year')) ?: null;
            $funding = Arr::get($meta, '_stripe_card_funding');

            $paymentMethod = UserPaymentMethod::updateOrCreate(
                [
                    'provider' => 'stripe',
                    'provider_payment_method_id' => $token['token'],
                ],
                [
                    'user_id' => $user->id,
                    'provider_customer_id' => $stripeCustomerId,
                    'brand' => $brand,
                    'last4' => $last4,
                    'exp_month' => $expMonth,
                    'exp_year' => $expYear,
                    'funding' => $funding,
                    'is_default' => $token['is_default'],
                    'ready_for_off_session' => true,
                    'status' => 'active',
                    'meta' => $meta,
                ]
            );

            $summary['methods_synced']++;

            if ($token['is_default']) {
                $defaultStripeId = $paymentMethod->provider_payment_method_id;
                $summary['default_methods']++;
            }
        }

        if ($defaultStripeId) {
            $user->forceFill([
                'stripe_default_payment_method_id' => $defaultStripeId,
            ])->save();

            $user->paymentMethods()
                ->where('provider', 'stripe')
                ->where('provider_payment_method_id', '!=', $defaultStripeId)
                ->update(['is_default' => false]);
        }
    }
}
