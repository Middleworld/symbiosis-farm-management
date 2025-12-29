<?php

namespace App\Console\Commands;

use App\Models\VegboxSubscription;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SyncWooVegboxSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'vegbox:sync-woo-subscriptions {--dry-run : Show changes without persisting}';

    /**
     * The console command description.
     */
    protected $description = 'Align vegbox subscription records with current WooCommerce subscription statuses';

    private const META_KEYS = [
        '_schedule_next_payment',
        '_schedule_cancelled',
        '_schedule_end',
        '_billing_period',
        '_billing_interval',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $subscriptions = VegboxSubscription::query()
            ->whereNotNull('woo_subscription_id')
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->info('No WooCommerce-linked subscriptions found.');
            return Command::SUCCESS;
        }

        $this->info(sprintf('Evaluating %d vegbox subscriptions linked to WooCommerce…', $subscriptions->count()));

        $wooPosts = $this->fetchWooPosts($subscriptions->pluck('woo_subscription_id'));
        $metaMap = $this->buildMetaMap($subscriptions->pluck('woo_subscription_id'));

        $summary = [
            'synced' => 0,
            'skipped' => 0,
            'missing' => 0,
        ];

        foreach ($subscriptions as $subscription) {
            $wooId = $subscription->woo_subscription_id;
            $wooPost = $wooPosts->get($wooId);

            if (!$wooPost) {
                $this->warn(sprintf('⚠️  Woo subscription #%d not found (vegbox #%d)', $wooId, $subscription->id));
                $summary['missing']++;
                continue;
            }

            $meta = $metaMap->get($wooId, collect());

            $payload = $this->buildUpdatePayload($wooPost->post_status, $meta, $wooPost->post_modified);

            if ($this->hasDifferences($subscription, $payload)) {
                if ($dryRun) {
                    $this->line(sprintf('📝 [DRY] Would sync vegbox #%d (Woo #%d) status=%s next_billing=%s',
                        $subscription->id,
                        $wooId,
                        $wooPost->post_status,
                        $payload['next_billing_at'] ?? 'null'
                    ));
                } else {
                    $subscription->update($payload);
                    $this->line(sprintf('✅ Synced vegbox #%d (Woo #%d) to %s',
                        $subscription->id,
                        $wooId,
                        $wooPost->post_status
                    ));
                }

                $summary['synced']++;
            } else {
                $summary['skipped']++;
            }
        }

        $this->newLine();
        $this->info('📊 Sync summary');
        $this->line(sprintf('  🔄 Updated: %d', $summary['synced']));
        $this->line(sprintf('  ⏭️  Unchanged: %d', $summary['skipped']));
        $this->line(sprintf('  ❌ Missing Woo rows: %d', $summary['missing']));

        if ($dryRun) {
            $this->comment('Dry run complete. Re-run without --dry-run to persist changes.');
        }

        return Command::SUCCESS;
    }

    private function fetchWooPosts(Collection $ids): Collection
    {
        if ($ids->isEmpty()) {
            return collect();
        }

        return DB::connection('wordpress')
            ->table('posts')
            ->where('post_type', 'shop_subscription')
            ->whereIn('ID', $ids)
            ->select('ID', 'post_status', 'post_modified')
            ->get()
            ->keyBy('ID');
    }

    private function buildMetaMap(Collection $ids): Collection
    {
        if ($ids->isEmpty()) {
            return collect();
        }

        $rows = DB::connection('wordpress')
            ->table('postmeta')
            ->whereIn('post_id', $ids)
            ->whereIn('meta_key', self::META_KEYS)
            ->get();

        $map = [];

        foreach ($rows as $row) {
            if (!isset($map[$row->post_id])) {
                $map[$row->post_id] = [];
            }

            $map[$row->post_id][$row->meta_key] = $row->meta_value;
        }

        return collect($map)->map(fn ($values) => collect($values));
    }

    private function buildUpdatePayload(string $status, Collection $meta, ?string $modified): array
    {
        $nextPayment = $this->parseDateValue($meta->get('_schedule_next_payment'));
        $cancelledAt = $this->parseDateValue($meta->get('_schedule_cancelled'));
        $endedAt = $this->parseDateValue($meta->get('_schedule_end'));
        $modifiedAt = $this->parseDateValue($modified);

        $payload = [
            'next_billing_at' => $nextPayment,
            'billing_period' => $meta->get('_billing_period') ?? 'month',
            'billing_frequency' => $meta->get('_billing_interval') ?? '1',
        ];

        switch ($status) {
            case 'wc-active':
                $payload['canceled_at'] = null;
                $payload['ends_at'] = null;
                $payload['cancels_at'] = null;
                break;
            case 'wc-pending-cancel':
                $payload['cancels_at'] = $cancelledAt ?? $endedAt ?? $modifiedAt;
                $payload['canceled_at'] = null;
                $payload['ends_at'] = null;
                break;
            default:
                $payload['canceled_at'] = $cancelledAt ?? $modifiedAt ?? now();
                $payload['ends_at'] = $endedAt ?? $payload['canceled_at'];
                $payload['cancels_at'] = null;
                break;
        }

        return $payload;
    }

    private function hasDifferences(VegboxSubscription $subscription, array $payload): bool
    {
        foreach ($payload as $key => $value) {
            $current = $subscription->{$key};

            if ($current instanceof Carbon) {
                if ($value instanceof Carbon && !$current->equalTo($value)) {
                    return true;
                }

                if ($value === null) {
                    return true;
                }
            } elseif ($value instanceof Carbon) {
                if ($current === null) {
                    return true;
                }

                if (!Carbon::parse($current)->equalTo($value)) {
                    return true;
                }
            } elseif ($current != $value) {
                return true;
            }
        }

        return false;
    }

    private function parseDateValue(?string $value): ?Carbon
    {
        if (!$value || $value === '0') {
            return null;
        }

        try {
            return Carbon::parse($value, 'UTC')->setTimezone(config('app.timezone'));
        } catch (\Exception $e) {
            return null;
        }
    }
}
