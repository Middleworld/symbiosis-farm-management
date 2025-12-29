<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * Reliable service for creating FarmOS logs
 * Wraps FarmOSApi with retry logic, validation, and error handling
 */
class FarmOSLogService
{
    protected FarmOSApi $farmOSApi;
    protected int $maxRetries = 3;
    protected int $retryDelay = 2; // seconds

    public function __construct(FarmOSApi $farmOSApi)
    {
        $this->farmOSApi = $farmOSApi;
    }

    /**
     * Create seeding log with retry logic
     */
    public function createSeedingLog(array $data): array
    {
        $validated = $this->validateSeedingData($data);
        
        return $this->executeWithRetry(function() use ($validated) {
            return $this->farmOSApi->createSeedingLog($validated);
        }, 'seeding', $validated);
    }

    /**
     * Create transplanting log with retry logic
     */
    public function createTransplantingLog(array $data): array
    {
        $validated = $this->validateTransplantingData($data);
        
        return $this->executeWithRetry(function() use ($validated) {
            return $this->farmOSApi->createTransplantingLog($validated);
        }, 'transplanting', $validated);
    }

    /**
     * Create harvest log with retry logic
     */
    public function createHarvestLog(array $data): array
    {
        $validated = $this->validateHarvestData($data);
        
        return $this->executeWithRetry(function() use ($validated) {
            return $this->farmOSApi->createHarvestLog($validated);
        }, 'harvest', $validated);
    }

    /**
     * Create planting asset with retry logic
     */
    public function createPlantingAsset(array $data, ?string $locationId = null): array
    {
        $validated = $this->validatePlantingAssetData($data);
        
        return $this->executeWithRetry(function() use ($validated, $locationId) {
            return $this->farmOSApi->createPlantingAsset($validated, $locationId);
        }, 'planting_asset', $validated);
    }

