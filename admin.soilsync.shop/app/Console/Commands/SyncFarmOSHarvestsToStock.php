<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FarmOSApi;
use App\Models\HarvestLog;
use App\Models\StockItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncFarmOSHarvestsToStock extends Command
{
    protected $signature = 'farmos:sync-harvests-to-stock 
                           {--since= : Sync harvests since date (Y-m-d)}
                           {--dry-run : Show what would be synced without making changes}';
    
    protected $description = 'Sync FarmOS harvest logs to local stock and optionally WooCommerce';

    protected FarmOSApi $farmOSApi;

    public function __construct(FarmOSApi $farmOSApi)
    {
        parent::__construct();
        $this->farmOSApi = $farmOSApi;
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('🌾 Starting FarmOS harvest → stock sync...');
        
        try {
            // 1. Fetch recent harvests from FarmOS
            $since = $this->option('since') 
                ? Carbon::parse($this->option('since'))
                : Carbon::now()->subDays(7);
            
            $this->info("📡 Fetching harvests since {$since->toDateString()}...");
            
            $harvests = $this->farmOSApi->getHarvestLogs($since->toISOString());
            
            if (empty($harvests)) {
                $this->info('✓ No new harvests found.');
                return 0;
            }
            
            $this->info("📊 Found " . count($harvests) . " harvest logs");
            
            $synced = 0;
            $stockUpdated = 0;
            $errors = 0;
            
            DB::beginTransaction();
            
            try {
                foreach ($harvests as $harvestData) {
                    $result = $this->processHarvest($harvestData, $dryRun);
                    
                    if ($result['harvest_synced']) $synced++;
                    if ($result['stock_updated']) $stockUpdated++;
                    if ($result['error']) $errors++;
                }
                
                if (!$dryRun) {
                    DB::commit();
                    $this->info('✓ Database changes committed');
                } else {
                    DB::rollBack();
                    $this->warn('✓ Dry run complete - no changes made');
                }
                
                $this->newLine();
                $this->info("✅ Sync complete!");
                $this->table(
                    ['Metric', 'Count'],
                    [
                        ['Harvests Synced', $synced],
                        ['Stock Items Updated', $stockUpdated],
                        ['Errors', $errors],
                    ]
                );
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Sync failed: ' . $e->getMessage());
            Log::error('FarmOS harvest sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
    
    protected function processHarvest(array $harvestData, bool $dryRun): array
    {
        $result = ['harvest_synced' => false, 'stock_updated' => false, 'error' => false];
        
        try {
            $attributes = $harvestData['attributes'] ?? [];
            $farmosId = $harvestData['id'] ?? null;
            
            if (!$farmosId) {
                $this->warn("  ⚠ Skipping harvest with no ID");
                $result['error'] = true;
                return $result;
            }
            
            // Extract harvest data
            $cropName = $this->extractCropName($harvestData);
            $quantity = $this->extractQuantity($harvestData);
            $unit = $this->extractUnit($harvestData);
            $harvestDate = isset($attributes['timestamp']) 
                ? Carbon::parse($attributes['timestamp']) 
                : now();
            
            if (!$cropName || $quantity <= 0) {
                $this->warn("  ⚠ Skipping invalid harvest: {$cropName} - {$quantity} {$unit}");
                $result['error'] = true;
                return $result;
            }
            
            $this->line("  Processing: {$cropName} - {$quantity} {$unit}");
            
            if ($dryRun) {
                $this->line("    [DRY RUN] Would sync harvest and update stock");
                return ['harvest_synced' => true, 'stock_updated' => true, 'error' => false];
            }
            
            // Update or create local harvest log
            $harvestLog = HarvestLog::updateOrCreate(
                ['farmos_id' => $farmosId],
                [
                    'farmos_asset_id' => $this->extractAssetId($harvestData),
                    'crop_name' => $cropName,
                    'crop_type' => $this->extractCropType($harvestData),
                    'quantity' => $quantity,
                    'units' => $unit,
                    'harvest_date' => $harvestDate,
                    'location' => $this->extractLocation($harvestData),
                    'notes' => $attributes['notes']['value'] ?? '',
                    'status' => $attributes['status'] ?? 'done',
                    'farmos_data' => $harvestData,
                ]
            );
            
            $result['harvest_synced'] = true;
            $this->line("    ✓ Harvest log saved (ID: {$harvestLog->id})");
            
            // Update local stock if not already synced
            if (!$harvestLog->synced_to_stock) {
                $stockItem = StockItem::firstOrCreate(
                    ['name' => $cropName],
                    [
                        'crop_type' => $this->extractCropType($harvestData),
                        'units' => $unit,
                        'current_stock' => 0,
                        'reserved_stock' => 0,
                        'available_stock' => 0,
                        'minimum_stock' => 0,
                        'is_active' => true,
                        'track_stock' => true,
                    ]
                );
                
                // Add quantity to stock
                $stockItem->increment('current_stock', $quantity);
                
                // Recalculate available stock
                $availableStock = $stockItem->current_stock - ($stockItem->reserved_stock ?? 0);
                $stockItem->update([
                    'available_stock' => max(0, $availableStock),
                    'last_harvest_date' => $harvestDate
                ]);
                
                $harvestLog->update(['synced_to_stock' => true]);
                $result['stock_updated'] = true;
                
                $this->line("    ✓ Stock updated: {$stockItem->current_stock} {$unit} (Available: {$stockItem->available_stock})");
            } else {
                $this->line("    ⊝ Already synced to stock");
            }
            
        } catch (\Exception $e) {
            $this->error("    ✗ Error: {$e->getMessage()}");
            Log::error('Harvest processing error', [
                'harvest_id' => $farmosId ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            $result['error'] = true;
        }
        
        return $result;
    }
    
    protected function extractCropName(array $harvestData): ?string
    {
        // Try to get from asset relationship
        if (isset($harvestData['relationships']['asset']['data'][0]['meta']['label'])) {
            return $harvestData['relationships']['asset']['data'][0]['meta']['label'];
        }
        
        // Try from attributes
        if (isset($harvestData['attributes']['name'])) {
            return $harvestData['attributes']['name'];
        }
        
        // Try from notes
        if (isset($harvestData['attributes']['notes']['value'])) {
            $notes = $harvestData['attributes']['notes']['value'];
            // Try to extract crop name from notes (basic pattern)
            if (preg_match('/^([A-Za-z\s]+)/', $notes, $matches)) {
                return trim($matches[1]);
            }
        }
        
        return null;
    }
    
    protected function extractQuantity(array $harvestData): float
    {
        // Try to get from quantity field
        if (isset($harvestData['relationships']['quantity']['data'])) {
            foreach ($harvestData['relationships']['quantity']['data'] as $qty) {
                if (isset($qty['meta']['value'])) {
                    return (float) $qty['meta']['value'];
                }
            }
        }
        
        // Try from attributes
        if (isset($harvestData['attributes']['quantity']['value'])) {
            return (float) $harvestData['attributes']['quantity']['value'];
        }
        
        return 0.0;
    }
    
    protected function extractUnit(array $harvestData): string
    {
        // Try to get from quantity field
        if (isset($harvestData['relationships']['quantity']['data'])) {
            foreach ($harvestData['relationships']['quantity']['data'] as $qty) {
                if (isset($qty['meta']['units'])) {
                    return $qty['meta']['units'];
                }
            }
        }
        
        // Try from attributes
        if (isset($harvestData['attributes']['quantity']['units'])) {
            return $harvestData['attributes']['quantity']['units'];
        }
        
        return 'kg'; // Default
    }
    
    protected function extractCropType(array $harvestData): ?string
    {
        // Try to get from categories/taxonomy
        if (isset($harvestData['relationships']['category']['data'][0]['meta']['label'])) {
            return $harvestData['relationships']['category']['data'][0]['meta']['label'];
        }
        
        return null;
    }
    
    protected function extractLocation(array $harvestData): ?string
    {
        // Try to get from location relationship
        if (isset($harvestData['relationships']['location']['data'][0]['meta']['label'])) {
            return $harvestData['relationships']['location']['data'][0]['meta']['label'];
        }
        
        return null;
    }
    
    protected function extractAssetId(array $harvestData): ?string
    {
        // Try to get from asset relationship
        if (isset($harvestData['relationships']['asset']['data'][0]['id'])) {
            return $harvestData['relationships']['asset']['data'][0]['id'];
        }
        
        return null;
    }
}
