<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class BluetoothScaleService
{
    /**
     * Supported scale manufacturers and their Bluetooth profiles
     */
    const SUPPORTED_SCALES = [
        'a_and_d' => [
            'name' => 'A&D Company',
            'service_uuid' => '00001101-0000-1000-8000-00805f9b34fb', // Serial Port Profile
            'characteristic_uuid' => '00001101-0000-1000-8000-00805f9b34fb',
            'name_prefixes' => ['A&D', 'AND', 'AD-', 'EW-', 'GF-', 'GX-', 'HD-'],
            'data_format' => 'ascii',
            'commands' => [
                'tare' => 'T',
                'zero' => 'Z',
                'print' => 'P'
            ]
        ],
        'ohaus' => [
            'name' => 'Ohaus',
            'service_uuid' => '000018f0-0000-1000-8000-00805f9b34fb', // Ohaus custom service
            'characteristic_uuid' => '00002af0-0000-1000-8000-00805f9b34fb',
            'name_prefixes' => ['Ohaus', 'OH-', 'Valor', 'Defender'],
            'data_format' => 'binary',
            'commands' => [
                'tare' => 'T',
                'zero' => 'Z',
                'print' => 'P'
            ]
        ],
        'sartorius' => [
            'name' => 'Sartorius',
            'service_uuid' => '00001101-0000-1000-8000-00805f9b34fb',
            'characteristic_uuid' => '00001101-0000-1000-8000-00805f9b34fb',
            'name_prefixes' => ['Sartorius', 'SAR-', 'CPA', 'Secura', 'Quintix'],
            'data_format' => 'ascii',
            'commands' => [
                'tare' => 'T',
                'zero' => 'Z',
                'print' => 'PRT'
            ]
        ],
        'generic' => [
            'name' => 'Generic Bluetooth Scale',
            'service_uuid' => '00001101-0000-1000-8000-00805f9b34fb', // SPP fallback
            'characteristic_uuid' => '00001101-0000-1000-8000-00805f9b34fb',
            'name_prefixes' => ['Scale', 'Balance', 'Weight'],
            'data_format' => 'ascii',
            'commands' => [
                'tare' => 'T',
                'zero' => 'Z',
                'print' => 'P'
            ]
        ]
    ];

    /**
     * Weight units and conversion factors
     */
    const WEIGHT_UNITS = [
        'kg' => ['name' => 'Kilograms', 'factor' => 1.0, 'symbol' => 'kg'],
        'g' => ['name' => 'Grams', 'factor' => 1000.0, 'symbol' => 'g'],
        'lbs' => ['name' => 'Pounds', 'factor' => 2.20462, 'symbol' => 'lbs'],
        'oz' => ['name' => 'Ounces', 'factor' => 35.274, 'symbol' => 'oz'],
    ];

    protected $connectedDevice = null;
    protected $currentScale = null;
    protected $weightUnit = 'kg';
    protected $tareWeight = 0.0;

    /**
     * Scan for available Bluetooth scales
     */
    public function scanForScales()
    {
        try {
            // In a real implementation, this would use Web Bluetooth API
            // For now, return supported scale types
            $scales = [];
            foreach (self::SUPPORTED_SCALES as $key => $scale) {
                $scales[] = [
                    'id' => $key,
                    'name' => $scale['name'],
                    'supported' => true,
                    'requires_pairing' => true
                ];
            }

            return [
                'success' => true,
                'scales' => $scales,
                'message' => 'Scale types available for connection'
            ];
        } catch (Exception $e) {
            Log::error('Scale scanning error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Connect to a Bluetooth scale
     */
    public function connectToScale($scaleType = null, $deviceName = null)
    {
        try {
            if (!$scaleType || !isset(self::SUPPORTED_SCALES[$scaleType])) {
                $scaleType = 'generic'; // Default fallback
            }

            $this->currentScale = self::SUPPORTED_SCALES[$scaleType];

            // In a real implementation, this would:
            // 1. Use Web Bluetooth API to request device
            // 2. Connect to GATT server
            // 3. Discover services and characteristics
            // 4. Set up notifications for weight data

            // Simulate connection
            $this->connectedDevice = [
                'name' => $deviceName ?: $this->currentScale['name'] . ' Scale',
                'type' => $scaleType,
                'connected_at' => now(),
                'status' => 'connected'
            ];

            // Store connection in cache for session persistence
            Cache::put('bluetooth_scale_connected', $this->connectedDevice, now()->addHours(8));

            Log::info('Bluetooth scale connected', [
                'scale_type' => $scaleType,
                'device_name' => $this->connectedDevice['name']
            ]);

            return [
                'success' => true,
                'device' => $this->connectedDevice,
                'scale_info' => $this->currentScale,
                'message' => 'Scale connected successfully'
            ];

        } catch (Exception $e) {
            Log::error('Scale connection error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Disconnect from scale
     */
    public function disconnectScale()
    {
        try {
            if ($this->connectedDevice) {
                Log::info('Bluetooth scale disconnected', [
                    'device_name' => $this->connectedDevice['name']
                ]);

                $this->connectedDevice = null;
                $this->currentScale = null;
                $this->tareWeight = 0.0;

                Cache::forget('bluetooth_scale_connected');
            }

            return [
                'success' => true,
                'message' => 'Scale disconnected'
            ];

        } catch (Exception $e) {
            Log::error('Scale disconnection error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get current scale connection status
     */
    public function getScaleStatus()
    {
        $cached = Cache::get('bluetooth_scale_connected');

        if ($cached && !$this->connectedDevice) {
            $this->connectedDevice = $cached;
            $this->currentScale = self::SUPPORTED_SCALES[$cached['type']] ?? self::SUPPORTED_SCALES['generic'];
        }

        return [
            'connected' => $this->connectedDevice !== null,
            'device' => $this->connectedDevice,
            'scale_info' => $this->currentScale,
            'weight_unit' => $this->weightUnit,
            'tare_weight' => $this->tareWeight
        ];
    }

    /**
     * Read current weight from scale
     */
    public function readWeight()
    {
        try {
            if (!$this->connectedDevice) {
                return [
                    'success' => false,
                    'error' => 'No scale connected'
                ];
            }

            // In a real implementation, this would read from the Bluetooth characteristic
            // For simulation, return a mock weight
            $rawWeight = $this->simulateWeightReading();

            $netWeight = $rawWeight - $this->tareWeight;
            $netWeight = max(0, $netWeight); // Ensure non-negative

            return [
                'success' => true,
                'weight' => $netWeight,
                'unit' => $this->weightUnit,
                'raw_weight' => $rawWeight,
                'tare_weight' => $this->tareWeight,
                'formatted_weight' => $this->formatWeight($netWeight),
                'timestamp' => now()->toISOString()
            ];

        } catch (Exception $e) {
            Log::error('Weight reading error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Set tare weight (container weight)
     */
    public function setTare($tareWeight = null)
    {
        try {
            if ($tareWeight === null) {
                // Auto-tare: use current weight as tare
                $reading = $this->readWeight();
                if ($reading['success']) {
                    $this->tareWeight = $reading['raw_weight'];
                }
            } else {
                $this->tareWeight = floatval($tareWeight);
            }

            // Send tare command to scale if supported
            if ($this->currentScale && isset($this->currentScale['commands']['tare'])) {
                $this->sendCommandToScale($this->currentScale['commands']['tare']);
            }

            return [
                'success' => true,
                'tare_weight' => $this->tareWeight,
                'message' => 'Tare weight set to ' . $this->formatWeight($this->tareWeight)
            ];

        } catch (Exception $e) {
            Log::error('Tare setting error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Zero/calibrate the scale
     */
    public function zeroScale()
    {
        try {
            $this->tareWeight = 0.0;

            // Send zero command to scale if supported
            if ($this->currentScale && isset($this->currentScale['commands']['zero'])) {
                $this->sendCommandToScale($this->currentScale['commands']['zero']);
            }

            return [
                'success' => true,
                'message' => 'Scale zeroed'
            ];

        } catch (Exception $e) {
            Log::error('Scale zeroing error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Set weight unit
     */
    public function setWeightUnit($unit)
    {
        if (!isset(self::WEIGHT_UNITS[$unit])) {
            return [
                'success' => false,
                'error' => 'Invalid weight unit'
            ];
        }

        $this->weightUnit = $unit;

        return [
            'success' => true,
            'unit' => $unit,
            'unit_info' => self::WEIGHT_UNITS[$unit]
        ];
    }

    /**
     * Get supported weight units
     */
    public function getWeightUnits()
    {
        return self::WEIGHT_UNITS;
    }

    /**
     * Calculate price based on weight and price per unit
     */
    public function calculatePrice($weight, $pricePerKg, $unit = null)
    {
        $unit = $unit ?: $this->weightUnit;

        // Convert weight to kg for calculation
        $weightInKg = $this->convertToKg($weight, $unit);

        $totalPrice = $weightInKg * $pricePerKg;

        return [
            'weight' => $weight,
            'weight_unit' => $unit,
            'weight_kg' => $weightInKg,
            'price_per_kg' => $pricePerKg,
            'total_price' => round($totalPrice, 2),
            'formatted_price' => '£' . number_format($totalPrice, 2)
        ];
    }

    /**
     * Convert weight to kilograms
     */
    private function convertToKg($weight, $fromUnit)
    {
        if ($fromUnit === 'kg') {
            return $weight;
        }

        $factor = self::WEIGHT_UNITS[$fromUnit]['factor'] ?? 1.0;
        return $weight / $factor;
    }

    /**
     * Format weight for display
     */
    private function formatWeight($weight)
    {
        $unit = self::WEIGHT_UNITS[$this->weightUnit]['symbol'] ?? $this->weightUnit;
        return number_format($weight, 3) . ' ' . $unit;
    }

    /**
     * Send command to scale (when hardware integration is available)
     */
    private function sendCommandToScale($command)
    {
        // In a real implementation, this would send the command via Bluetooth
        Log::info('Scale command sent', [
            'command' => $command,
            'scale_type' => $this->currentScale['name'] ?? 'Unknown'
        ]);
    }

    /**
     * Simulate weight reading for development/testing
     */
    private function simulateWeightReading()
    {
        // Simulate realistic weight variations
        $baseWeight = 2.5; // kg
        $variation = (mt_rand(-50, 50) / 1000); // ±0.05 kg variation
        return max(0, $baseWeight + $variation);
    }

    /**
     * Get scale configuration for frontend
     */
    public function getScaleConfig()
    {
        return [
            'supported_scales' => collect(self::SUPPORTED_SCALES)->map(function ($scale, $key) {
                return [
                    'id' => $key,
                    'name' => $scale['name'],
                    'name_prefixes' => $scale['name_prefixes']
                ];
            })->values(),
            'weight_units' => self::WEIGHT_UNITS,
            'default_unit' => $this->weightUnit
        ];
    }
}