    /**
     * Create complete succession (asset + seeding log)
     */
    public function createSuccession(array $successionData): array
    {
        try {
            Log::info('Creating succession in FarmOS', [
                'crop' => $successionData['crop_name'] ?? 'unknown',
                'variety' => $successionData['variety_name'] ?? 'unknown'
            ]);

            // Step 1: Create planting asset
            $assetResult = $this->createPlantingAsset([
                'name' => $this->buildPlantingName($successionData),
                'crop_name' => $successionData['crop_name'],
                'variety_name' => $successionData['variety_name'] ?? null,
                'status' => 'active',
                'notes' => $successionData['notes'] ?? ''
            ], $successionData['location_id'] ?? null);

            if (!$assetResult['success']) {
                throw new Exception('Failed to create planting asset: ' . ($assetResult['error'] ?? 'Unknown error'));
            }

            $plantingId = $assetResult['data']['id'] ?? null;
            if (!$plantingId) {
                throw new Exception('Planting asset created but no ID returned');
            }

            // Step 2: Create seeding log
            $logResult = $this->createSeedingLog([
                'crop_name' => $successionData['crop_name'],
                'variety_name' => $successionData['variety_name'] ?? null,
                'planting_id' => $plantingId,
                'timestamp' => $successionData['seeding_date'] ?? now()->toIso8601String(),
                'quantity' => $successionData['quantity'] ?? null,
                'quantity_unit' => $successionData['quantity_unit'] ?? 'seeds',
                'location_id' => $successionData['location_id'] ?? null,
                'notes' => $successionData['notes'] ?? '',
                'status' => 'done'
            ]);

            if (!$logResult['success']) {
                Log::warning('Seeding log creation failed but asset exists', [
                    'planting_id' => $plantingId,
                    'error' => $logResult['error'] ?? 'Unknown'
                ]);
            }

            return [
                'success' => true,
                'planting_id' => $plantingId,
                'seeding_log_id' => $logResult['data']['id'] ?? null,
                'asset_url' => $assetResult['url'] ?? null,
                'message' => 'Succession created successfully'
            ];

        } catch (Exception $e) {
            Log::error('Succession creation failed', [
                'error' => $e->getMessage(),
                'data' => $successionData
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Execute API call with retry logic
     */
    protected function executeWithRetry(callable $operation, string $logType, array $data): array
    {
        $attempt = 0;
        $lastError = null;

        while ($attempt < $this->maxRetries) {
            try {
                $attempt++;
                
                Log::info("FarmOS {$logType} log creation attempt {$attempt}/{$this->maxRetries}");
                
                $result = $operation();
                
                if ($result && isset($result['success']) && $result['success']) {
                    Log::info("FarmOS {$logType} log created successfully", [
                        'id' => $result['data']['id'] ?? null,
                        'attempt' => $attempt
                    ]);
                    return $result;
                }

                $lastError = $result['error'] ?? 'Unknown error';
                Log::warning("FarmOS {$logType} log creation failed", [
                    'attempt' => $attempt,
                    'error' => $lastError
                ]);

            } catch (Exception $e) {
                $lastError = $e->getMessage();
                Log::warning("FarmOS {$logType} log creation exception", [
                    'attempt' => $attempt,
                    'error' => $lastError,
                    'trace' => $e->getTraceAsString()
                ]);
            }

            // Wait before retry (exponential backoff)
            if ($attempt < $this->maxRetries) {
                $delay = $this->retryDelay * $attempt;
                Log::info("Waiting {$delay}s before retry...");
                sleep($delay);
            }
        }

        // All retries failed
        return [
            'success' => false,
            'error' => "Failed after {$this->maxRetries} attempts: {$lastError}",
            'attempts' => $attempt
        ];
    }

    /**
     * Validate seeding log data
     */
    protected function validateSeedingData(array $data): array
    {
        $required = ['crop_name', 'timestamp'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }

        return array_merge([
            'status' => 'done',
            'notes' => '',
            'quantity_unit' => 'seeds'
        ], $data);
    }

    /**
     * Validate transplanting log data
     */
    protected function validateTransplantingData(array $data): array
    {
        $required = ['crop_name', 'timestamp'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }

        return array_merge([
            'status' => 'done',
            'notes' => '',
            'quantity_unit' => 'plants',
            'is_movement' => true
        ], $data);
    }

    /**
     * Validate harvest log data
     */
    protected function validateHarvestData(array $data): array
    {
        $required = ['crop_name', 'timestamp'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }

        return array_merge([
            'status' => 'done',
            'notes' => '',
            'quantity_unit' => 'kg'
        ], $data);
    }

    /**
     * Validate planting asset data
     */
    protected function validatePlantingAssetData(array $data): array
    {
        $required = ['name', 'crop_name'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }

        return array_merge([
            'status' => 'active',
            'notes' => ''
        ], $data);
    }

    /**
     * Build planting name from succession data
     */
    protected function buildPlantingName(array $data): string
    {
        $name = $data['crop_name'];
        
        if (!empty($data['variety_name']) && $data['variety_name'] !== 'Generic') {
            $name .= ' - ' . $data['variety_name'];
        }

        if (!empty($data['succession_number'])) {
            $name .= ' (S' . $data['succession_number'] . ')';
        }

        if (!empty($data['season'])) {
            $name .= ' - ' . $data['season'];
        }

        return $name;
    }

    /**
     * Check if FarmOS API is available
     */
    public function healthCheck(): array
    {
        try {
            $isAuth = $this->farmOSApi->isAuthenticated();
            
            if (!$isAuth) {
                return [
                    'healthy' => false,
                    'message' => 'Not authenticated with FarmOS'
                ];
            }

            return [
                'healthy' => true,
                'message' => 'FarmOS API is accessible'
            ];

        } catch (Exception $e) {
            return [
                'healthy' => false,
                'message' => 'FarmOS API error: ' . $e->getMessage()
            ];
        }
    }
}
