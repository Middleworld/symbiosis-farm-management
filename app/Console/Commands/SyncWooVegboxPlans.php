<?php

namespace App\Console\Commands;

use App\Models\VegboxPlan;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncWooVegboxPlans extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'vegbox:sync-woo-plans
                            {--dry-run : Output intended changes without persisting}
                            {--product=* : Limit sync to specific WooCommerce product IDs}
                            {--variation=* : Limit sync to specific WooCommerce variation IDs}
                            {--limit= : Process at most N variations (after filters)}';

    /**
     * The console command description.
     */
    protected $description = 'Sync WooCommerce vegbox variations into VegboxPlan records';

    private const TRUTHY_VALUES = ['1', 'true', 'yes', 'on'];

    private const DEFAULT_CURRENCY = 'GBP';

    private const DELIVERY_MAP = [
        'weekly' => 'weekly',
        'weekly box' => 'weekly',
        'fortnightly' => 'bi-weekly',
        'fortnightly box' => 'bi-weekly',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $productFilter = $this->parseNumericOption('product');
        $variationFilter = $this->parseNumericOption('variation');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        $this->info('🔄 Syncing WooCommerce variations into Vegbox plans');

        $productIds = $productFilter->isNotEmpty()
            ? $productFilter
            : $this->fetchVegboxProductIds();

        if ($productIds->isEmpty() && $variationFilter->isEmpty()) {
            $this->warn('No vegbox product meta detected. Syncing all WooCommerce variations (consider using --product for narrower scope).');
        }

        $variations = $this->fetchVariations($productIds, $variationFilter, $limit);

        if ($variations->isEmpty()) {
            $this->warn('No matching WooCommerce variations found. Nothing to sync.');
            return Command::SUCCESS;
        }

        $metaMap = $this->buildMetaMap($variations->pluck('variation_id'));

        $summary = [
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'skipped' => 0,
            'restored' => 0,
        ];

        foreach ($variations as $index => $variation) {
            $meta = $metaMap->get($variation->variation_id, collect());

            $context = [
                'variation_id' => $variation->variation_id,
                'product_id' => $variation->post_parent,
                'product_title' => $variation->product_title,
            ];

            $paymentOption = $this->normalizePaymentOption($meta->get('attribute_pa_payment-option') ?? $meta->get('attribute_payment-option'));

            if (!$paymentOption) {
                $this->warn(sprintf('⏭️  Variation %d skipped: missing payment option', $variation->variation_id));
                $summary['skipped']++;
                continue;
            }

            $deliveryFrequency = $this->normalizeDeliveryFrequency($meta->get('attribute_pa_frequency') ?? $meta->get('attribute_frequency'));
            $price = $this->resolvePrice($meta);

            if ($price === null) {
                $this->warn(sprintf('⏭️  Variation %d skipped: missing price', $variation->variation_id));
                $summary['skipped']++;
                continue;
            }

            $billing = $this->determineBillingSchedule($paymentOption);

            $payload = [
                'translations' => [
                    'name' => ['en' => $this->buildPlanName($variation->product_title, $paymentOption, $deliveryFrequency)],
                    'description' => ['en' => $this->buildPlanDescription($variation->variation_id, $paymentOption, $deliveryFrequency)],
                ],
                'slug' => Str::slug($variation->product_title . '-' . $paymentOption . '-' . $deliveryFrequency) . '-' . $variation->variation_id,
                'is_active' => $variation->variation_status === 'publish',
                'price' => $price,
                'signup_fee' => 0,
                'currency' => self::DEFAULT_CURRENCY,
                'trial_period' => 0,
                'trial_interval' => 'day',
                'invoice_period' => $billing['period'],
                'invoice_interval' => $billing['interval'],
                'grace_period' => 7,
                'grace_interval' => 'day',
                'prorate_day' => null,
                'prorate_period' => null,
                'prorate_extend_due' => null,
                'active_subscribers_limit' => null,
                'sort_order' => $index + 1,
                'box_size' => $this->detectBoxSize($variation->product_title),
                'delivery_frequency' => $deliveryFrequency,
                'max_deliveries_per_month' => $deliveryFrequency === 'bi-weekly' ? 2 : 4,
                'contents_description' => null,
            ];

            $result = $this->upsertPlan($variation->variation_id, $payload, $dryRun);

            if ($result === 'created') {
                $summary['created']++;
            } elseif ($result === 'updated') {
                $summary['updated']++;
            } elseif ($result === 'restored') {
                $summary['restored']++;
            } elseif ($result === 'unchanged') {
                $summary['unchanged']++;
            } else {
                $summary['skipped']++;
            }
        }

        $this->newLine();
        $this->info('📊 Sync summary');
        $this->line(sprintf('  ✅ Created:   %d', $summary['created']));
        $this->line(sprintf('  ♻️  Updated:   %d', $summary['updated']));
        $this->line(sprintf('  🔁 Restored:  %d', $summary['restored']));
        $this->line(sprintf('  💤 Unchanged: %d', $summary['unchanged']));
        $this->line(sprintf('  ⏭️  Skipped:   %d', $summary['skipped']));

        if ($dryRun) {
            $this->comment('Dry run complete. Re-run without --dry-run to persist changes.');
        }

        return Command::SUCCESS;
    }

    private function fetchVegboxProductIds(): Collection
    {
        return DB::connection('wordpress')
            ->table('postmeta')
            ->where('meta_key', '_is_vegbox_subscription')
            ->whereIn(DB::raw('LOWER(meta_value)'), array_map('strtolower', self::TRUTHY_VALUES))
            ->pluck('post_id')
            ->unique();
    }

    private function fetchVariations(Collection $productIds, Collection $variationIds, ?int $limit): Collection
    {
        $query = DB::connection('wordpress')
            ->table('posts as v')
            ->select(
                'v.ID as variation_id',
                'v.post_parent',
                'v.post_status as variation_status',
                'parent.post_title as product_title',
                'parent.post_name as product_slug'
            )
            ->join('posts as parent', 'parent.ID', '=', 'v.post_parent')
            ->where('v.post_type', 'product_variation')
            ->where('v.post_status', 'publish');

        if ($productIds->isNotEmpty()) {
            $query->whereIn('v.post_parent', $productIds);
        }

        if ($variationIds->isNotEmpty()) {
            $query->whereIn('v.ID', $variationIds);
        }

        if ($limit) {
            $query->limit($limit);
        }

        return collect($query->orderBy('v.post_parent')->orderBy('v.ID')->get());
    }

    private function buildMetaMap(Collection $variationIds): Collection
    {
        $keys = [
            '_price',
            '_regular_price',
            '_sale_price',
            'attribute_payment-option',
            'attribute_pa_payment-option',
            'attribute_frequency',
            'attribute_pa_frequency',
        ];

        $rows = DB::connection('wordpress')
            ->table('postmeta')
            ->whereIn('post_id', $variationIds)
            ->whereIn('meta_key', $keys)
            ->get();

        $map = [];

        foreach ($rows as $row) {
            $map[$row->post_id][$row->meta_key] = $row->meta_value;
        }

        return collect($map)->map(fn ($values) => collect($values));
    }

    private function normalizePaymentOption(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = strtolower(trim($value));

        return match ($value) {
            'weekly' => 'weekly',
            'fortnightly' => 'fortnightly',
            'monthly' => 'monthly',
            'annual', 'yearly' => 'annual',
            'quarterly' => 'quarterly',
            default => $value ?: null,
        };
    }

    private function normalizeDeliveryFrequency(?string $value): string
    {
        if (!$value) {
            return 'weekly';
        }

        $value = strtolower(trim($value));

        foreach (self::DELIVERY_MAP as $needle => $frequency) {
            if (str_contains($value, $needle)) {
                return $frequency;
            }
        }

        return 'weekly';
    }

    private function determineBillingSchedule(string $paymentOption): array
    {
        return match ($paymentOption) {
            'weekly' => ['period' => 7, 'interval' => 'day'],
            'fortnightly' => ['period' => 14, 'interval' => 'day'],
            'quarterly' => ['period' => 3, 'interval' => 'month'],
            'annual' => ['period' => 1, 'interval' => 'year'],
            default => ['period' => 1, 'interval' => 'month'],
        };
    }

    private function detectBoxSize(string $productTitle): string
    {
        $title = strtolower($productTitle);

        return match (true) {
            str_contains($title, 'single person') => 'Single Person',
            str_contains($title, "couple") => "Couple's",
            str_contains($title, 'small family') => 'Small Family',
            str_contains($title, 'large family') => 'Large Family',
            default => 'Small Family',
        };
    }

    private function resolvePrice(Collection $meta): ?float
    {
        $candidates = [
            $meta->get('_price'),
            $meta->get('_regular_price'),
            $meta->get('_sale_price'),
        ];

        foreach ($candidates as $value) {
            if ($value !== null && $value !== '') {
                return (float) $value;
            }
        }

        return null;
    }

    private function buildPlanName(string $productTitle, string $paymentOption, string $deliveryFrequency): string
    {
        return sprintf('%s • %s payments • %s deliveries',
            $productTitle,
            ucfirst($paymentOption),
            $deliveryFrequency === 'bi-weekly' ? 'Fortnightly' : 'Weekly'
        );
    }

    private function buildPlanDescription(int $variationId, string $paymentOption, string $deliveryFrequency): string
    {
        return sprintf(
            'Auto-synced from WooCommerce variation #%d (%s payments, %s deliveries).',
            $variationId,
            ucfirst($paymentOption),
            $deliveryFrequency === 'bi-weekly' ? 'fortnightly' : 'weekly'
        );
    }

    private function upsertPlan(int $planId, array $payload, bool $dryRun): string
    {
        /** @var VegboxPlan|null $plan */
        $plan = VegboxPlan::withTrashed()->find($planId);
        $action = 'unchanged';
        $isNew = false;

        if (!$plan) {
            $plan = new VegboxPlan();
            $plan->id = $planId;
            $isNew = true;
        }

        $this->applyTranslations($plan, $payload['translations']);

        $plan->fill(collect($payload)->except('translations')->toArray());

        if ($plan->trashed()) {
            if ($dryRun) {
                $this->line(sprintf('🩺 [DRY] Would restore deleted plan #%d', $planId));
                return 'restored';
            }

            $plan->restore();
            $action = 'restored';
        }

        if ($plan->isDirty()) {
            if ($dryRun) {
                $this->line(sprintf('📝 [DRY] Would %s plan #%d (%s)', $isNew ? 'create' : 'update', $planId, $plan->slug));
                return $isNew ? 'created' : 'updated';
            }

            $plan->save();
            $action = $isNew ? 'created' : ($action === 'restored' ? 'restored' : 'updated');
            $this->line(sprintf('✅ %s plan #%d (%s)', ucfirst($action), $planId, $plan->slug));
            return $action;
        }

        return $action;
    }

    private function applyTranslations(VegboxPlan $plan, array $translations): void
    {
        foreach ($translations as $field => $value) {
            $current = $plan->getTranslations($field);

            if ($current !== $value) {
                $plan->setTranslations($field, $value);
            }
        }
    }

    private function parseNumericOption(string $key): Collection
    {
        return collect($this->option($key))
            ->flatMap(fn ($value) => Str::of($value)->replace(',', ' ')->explode(' '))
            ->filter()
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value > 0)
            ->unique()
            ->values();
    }
}
