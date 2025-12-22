<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HarvestLog;
use App\Models\StockItem;
use App\Services\FarmOSApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FieldKitWebhookController extends Controller
{
    protected FarmOSApi $farmOSApi;

    public function __construct(FarmOSApi $farmOSApi)
    {
        $this->farmOSApi = $farmOSApi;
    }

    /**
     * Handle task completion from Field Kit
     * Called when a task is marked complete via QR scan
     * 
     * POST /api/fieldkit/task-completed
     */
    public function taskCompleted(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|string',
            'task_type' => 'required|string|in:seeding,transplant,harvest,observation,maintenance',
            'plant_asset_id' => 'nullable|string',
            'crop_name' => 'nullable|string',
            'timestamp' => 'required|date',
            'location' => 'nullable|string',
            'notes' => 'nullable|string',
            'quantity' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string',
            'worker_name' => 'nullable|string',
            'weather' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        Log::info('Field Kit task completed', [
            'task_type' => $data['task_type'],
            'task_id' => $data['task_id'],
            'worker' => $data['worker_name'] ?? 'unknown',
            'timestamp' => $data['timestamp']
        ]);

        try {
            // Handle different task types
            switch ($data['task_type']) {
                case 'harvest':
                    $result = $this->processFieldHarvest($data);
                    break;
                    
                case 'seeding':
                case 'transplant':
                    $result = $this->processPlantingTask($data);
                    break;
                    
                case 'observation':
                case 'maintenance':
                    $result = $this->processGeneralTask($data);
                    break;
                    
                default:
                    $result = [
                        'success' => true,
                        'message' => 'Task recorded'
                    ];
            }

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Field Kit task processing failed', [
                'task_id' => $data['task_id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Task processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process a harvest from field
     */
    protected function processFieldHarvest(array $data): array
    {
        if (!isset($data['quantity']) || $data['quantity'] <= 0) {
            return [
                'success' => false,
                'message' => 'Harvest quantity is required and must be greater than 0'
            ];
        }

        // Create harvest log
        $harvestLog = HarvestLog::create([
            'farmos_id' => $data['task_id'],
            'farmos_asset_id' => $data['plant_asset_id'] ?? null,
            'crop_name' => $data['crop_name'] ?? 'Unknown',
            'quantity' => $data['quantity'],
            'units' => $data['unit'] ?? 'kg',
            'harvest_date' => $data['timestamp'],
            'location' => $data['location'] ?? null,
            'notes' => $this->buildNotes($data),
            'status' => 'done',
            'synced_to_stock' => false,
        ]);

        Log::info('Field harvest recorded', [
            'harvest_id' => $harvestLog->id,
            'crop' => $harvestLog->crop_name,
            'quantity' => $harvestLog->quantity,
            'units' => $harvestLog->units
        ]);

        // Stock update will happen via scheduled sync
        return [
            'success' => true,
            'message' => 'Harvest recorded successfully',
            'harvest_id' => $harvestLog->id,
            'note' => 'Stock will be updated within 15 minutes via automated sync'
        ];
    }

    /**
     * Process planting tasks (seeding/transplant)
     */
    protected function processPlantingTask(array $data): array
    {
        // For now, just log it - future integration could create FarmOS logs
        Log::info('Field planting task completed', $data);

        return [
            'success' => true,
            'message' => ucfirst($data['task_type']) . ' task recorded',
            'note' => 'Task logged locally. Sync to FarmOS not yet implemented.'
        ];
    }

    /**
     * Process general tasks (observation/maintenance)
     */
    protected function processGeneralTask(array $data): array
    {
        Log::info('Field general task completed', $data);

        return [
            'success' => true,
            'message' => ucfirst($data['task_type']) . ' recorded'
        ];
    }

    /**
     * Build notes from field data
     */
    protected function buildNotes(array $data): string
    {
        $notes = [];

        if (!empty($data['notes'])) {
            $notes[] = $data['notes'];
        }

        if (!empty($data['worker_name'])) {
            $notes[] = "Worker: {$data['worker_name']}";
        }

        if (!empty($data['weather'])) {
            $notes[] = "Weather: {$data['weather']}";
        }

        $notes[] = "Recorded via Field Kit at " . date('Y-m-d H:i', strtotime($data['timestamp']));

        return implode(' | ', $notes);
    }

    /**
     * Generate QR code for a plant asset/task
     * 
     * POST /api/fieldkit/generate-qr
     */
    public function generateTaskQR(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plant_asset_id' => 'required|string',
            'task_type' => 'required|string|in:seeding,transplant,harvest,observation,maintenance',
            'crop_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Generate Field Kit URL for the task
        $fieldKitUrl = sprintf(
            'https://middleworld.farm/farm/%s?asset=%s&crop=%s',
            $data['task_type'],
            urlencode($data['plant_asset_id']),
            urlencode($data['crop_name'])
        );

        // Generate QR code URL using Google Charts API (free, no API key needed)
        $qrUrl = sprintf(
            'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=%s',
            urlencode($fieldKitUrl)
        );

        // Alternative: QR Server API
        $qrUrlAlt = sprintf(
            'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%s',
            urlencode($fieldKitUrl)
        );

        Log::info('QR code generated', [
            'asset_id' => $data['plant_asset_id'],
            'task_type' => $data['task_type'],
            'crop' => $data['crop_name']
        ]);

        return response()->json([
            'success' => true,
            'qr_url' => $qrUrl,
            'qr_url_alt' => $qrUrlAlt,
            'field_kit_url' => $fieldKitUrl,
            'instructions' => 'Scan this QR code with Field Kit app to access task'
        ]);
    }

    /**
     * Batch generate QR codes for multiple assets
     * 
     * POST /api/fieldkit/batch-generate-qr
     */
    public function batchGenerateQR(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'assets' => 'required|array|min:1|max:50',
            'assets.*.plant_asset_id' => 'required|string',
            'assets.*.task_type' => 'required|string|in:seeding,transplant,harvest,observation,maintenance',
            'assets.*.crop_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $assets = $request->input('assets');
        $results = [];

        foreach ($assets as $asset) {
            $fieldKitUrl = sprintf(
                'https://middleworld.farm/farm/%s?asset=%s&crop=%s',
                $asset['task_type'],
                urlencode($asset['plant_asset_id']),
                urlencode($asset['crop_name'])
            );

            $qrUrl = sprintf(
                'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=%s',
                urlencode($fieldKitUrl)
            );

            $results[] = [
                'plant_asset_id' => $asset['plant_asset_id'],
                'crop_name' => $asset['crop_name'],
                'task_type' => $asset['task_type'],
                'qr_url' => $qrUrl,
                'field_kit_url' => $fieldKitUrl
            ];
        }

        Log::info('Batch QR codes generated', ['count' => count($results)]);

        return response()->json([
            'success' => true,
            'count' => count($results),
            'qr_codes' => $results
        ]);
    }

    /**
     * Get Field Kit sync status
     * 
     * GET /api/fieldkit/sync-status
     */
    public function syncStatus(Request $request)
    {
        $recentHarvests = HarvestLog::where('created_at', '>=', now()->subDays(7))
            ->where('status', 'done')
            ->count();

        $pendingSync = HarvestLog::where('synced_to_stock', false)
            ->where('status', 'done')
            ->count();

        $syncedToStock = HarvestLog::where('synced_to_stock', true)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return response()->json([
            'success' => true,
            'status' => [
                'recent_harvests_7_days' => $recentHarvests,
                'pending_stock_sync' => $pendingSync,
                'synced_to_stock_7_days' => $syncedToStock,
                'last_check' => now()->toIso8601String(),
                'next_scheduled_sync' => 'Every 15 minutes'
            ]
        ]);
    }
}
