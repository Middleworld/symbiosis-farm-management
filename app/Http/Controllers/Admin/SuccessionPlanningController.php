<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FarmOSApi;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SuccessionPlanningController extends Controller
{
    protected $farmOSApi;
    protected $holisticAI;

    public function __construct(FarmOSApi $farmOSApi)
    {
        $this->farmOSApi = $farmOSApi;
        $this->holisticAI = null; // Temporarily disable until service is stable
    }

    /**
     * Display the succession planning interface
     */
    public function index()
    {
        // Auto-wake AI service on page load to avoid cold start delays (but don't fail if it errors)
        try {
            $this->wakeUpAIService();
        } catch (\Exception $e) {
            // Don't let AI wake-up failures break the page load
            Log::debug('AI wake-up failed during page load: ' . $e->getMessage());
        }
        
        try {
            // Get available crop types and varieties from farmOS
            $cropData = $this->farmOSApi->getAvailableCropTypes();
            $geometryAssets = $this->farmOSApi->getGeometryAssets();
            $availableBeds = $this->extractAvailableBeds($geometryAssets);
            
            // Crop timing presets for common market garden crops
            $cropPresets = $this->getCropTimingPresets();
            
            return view('admin.farmos.succession-planning', compact(
                'cropData',
                'availableBeds', 
                'cropPresets'
            ));
            
        } catch (\Exception $e) {
            Log::error('Failed to load succession planning: ' . $e->getMessage());
            
            // Fallback data for demo
            $cropData = [
                'types' => [
                    ['id' => 'lettuce', 'name' => 'lettuce', 'label' => 'Lettuce'],
                    ['id' => 'carrot', 'name' => 'carrot', 'label' => 'Carrot'],
                    ['id' => 'radish', 'name' => 'radish', 'label' => 'Radish'],
                    ['id' => 'spinach', 'name' => 'spinach', 'label' => 'Spinach'],
                    ['id' => 'kale', 'name' => 'kale', 'label' => 'Kale'],
                    ['id' => 'arugula', 'name' => 'arugula', 'label' => 'Arugula'],
                    ['id' => 'beets', 'name' => 'beets', 'label' => 'Beets']
                ],
                'varieties' => []
            ];
            $availableBeds = $this->getFallbackBeds();
            $cropPresets = $this->getCropTimingPresets();
            
            return view('admin.farmos.succession-planning', compact(
                'cropData',
                'availableBeds',
                'cropPresets'
            ));
        }
    }

    /**
     * Generate succession plan based on user input
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'crop_type' => 'required|string',
            'variety' => 'nullable|string',
            'succession_count' => 'required|integer|min:1|max:50',
            'interval_days' => 'required|integer|min:1|max:365',
            'first_seeding_date' => 'required|date',
            'seeding_to_transplant_days' => 'nullable|integer|min:0|max:180',
            'transplant_to_harvest_days' => 'required|integer|min:1|max:365',
            'harvest_duration_days' => 'required|integer|min:1|max:90',
            'beds_per_planting' => 'required|integer|min:1|max:10',
            'auto_assign_beds' => 'boolean',
            'selected_beds' => 'nullable|array',
            'notes' => 'nullable|string|max:1000',
            'direct_sow' => 'boolean'
        ]);

        try {
            // Get existing farmOS crop plans to check for conflicts
            $existingPlans = $this->farmOSApi->getCropPlanningData();
            $cropPresets = $this->getCropTimingPresets();
            
            // Generate succession plan with AI assistance
            $successionPlan = $this->generateSuccessionPlan($validated, $existingPlans, $cropPresets);
            
            // Check for bed conflicts and find alternatives
            $planWithBeds = $this->assignBedsWithConflictResolution($successionPlan);
            
            // Use AI to optimize the plan
            $optimizedPlan = $this->optimizePlanWithAI($planWithBeds, $validated);
            
            return response()->json([
                'success' => true,
                'plan' => $optimizedPlan,
                'message' => 'Succession plan generated successfully with AI optimization',
                'conflicts_resolved' => $optimizedPlan['conflicts_resolved'] ?? 0
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to generate succession plan: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate succession plan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create farmOS logs for the succession plan via API
     * IMPORTANT: This admin is a CLIENT ONLY - all data goes to farmOS via API
     * Our planting chart will then read this data back from farmOS
     */
    public function createLogs(Request $request)
    {
        $validated = $request->validate([
            'plan' => 'required|array',
            'confirm' => 'required|boolean'
        ]);

        if (!$validated['confirm']) {
            return response()->json([
                'success' => false,
                'message' => 'Plan creation not confirmed'
            ], 400);
        }

        try {
            $createdLogs = [];
            $errors = [];
            $farmOSPlans = []; // Track what we send to farmOS

            foreach ($validated['plan']['plantings'] as $planting) {
                try {
                    // CRITICAL: All data goes to farmOS via API calls
                    // We store NOTHING locally - farmOS is the master database
                    
                    // Create seeding plan in farmOS
                    if (!empty($planting['seeding_date'])) {
                        $seedingType = ($planting['direct_sow'] ?? false) ? 'direct_seed' : 'seed';
                        $seedingNote = ($planting['direct_sow'] ?? false) ? 'Direct Sow' : 'Seeding';
                        
                        $seedingPlan = $this->farmOSApi->createCropPlan([
                            'type' => $seedingType,
                            'crop' => [
                                'name' => $planting['crop'],
                                'variety' => $planting['variety'] ?? ''
                            ],
                            'location' => $planting['bed_id'],
                            'timestamp' => $planting['seeding_date'],
                            'notes' => "Succession #{$planting['sequence']}: {$seedingNote} - " . ($planting['notes'] ?? ''),
                            'status' => 'pending'
                        ]);
                        $createdLogs[] = $seedingPlan;
                        $farmOSPlans[] = $seedingPlan;
                    }

                    // Create transplant plan in farmOS (only for non-direct-sow crops)
                    if (!empty($planting['transplant_date']) && !($planting['direct_sow'] ?? false)) {
                        $transplantPlan = $this->farmOSApi->createCropPlan([
                            'type' => 'transplant',
                            'crop' => [
                                'name' => $planting['crop'],
                                'variety' => $planting['variety'] ?? ''
                            ],
                            'location' => $planting['bed_id'],
                            'timestamp' => $planting['transplant_date'],
                            'notes' => "Succession #{$planting['sequence']}: Transplant - " . ($planting['notes'] ?? ''),
                            'status' => 'pending'
                        ]);
                        $createdLogs[] = $transplantPlan;
                        $farmOSPlans[] = $transplantPlan;
                    }

                    // Create harvest plan in farmOS
                    if (!empty($planting['harvest_date'])) {
                        $harvestPlan = $this->farmOSApi->createCropPlan([
                            'type' => 'harvest',
                            'crop' => [
                                'name' => $planting['crop'],
                                'variety' => $planting['variety'] ?? ''
                            ],
                            'location' => $planting['bed_id'],
                            'timestamp' => $planting['harvest_date'],
                            'notes' => "Succession #{$planting['sequence']}: Harvest - " . ($planting['notes'] ?? ''),
                            'status' => 'pending'
                        ]);
                        $createdLogs[] = $harvestPlan;
                        $farmOSPlans[] = $harvestPlan;
                    }

                } catch (\Exception $e) {
                    $errors[] = "Failed to create farmOS plans for planting #{$planting['sequence']}: " . $e->getMessage();
                    Log::error("farmOS API error during succession planning: " . $e->getMessage());
                }
            }

            // Log what was sent to farmOS for debugging
            Log::info('Succession planning: Created ' . count($farmOSPlans) . ' plans in farmOS', [
                'plans_created' => count($farmOSPlans),
                'crop_type' => $validated['plan']['crop_type'] ?? 'unknown',
                'total_plantings' => $validated['plan']['total_plantings'] ?? 0
            ]);

            return response()->json([
                'success' => true,
                'created_logs' => count($createdLogs),
                'errors' => $errors,
                'message' => count($createdLogs) . ' farmOS crop plans created successfully',
                'note' => 'Data now exists in farmOS - planting chart will show these when refreshed'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create succession plans in farmOS: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create farmOS plans: ' . $e->getMessage(),
                'note' => 'No data was stored locally - all operations are against farmOS API'
            ], 500);
        }
    }

    /**
     * Create individual succession planting log from form data
     */
    public function createSingleLog(Request $request)
    {
        $validated = $request->validate([
            'succession_id' => 'required|string',
            'crop_name' => 'required|string',
            'variety_name' => 'nullable|string',
            'seeding' => 'required|array',
            'seeding.date' => 'required|date',
            'seeding.location' => 'required|string',
            'seeding.quantity' => 'nullable|string',
            'seeding.notes' => 'nullable|string',
            'seeding.direct_sow' => 'boolean',
            'transplant' => 'nullable|array',
            'transplant.date' => 'nullable|date',
            'transplant.location' => 'nullable|string',
            'transplant.quantity' => 'nullable|string',
            'transplant.spacing' => 'nullable|string',
            'transplant.notes' => 'nullable|string',
            'harvest' => 'required|array',
            'harvest.date' => 'required|date',
            'harvest.expected_yield' => 'nullable|string',
            'harvest.notes' => 'nullable|string',
        ]);

        try {
            $createdLogs = [];
            $errors = [];

            // Create seeding log directly
            $seedingType = ($validated['seeding']['direct_sow'] ?? false) ? 'direct_seed' : 'seed';
            $seedingNote = ($validated['seeding']['direct_sow'] ?? false) ? 'Direct Sow' : 'Seeding';
            
            $seedingLog = $this->farmOSApi->createCropPlan([
                'type' => $seedingType,
                'crop' => [
                    'name' => $validated['crop_name'],
                    'variety' => $validated['variety_name'] ?? ''
                ],
                'location' => $validated['seeding']['location'],
                'timestamp' => $validated['seeding']['date'],
                'notes' => "Succession #{$validated['succession_id']}: {$seedingNote} - " . ($validated['seeding']['notes'] ?? 'AI-calculated succession seeding'),
                'status' => 'pending'
            ]);
            $createdLogs[] = $seedingLog;

            // Create transplant log directly if scheduled
            if (!empty($validated['transplant']) && !empty($validated['transplant']['date'])) {
                $transplantLog = $this->farmOSApi->createCropPlan([
                    'type' => 'transplant',
                    'crop' => [
                        'name' => $validated['crop_name'],
                        'variety' => $validated['variety_name'] ?? ''
                    ],
                    'location' => $validated['transplant']['location'],
                    'timestamp' => $validated['transplant']['date'],
                    'notes' => "Succession #{$validated['succession_id']}: Transplant - " . ($validated['transplant']['notes'] ?? 'AI-calculated succession transplant'),
                    'status' => 'pending'
                ]);
                $createdLogs[] = $transplantLog;
            }

            // Create harvest log directly
            $harvestLog = $this->farmOSApi->createCropPlan([
                'type' => 'harvest',
                'crop' => [
                    'name' => $validated['crop_name'],
                    'variety' => $validated['variety_name'] ?? ''
                ],
                'location' => $validated['transplant']['location'] ?? $validated['seeding']['location'],
                'timestamp' => $validated['harvest']['date'],
                'notes' => "Succession #{$validated['succession_id']}: Harvest - " . ($validated['harvest']['notes'] ?? 'AI-calculated succession harvest'),
                'status' => 'pending'
            ]);
            $createdLogs[] = $harvestLog;

            Log::info('Single succession log created in farmOS', [
                'succession_id' => $validated['succession_id'],
                'crop' => $validated['crop_name'],
                'logs_created' => count($createdLogs)
            ]);

            return response()->json([
                'success' => true,
                'created_logs' => count($createdLogs),
                'message' => "Succession #{$validated['succession_id']} logs created successfully in farmOS"
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create single succession log in farmOS: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create farmOS logs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate basic succession plan with timing calculations
     */
    private function generateSuccessionPlan($validated, $existingPlans, $cropPresets)
    {
        $cropType = $validated['crop_type'];
        $variety = $validated['variety'] ?? 'Standard';
        $successionCount = $validated['succession_count'];
        $intervalDays = $validated['interval_days'];
        $firstSeedingDate = Carbon::parse($validated['first_seeding_date']);
        $notes = $validated['notes'] ?? '';
        $isDirectSow = $validated['direct_sow'] ?? false;

        // 🧠 Get AI-optimized harvest window analysis
        try {
            $contextualData = [
                'weather_forecast' => $this->getCurrentSeasonDescription(),
                'current_season_performance' => 'Planning for ' . $firstSeedingDate->format('F Y'),
                'succession_context' => "Planning {$successionCount} successions with {$intervalDays} day intervals"
            ];

            // Use new HuggingFace AI service directly instead of old HolisticAICropService
            $aiHarvestWindow = $this->getHuggingFaceHarvestWindow($cropType, $variety, $contextualData);

            if ($aiHarvestWindow['success']) {
                // Use AI recommendations for timing
                $transplantToHarvestDays = $aiHarvestWindow['peak_harvest_days'] ?? $validated['transplant_to_harvest_days'];
                $harvestDurationDays = $aiHarvestWindow['optimal_harvest_days'] ?? $validated['harvest_duration_days'];
                $recommendedInterval = $aiHarvestWindow['days_between_plantings'] ?? $intervalDays;
                
                // Adjust interval if AI suggests different timing
                if (abs($recommendedInterval - $intervalDays) <= 7) {
                    $intervalDays = $recommendedInterval;
                }
                
                $aiUsed = true;
                $aiSource = $aiHarvestWindow['source'];
                $aiConfidence = $aiHarvestWindow['ai_confidence'] ?? 'medium';
            } else {
                // Fallback to user input
                $transplantToHarvestDays = $validated['transplant_to_harvest_days'];
                $harvestDurationDays = $validated['harvest_duration_days'];
                $aiUsed = false;
                $aiSource = 'manual_input';
                $aiConfidence = 'user_specified';
            }
        } catch (\Exception $e) {
            Log::warning('AI harvest analysis failed, using manual input: ' . $e->getMessage());
            $transplantToHarvestDays = $validated['transplant_to_harvest_days'];
            $harvestDurationDays = $validated['harvest_duration_days'];
            $aiUsed = false;
            $aiSource = 'fallback_manual';
            $aiConfidence = 'user_specified';
        }

        // Get crop timing from user input (preferred) or presets
        $seedingToTransplantDays = $isDirectSow ? 0 : ($validated['seeding_to_transplant_days'] ?? 0);
        
        $plantings = [];
        
        for ($i = 0; $i < $successionCount; $i++) {
            $seedingDate = $firstSeedingDate->copy()->addDays($i * $intervalDays);
            
            // For direct sow crops, transplant date is null
            $transplantDate = $isDirectSow ? null : 
                ($seedingToTransplantDays > 0 ? $seedingDate->copy()->addDays($seedingToTransplantDays) : null);
                
            // Calculate harvest date based on planting method
            if ($isDirectSow) {
                // Direct sow: harvest date calculated from seeding date
                $harvestDate = $seedingDate->copy()->addDays($transplantToHarvestDays);
            } else {
                // Transplant: harvest date calculated from transplant date
                $harvestDate = $transplantDate ? 
                    $transplantDate->copy()->addDays($transplantToHarvestDays) :
                    $seedingDate->copy()->addDays($transplantToHarvestDays);
            }
            
            $plantings[] = [
                'sequence' => $i + 1,
                'crop' => $cropType,
                'variety' => $variety,
                'seeding_date' => $seedingDate->format('Y-m-d'),
                'transplant_date' => $transplantDate ? $transplantDate->format('Y-m-d') : null,
                'harvest_date' => $harvestDate->format('Y-m-d'),
                'harvest_duration_days' => $harvestDurationDays,
                'direct_sow' => $isDirectSow,
                'notes' => $notes,
                'bed_id' => null, // To be assigned later
                'conflicts' => [],
                'ai_optimized' => $aiUsed,
                'ai_confidence' => $aiConfidence
            ];
        }

        return [
            'crop_type' => $cropType,
            'variety' => $variety,
            'total_plantings' => $successionCount,
            'interval_days' => $intervalDays,
            'direct_sow' => $isDirectSow,
            'plantings' => $plantings,
            'ai_enhanced' => $aiUsed,
            'ai_source' => $aiSource,
            'ai_confidence' => $aiConfidence,
            'ai_recommendations' => $aiHarvestWindow['contextual_factors'] ?? []
        ];
    }

    /**
     * Assign beds while checking for conflicts with existing farmOS data
     */
    private function assignBedsWithConflictResolution($plan)
    {
        // Get all available beds from farmOS
        $geometryAssets = $this->farmOSApi->getGeometryAssets();
        $availableBeds = $this->extractAvailableBeds($geometryAssets);
        
        // Get existing crop plans to check for conflicts
        $existingPlans = $this->farmOSApi->getCropPlanningData();
        
        $conflictsResolved = 0;
        
        foreach ($plan['plantings'] as &$planting) {
            $assigned = false;
            $attemptCount = 0;
            
            // Try to find an available bed
            while (!$assigned && $attemptCount < count($availableBeds)) {
                $candidateBed = $availableBeds[$attemptCount % count($availableBeds)];
                
                // Check for conflicts with existing plans
                $conflicts = $this->checkBedConflicts(
                    $candidateBed, 
                    $planting['seeding_date'], 
                    $planting['harvest_date'], 
                    $existingPlans
                );
                
                if (empty($conflicts)) {
                    $planting['bed_id'] = $candidateBed['id'];
                    $planting['bed_name'] = $candidateBed['name'];
                    $assigned = true;
                } else {
                    $planting['conflicts'] = $conflicts;
                    $conflictsResolved++;
                }
                
                $attemptCount++;
            }
            
            if (!$assigned) {
                $planting['bed_id'] = null;
                $planting['bed_name'] = 'No available bed found';
                $planting['conflicts'][] = 'All beds are occupied during this period';
            }
        }
        
        $plan['conflicts_resolved'] = $conflictsResolved;
        return $plan;
    }

    /**
     * Use AI to optimize the succession plan
     */
    private function optimizePlanWithAI($plan, $originalRequest)
    {
        try {
            $aiContext = [
                'crop_type' => $plan['crop_type'],
                'variety' => $plan['variety'],
                'total_plantings' => $plan['total_plantings'],
                'interval_days' => $plan['interval_days'],
                'conflicts_resolved' => $plan['conflicts_resolved'] ?? 0,
                'current_date' => now()->format('Y-m-d'),
                'farm_beds' => count($this->getFallbackBeds()),
                'plantings_overview' => array_map(function($p) {
                    return [
                        'sequence' => $p['sequence'],
                        'seeding_date' => $p['seeding_date'],
                        'harvest_date' => $p['harvest_date'],
                        'bed_assigned' => !empty($p['bed_id']),
                        'conflicts' => count($p['conflicts'] ?? [])
                    ];
                }, $plan['plantings'])
            ];

            $aiQuestion = "Analyze this succession planting plan for {$plan['crop_type']} and suggest optimizations. "
                        . "Consider: timing intervals, bed rotation, seasonal factors, and any conflicts found. "
                        . "Provide specific recommendations for improving yield and reducing conflicts.";

            $response = Http::timeout(3)->post(env('AI_SERVICE_URL', 'http://localhost:8005'), [
                'question' => $aiQuestion,
                'context' => json_encode($aiContext)
            ]);

            if ($response->successful()) {
                $aiData = $response->json();
                $plan['ai_recommendations'] = $aiData['answer'] ?? 'No recommendations available';
                $plan['ai_analysis_date'] = now()->format('Y-m-d H:i:s');
            } else {
                $plan['ai_recommendations'] = 'AI analysis unavailable';
            }

        } catch (\Exception $e) {
            Log::warning('AI optimization failed: ' . $e->getMessage());
            $plan['ai_recommendations'] = 'AI analysis temporarily unavailable';
        }

        return $plan;
    }

    /**
     * Check for bed conflicts with existing farmOS plans
     */
    private function checkBedConflicts($bed, $startDate, $endDate, $existingPlans)
    {
        $conflicts = [];
        $startCarbon = Carbon::parse($startDate);
        $endCarbon = Carbon::parse($endDate);

        foreach ($existingPlans as $plan) {
            if (isset($plan['location']) && $plan['location'] === $bed['name']) {
                $planStart = Carbon::parse($plan['start_date'] ?? $plan['timestamp']);
                $planEnd = Carbon::parse($plan['end_date'] ?? $plan['harvest_date'] ?? $planStart->copy()->addDays(90));

                // Check for date overlap
                if ($startCarbon->lte($planEnd) && $endCarbon->gte($planStart)) {
                    $conflicts[] = [
                        'type' => 'date_overlap',
                        'existing_crop' => $plan['crop'] ?? 'Unknown crop',
                        'existing_start' => $planStart->format('Y-m-d'),
                        'existing_end' => $planEnd->format('Y-m-d')
                    ];
                }
            }
        }

        return $conflicts;
    }

    /**
     * Extract available beds from farmOS geometry assets
     */
    private function extractAvailableBeds($geometryAssets)
    {
        $beds = [];
        
        if (isset($geometryAssets['features'])) {
            foreach ($geometryAssets['features'] as $feature) {
                $properties = $feature['attributes'] ?? [];
                $name = $properties['name'] ?? 'Unknown';
                
                // Filter for bed-type assets
                if (stripos($name, 'bed') !== false || preg_match('/\d+\/\d+/', $name)) {
                    $beds[] = [
                        'id' => $feature['id'] ?? uniqid(),
                        'name' => $name,
                        'type' => $properties['type'] ?? 'bed',
                        'area' => $properties['area'] ?? 0
                    ];
                }
            }
        }

        // Fallback if no beds found
        if (empty($beds)) {
            return $this->getFallbackBeds();
        }

        return $beds;
    }

    /**
     * Get fallback bed data for demo/testing
     */
    private function getFallbackBeds()
    {
        $beds = [];
        for ($block = 1; $block <= 10; $block++) {
            for ($bed = 1; $bed <= 16; $bed++) {
                $beds[] = [
                    'id' => "bed_{$block}_{$bed}",
                    'name' => "{$block}/{$bed}",
                    'type' => 'bed',
                    'area' => 100 + ($block * $bed * 10) % 150 // Dynamic area calculation
                ];
            }
        }
        return $beds;
    }

    /**
     * Get crop timing presets for common market garden crops
     */
    private function getCropTimingPresets()
    {
        return [
            'lettuce' => [
                'transplant_days' => 21,  // Seed to transplant
                'harvest_days' => 65,     // Seed to harvest
                'yield_period' => 14      // Harvest window
            ],
            'carrot' => [
                'transplant_days' => 0,   // Direct seed
                'harvest_days' => 75,
                'yield_period' => 21
            ],
            'radish' => [
                'transplant_days' => 0,
                'harvest_days' => 30,
                'yield_period' => 10
            ],
            'spinach' => [
                'transplant_days' => 14,
                'harvest_days' => 50,
                'yield_period' => 21
            ],
            'kale' => [
                'transplant_days' => 28,
                'harvest_days' => 70,
                'yield_period' => 60
            ],
            'arugula' => [
                'transplant_days' => 0,
                'harvest_days' => 40,
                'yield_period' => 14
            ],
            'chard' => [
                'transplant_days' => 21,
                'harvest_days' => 60,
                'yield_period' => 90
            ],
            'beets' => [
                'transplant_days' => 0,
                'harvest_days' => 70,
                'yield_period' => 21
            ],
            'cilantro' => [
                'transplant_days' => 0,   // Direct seed
                'harvest_days' => 45,
                'yield_period' => 21
            ],
            'dill' => [
                'transplant_days' => 0,   // Direct seed
                'harvest_days' => 50,
                'yield_period' => 14
            ],
            'scallion' => [
                'transplant_days' => 14,
                'harvest_days' => 60,
                'yield_period' => 30
            ],
            'mesclun' => [
                'transplant_days' => 0,   // Cut-and-come-again mix
                'harvest_days' => 30,
                'yield_period' => 21
            ],
            'fennel' => [
                'transplant_days' => 21,  // Can be transplanted or direct seeded
                'harvest_days' => 85,     // Seed to harvest (bulb fennel)
                'yield_period' => 14      // Harvest window for bulbs
            ],
            'default' => [
                'transplant_days' => 21,
                'harvest_days' => 70,
                'yield_period' => 21
            ]
        ];
    }

    /**
     * Get AI-powered crop timing recommendations
     */
    public function getAICropTiming(Request $request): JsonResponse
    {
        // Debug logging
        Log::info('AI crop timing request received', [
            'crop_type' => $request->input('crop_type'),
            'season' => $request->input('season'),
            'is_direct_sow' => $request->input('is_direct_sow')
        ]);
        
        try {
            $cropType = $request->input('crop_type');
            $season = $request->input('season', $this->getCurrentSeason());
            $isDirectSow = $request->input('is_direct_sow', false);
            
            if (!$cropType) {
                Log::warning('AI crop timing: Missing crop type');
                return new JsonResponse(['error' => 'Crop type is required'], 400);
            }

            // Get base timing from presets
            $presets = $this->getCropTimingPresets();
            $baseTiming = $presets[$cropType] ?? $presets['default'];
            
            // Apply seasonal adjustments
            $seasonalAdjustments = $this->getSeasonalAdjustments($season);
            
            // Calculate AI-enhanced timing
            $timing = $this->calculateAITiming($baseTiming, $seasonalAdjustments, $isDirectSow, $cropType, $season);
            
            Log::info('AI crop timing success', [
                'crop_type' => $cropType,
                'season' => $season,
                'timing' => $timing
            ]);
            
            return new JsonResponse([
                'success' => true,
                'timing' => $timing,
                'recommendations' => $this->getAIRecommendations($cropType, $season, $isDirectSow)
            ]);
            
        } catch (\Exception $e) {
            Log::error('AI crop timing failed: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to get timing recommendations'], 500);
        }
    }

    /**
     * Calculate AI-enhanced timing based on crop, season, and growing method
     */
    private function calculateAITiming(array $baseTiming, array $seasonalAdjustments, bool $isDirectSow, string $cropType, string $season): array
    {
        $timing = [
            'days_to_transplant' => $baseTiming['transplant_days'],
            'days_to_harvest' => $baseTiming['harvest_days'],
            'harvest_window' => $baseTiming['yield_period']
        ];
        
        // Apply direct sow adjustments
        if ($isDirectSow) {
            $timing['days_to_transplant'] = 0;
            // Direct sow crops often take slightly longer to harvest
            $timing['days_to_harvest'] += $this->getDirectSowAdjustment($cropType);
        }
        
        // Apply seasonal adjustments
        $timing['days_to_transplant'] = max(0, $timing['days_to_transplant'] + $seasonalAdjustments['transplant_adjustment']);
        $timing['days_to_harvest'] = max(1, $timing['days_to_harvest'] + $seasonalAdjustments['harvest_adjustment']);
        $timing['harvest_window'] = max(7, $timing['harvest_window'] + $seasonalAdjustments['window_adjustment']);
        
        // Apply crop-specific seasonal logic
        $timing = $this->applyCropSpecificSeasonalLogic($timing, $cropType, $season);
        
        return $timing;
    }

    /**
     * Get seasonal adjustments for timing calculations
     */
    private function getSeasonalAdjustments(string $season): array
    {
        // Convert to lowercase for consistent matching
        $season = strtolower($season);
        
        $adjustments = [
            'spring' => [
                'transplant_adjustment' => 0,    // Standard timing
                'harvest_adjustment' => 0,
                'window_adjustment' => 0
            ],
            'summer' => [
                'transplant_adjustment' => -3,   // Faster growth in heat
                'harvest_adjustment' => -7,     // Quicker maturation
                'window_adjustment' => -3       // Shorter harvest window (bolting risk)
            ],
            'fall' => [
                'transplant_adjustment' => 2,    // Slightly slower establishment
                'harvest_adjustment' => 5,      // Slower growth in cooling weather
                'window_adjustment' => 7        // Longer harvest window (less bolting)
            ],
            'winter' => [
                'transplant_adjustment' => 7,    // Much slower establishment
                'harvest_adjustment' => 14,     // Significantly slower growth
                'window_adjustment' => 14       // Extended harvest window
            ]
        ];
        
        return $adjustments[$season] ?? $adjustments['spring'];
    }

    /**
     * Get direct sow timing adjustments for specific crops
     */
    private function getDirectSowAdjustment(string $cropType): int
    {
        $adjustments = [
            'lettuce' => 7,      // Direct sow lettuce takes longer to establish
            'carrot' => 0,       // Carrots are typically direct sown
            'radish' => 0,       // Radishes are typically direct sown
            'spinach' => 5,      // Direct sow spinach slightly slower
            'arugula' => 3,      // Minimal difference for arugula
            'kale' => 10,        // Kale benefits from transplant head start
            'chard' => 7,        // Chard establishes better as transplant
            'beet' => 0,         // Beets are typically direct sown
            'turnip' => 0,       // Turnips are typically direct sown
            'cucumber' => 14,    // Cucumbers much better as transplants
            'tomato' => 21,      // Tomatoes require transplants in most climates
            'pepper' => 21,      // Peppers require transplants
            'basil' => 10,       // Basil better as transplant
            'cilantro' => 0,     // Cilantro often direct sown
            'dill' => 0,         // Dill typically direct sown
            'scallion' => 5,     // Scallions can be either
            'mesclun' => 0       // Mix is typically direct sown
        ];
        
        return $adjustments[$cropType] ?? 7; // Default 7-day adjustment for unknown crops
    }

    /**
     * Apply crop-specific seasonal logic
     */
    private function applyCropSpecificSeasonalLogic(array $timing, string $cropType, string $season): array
    {
        // Cool season crops (lettuce, spinach, kale, etc.)
        $coolSeasonCrops = ['lettuce', 'spinach', 'arugula', 'kale', 'chard', 'radish', 'turnip', 'cilantro', 'dill', 'mesclun'];
        
        // Warm season crops (tomato, pepper, cucumber, basil)
        $warmSeasonCrops = ['tomato', 'pepper', 'cucumber', 'basil'];
        
        if (in_array($cropType, $coolSeasonCrops)) {
            if ($season === 'summer') {
                // Cool season crops bolt quickly in summer heat
                $timing['harvest_window'] = max(7, $timing['harvest_window'] - 7);
                $timing['days_to_harvest'] -= 3; // Harvest earlier before bolting
            } elseif ($season === 'fall' || $season === 'winter') {
                // Cool season crops thrive in cool weather
                $timing['harvest_window'] += 7; // Extended harvest window
            }
        }
        
        if (in_array($cropType, $warmSeasonCrops)) {
            if ($season === 'winter') {
                // Warm season crops may not be viable in winter
                $timing['days_to_harvest'] += 21; // Much slower growth
                $timing['harvest_window'] -= 7;   // Shorter viable period
            } elseif ($season === 'summer') {
                // Warm season crops thrive in heat
                $timing['days_to_harvest'] -= 7;  // Faster maturation
                $timing['harvest_window'] += 14;  // Longer productive period
            }
        }
        
        return $timing;
    }

    /**
     * Get AI recommendations for the crop and conditions
     */
    private function getAIRecommendations(string $cropType, string $season, bool $isDirectSow): array
    {
        $recommendations = [];
        
        // Convert season to lowercase for consistent matching
        $season = strtolower($season);
        
        // Seasonal recommendations
        $seasonalTips = [
            'spring' => 'Ideal growing conditions. Watch for late frost risks.',
            'summer' => 'Hot weather. Provide shade for cool-season crops and ensure adequate water.',
            'fall' => 'Cool growing season. Excellent for greens and root vegetables.',
            'winter' => 'Slow growth period. Consider row covers or greenhouse protection.'
        ];
        
        $recommendations[] = $seasonalTips[$season] ?? $seasonalTips['spring'];
        
        // Direct sow vs transplant recommendations
        if ($isDirectSow) {
            $recommendations[] = 'Direct seeding: Ensure consistent soil moisture for germination.';
            
            // Crop-specific direct sow tips
            switch ($cropType) {
                case 'lettuce':
                    $recommendations[] = 'Lettuce: Thin seedlings to 6-8" spacing. Successive plant every 2 weeks.';
                    break;
                case 'carrot':
                    $recommendations[] = 'Carrots: Do not transplant. Thin to 2" spacing when 2" tall.';
                    break;
                case 'radish':
                    $recommendations[] = 'Radishes: Quick crop. Can interplant with slower vegetables.';
                    break;
            }
        } else {
            $recommendations[] = 'Transplanting: Start seeds ' . (14 + ($season === 'winter' ? 7 : 0)) . ' days before transplant date.';
        }
        
        // Crop-specific recommendations
        switch ($cropType) {
            case 'lettuce':
                $recommendations[] = 'Lettuce: Harvest outer leaves for continuous production.';
                break;
            case 'tomato':
                $recommendations[] = 'Tomatoes: Stake or cage for support. Prune suckers for better fruit development.';
                break;
            case 'cucumber':
                $recommendations[] = 'Cucumbers: Provide trellis support. Harvest regularly to encourage production.';
                break;
            case 'basil':
                $recommendations[] = 'Basil: Pinch flowers to encourage leaf growth. Harvest regularly.';
                break;
        }
        
        return $recommendations;
    }

    /**
     * Get current season based on date
     */
    private function getCurrentSeason(): string
    {
        $month = (int) date('n');
        
        if ($month >= 3 && $month <= 5) {
            return 'spring';
        } elseif ($month >= 6 && $month <= 8) {
            return 'summer';
        } elseif ($month >= 9 && $month <= 11) {
            return 'fall';
        } else {
            return 'winter';
        }
    }

    /**
     * 🌟 Get holistic AI crop recommendations with sacred geometry and lunar wisdom
     */
    public function getHolisticRecommendations(Request $request): JsonResponse
    {
        try {
            $cropType = $request->input('crop_type');
            $season = $request->input('season', $this->getCurrentSeason());
            
            if (!$cropType) {
                return response()->json(['error' => 'Crop type is required'], 400);
            }

            Log::info('🌙 Getting holistic recommendations', [
                'crop' => $cropType,
                'season' => $season,
                'moon_phase' => 'checking'
            ]);

            // Get comprehensive holistic recommendations
            $holisticRec = $this->holisticAI ? $this->holisticAI->getHolisticRecommendations($cropType, [
                'season' => $season,
                'include_sacred_geometry' => true,
                'include_lunar_timing' => true,
                'include_biodynamic' => true
            ]) : $this->getBasicHolisticRecommendations($cropType, $season);

            // Get sacred geometry spacing
            $spacing = $this->holisticAI ? $this->holisticAI->getSacredGeometrySpacing($cropType) : ['rows' => 30, 'plants' => 20, 'geometry' => 'square'];

            // Get companion mandala
            $companions = $this->holisticAI ? $this->holisticAI->getCompanionMandala($cropType) : ['basil', 'lettuce'];

            // Get lunar timing
            $lunarTiming = $this->holisticAI ? $this->holisticAI->getCurrentLunarTiming() : ['phase' => 'unknown', 'favorable_for' => 'general', 'current_date' => now()->format('Y-m-d')];

            $response = [
                'success' => true,
                'crop' => $cropType,
                'season' => $season,
                'holistic_wisdom' => $holisticRec,
                'sacred_spacing' => $spacing,
                'companion_mandala' => $companions,
                'lunar_timing' => $lunarTiming,
                'integration_notes' => [
                    '🌙 Plant during optimal lunar phase for maximum vitality',
                    '🌀 Use golden ratio spacing (1:1.618) for harmonious energy flow', 
                    '🌸 Create companion mandalas for living ecosystem balance',
                    '⭐ Honor cosmic timing for enhanced growth and flavor'
                ]
            ];

            Log::info('✨ Holistic recommendations generated successfully', [
                'crop' => $cropType,
                'has_sacred_geometry' => !empty($spacing),
                'has_companions' => !empty($companions),
                'lunar_phase' => $lunarTiming['current_phase'] ?? 'unknown'
            ]);

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('🚨 Holistic recommendations failed: ' . $e->getMessage());
            
            // Fallback to basic recommendations with mystical inspiration
            return response()->json([
                'success' => true,
                'crop' => $request->input('crop_type'),
                'holistic_wisdom' => $this->getFallbackHolisticWisdom($request->input('crop_type')),
                'message' => '🌱 Using ancient wisdom while cosmic connections restore...'
            ]);
        }
    }

    /**
     * 🌙 Get current moon phase and optimal planting timing
     */
    public function getMoonPhaseGuidance(Request $request): JsonResponse
    {
        try {
            $guidance = $this->holisticAI ? $this->holisticAI->getCurrentLunarTiming() : ['phase' => 'unknown', 'favorable_for' => 'general', 'current_date' => now()->format('Y-m-d')];
            
            return response()->json([
                'success' => true,
                'lunar_guidance' => $guidance,
                'cosmic_wisdom' => [
                    'New Moon' => 'Perfect for planting seeds - earth energy is receptive to new beginnings',
                    'Waxing Crescent' => 'Excellent for transplanting - growth energy is building',
                    'First Quarter' => 'Time for balanced maintenance and strengthening plant support',
                    'Waxing Gibbous' => 'Monitor and adjust - plants absorbing maximum cosmic energy',
                    'Full Moon' => 'Optimal harvest time - maximum life force and flavor concentration',
                    'Waning Gibbous' => 'Processing and preservation - cosmic energy moving inward',
                    'Last Quarter' => 'Pruning and removing - releasing what no longer serves',
                    'Waning Crescent' => 'Rest and soil restoration - preparing for next lunar cycle'
                ]
            ]);

        } catch (\Exception $e) {
            Log::warning('Moon phase guidance unavailable: ' . $e->getMessage());
            
            return response()->json([
                'success' => true,
                'lunar_guidance' => $this->getBasicLunarWisdom(),
                'message' => 'Using traditional lunar wisdom while cosmic connections restore...'
            ]);
        }
    }

    /**
     * 🌀 Get sacred geometry spacing recommendations
     */
    public function getSacredSpacing(Request $request): JsonResponse
    {
        try {
            $cropType = $request->input('crop_type');
            
            if (!$cropType) {
                return response()->json(['error' => 'Crop type is required'], 400);
            }

            $spacing = $this->holisticAI ? $this->holisticAI->getSacredGeometrySpacing($cropType) : ['rows' => 30, 'plants' => 20, 'geometry' => 'square'];
            
            return response()->json([
                'success' => true,
                'crop' => $cropType,
                'sacred_spacing' => $spacing,
                'geometry_wisdom' => [
                    'Golden Ratio (φ = 1.618)' => 'Nature\'s perfect proportion found in sunflowers, nautilus shells, and galaxy spirals',
                    'Fibonacci Sequence' => 'Sacred numbers: 1, 1, 2, 3, 5, 8, 13, 21, 34... for optimal plant arrangements',
                    'Hexagonal Patterns' => 'Six-sided formations maximize energy exchange and space efficiency',
                    'Spiral Arrangements' => 'Follow natural vortex patterns for enhanced vitality flow'
                ]
            ]);

        } catch (\Exception $e) {
            Log::warning('Sacred spacing unavailable: ' . $e->getMessage());
            
            return response()->json([
                'success' => true,
                'sacred_spacing' => $this->getBasicSacredSpacing($request->input('crop_type')),
                'message' => 'Using geometric principles while holistic service restores...'
            ]);
        }
    }

    /**
     * 🌸 Enhance existing succession plan with holistic AI wisdom
     */
    private function enhanceWithHolisticWisdom(array $plan, array $params): array
    {
        try {
            // Add holistic enhancements to the basic plan
            $enhanced = $this->holisticAI ? $this->holisticAI->enhanceSuccessionPlan($plan, $params) : $this->enhancePlanWithBasicWisdom($plan, $params);
            
            if ($enhanced['success'] ?? false) {
                Log::info('✨ Plan enhanced with holistic wisdom', [
                    'crop' => $params['crop_type'],
                    'enhancements' => count($enhanced['holistic_enhancements'] ?? [])
                ]);
                
                return $enhanced;
            }
            
            return $plan;
            
        } catch (\Exception $e) {
            Log::warning('Holistic enhancement failed, using base plan: ' . $e->getMessage());
            return $plan;
        }
    }

    /**
     * Fallback holistic wisdom when AI service is unavailable
     */
    private function getFallbackHolisticWisdom(string $cropType): array
    {
        return [
            'ancient_wisdom' => "🌱 {$cropType} carries the wisdom of countless seasons. Plant with intention and gratitude.",
            'elemental_guidance' => $this->getElementalGuidance($cropType),
            'seasonal_harmony' => 'Align your planting with natural rhythms - early morning for peace, evening for reflection.',
            'companion_spirits' => $this->getBasicCompanions($cropType),
            'sacred_reminder' => 'Every seed contains infinite potential. Honor the mystery of growth.'
        ];
    }

    private function getElementalGuidance(string $cropType): string
    {
        $elements = [
            'lettuce' => 'Water element - flows with lunar cycles, thrives with gentle moisture',
            'carrot' => 'Earth element - deep roots connect to underground wisdom',
            'radish' => 'Fire element - quick transformation, cleansing energy',
            'spinach' => 'Water element - cooling energy, lunar-responsive growth',
            'kale' => 'Earth element - sturdy constitution, grounding energy',
            'arugula' => 'Fire element - spicy life force, awakening energy'
        ];
        
        return $elements[$cropType] ?? 'Mixed elements - balanced approach honors all aspects of nature';
    }

    private function getBasicCompanions(string $cropType): array
    {
        $companions = [
            'lettuce' => ['Radish (pest protection)', 'Marigold (beneficial insects)', 'Chives (growth enhancement)'],
            'carrot' => ['Chives (flavor improvement)', 'Rosemary (pest deterrent)', 'Sage (energetic protection)'],
            'radish' => ['Lettuce (space sharing)', 'Spinach (soil improvement)', 'Calendula (healing energy)'],
            'spinach' => ['Strawberry (ground cover)', 'Thyme (aromatic support)', 'Borage (mineral uptake)'],
            'kale' => ['Nasturtium (pest control)', 'Dill (beneficial insects)', 'Chamomile (soil health)'],
            'arugula' => ['Basil (flavor synergy)', 'Oregano (protection)', 'Parsley (companion support)']
        ];
        
        return $companions[$cropType] ?? ['Marigold (universal companion)', 'Basil (harmony)', 'Chamomile (gentle healing)'];
    }

    private function getBasicLunarWisdom(): array
    {
        $currentDay = date('j');
        $phase = $currentDay <= 7 ? 'waxing' : ($currentDay <= 14 ? 'full' : ($currentDay <= 21 ? 'waning' : 'new'));
        
        return [
            'current_phase' => $phase,
            'guidance' => "Current lunar energy supports {$phase} moon activities",
            'planting_advice' => 'Plant seeds during new moon, transplant during waxing, harvest during full moon',
            'cosmic_reminder' => 'Moon cycles guide the flow of water and energy in all living things'
        ];
    }

    private function getBasicSacredSpacing(string $cropType): array
    {
        $baseSpacing = [
            'lettuce' => 6, 'carrot' => 2, 'radish' => 1,
            'spinach' => 4, 'kale' => 12, 'arugula' => 4
        ];
        
        $spacing = $baseSpacing[$cropType] ?? 6;
        $goldenRatio = 1.618;
        
        return [
            'plant_spacing_inches' => $spacing,
            'row_spacing_inches' => round($spacing * $goldenRatio, 1),
            'bed_width_ratio' => $spacing * 8, // Fibonacci number
            'path_width_ratio' => $spacing * 3, // Fibonacci number
            'sacred_note' => 'Spacing based on golden ratio (φ = 1.618) and Fibonacci sequence'
        ];
    }

    /**
     * Get current season description for AI context
     */
    private function getCurrentSeasonDescription(): string
    {
        $month = now()->month;
        
        if (in_array($month, [12, 1, 2])) {
            return 'Winter conditions: Cold temperatures, limited growing season, protected cultivation recommended';
        } elseif (in_array($month, [3, 4, 5])) {
            return 'Spring conditions: Cool, wet conditions with increasing daylight, risk of late frost';
        } elseif (in_array($month, [6, 7, 8])) {
            return 'Summer conditions: Warm, stable conditions, monitor for heat stress and drought';
        } else {
            return 'Autumn conditions: Cooling temperatures with shorter days, focus on cold-hardy varieties';
        }
    }

    /**
     * Get AI-optimized harvest window for succession planning
     */
    public function getOptimalHarvestWindow(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'crop_type' => 'required|string',
            'variety' => 'nullable|string',
            'location' => 'nullable|string'
        ]);

        try {
            // Gather contextual data for better AI analysis
            $contextualData = [
                'weather_forecast' => 'Current seasonal conditions for ' . ($validated['location'] ?? 'farm location'),
                'current_season_performance' => 'Planning analysis for succession timing'
            ];

            // Use new HuggingFace AI service instead of old HolisticAICropService
            $harvestWindow = $this->getHuggingFaceHarvestWindow(
                $validated['crop_type'],
                $validated['variety'],
                $contextualData
            );

            if ($harvestWindow['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $harvestWindow,
                    'ai_confidence' => $harvestWindow['ai_confidence'],
                    'data_quality' => 'huggingface_ai',
                    'recommendations_basis' => 'llama_3_1_analysis',
                    'contextual_factors' => [$harvestWindow['raw_answer'] ?? 'AI analysis complete'],
                    'timestamp' => now()->toISOString()
                ]);
            } else {
                throw new \Exception($harvestWindow['error'] ?? 'AI analysis failed');
            }

        } catch (\Exception $e) {
            Log::error('Harvest window optimization failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to get harvest window optimization',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle chat messages to Mistral AI
     */
    public function chat(Request $request): JsonResponse
    {
        // Fast execution settings for HuggingFace AI
        set_time_limit(30); // 30 seconds max - fast AI service
        ini_set('max_execution_time', 30);
        ini_set('default_socket_timeout', 10); // 10-second socket timeout for fast response
        
        try {
            $validated = $request->validate([
                'question' => 'required|string|max:1000',  // Fixed: expect 'question' not 'message'
                'crop_type' => 'nullable|string',
                'season' => 'nullable|string',
                'context' => 'nullable|string'
            ]);

            // Build contextual question for AI
            $question = $validated['question'];
            
            // Add context if provided
            if (!empty($validated['crop_type'])) {
                $question .= " (Context: working with {$validated['crop_type']} crop)";
            }
            
            if (!empty($validated['season'])) {
                $question .= " (Season: {$validated['season']})";
            }

            // Call AI service with fast timeout
            $response = Http::timeout(3) // 3 seconds - fast AI timeout
                ->connectTimeout(5) // 5 second connection timeout
                ->retry(1, 1000) // Retry once after 1 second if it fails
                ->withOptions([
                    'stream_context' => stream_context_create([
                        'http' => [
                            'timeout' => 3.0,
                        ]
                    ])
                ])
                ->post(env('AI_SERVICE_URL', 'http://localhost:8005') . '/ask', [
                    'question' => $question,
                    'context' => $validated['context'] ?? 'succession_planning_chat'
                ]);

            if ($response->successful()) {
                $aiData = $response->json();
                
                Log::info('Chat AI response received', [
                    'user_message' => $validated['question'],
                    'ai_wisdom' => $aiData['wisdom'] ?? 'basic',
                    'moon_phase' => $aiData['moon_phase'] ?? 'unknown'
                ]);

                return response()->json([
                    'success' => true,
                    'answer' => $aiData['answer'] ?? 'AI response unavailable',
                    'wisdom' => $aiData['wisdom'] ?? 'Basic agricultural guidance',
                    'moon_phase' => $aiData['moon_phase'] ?? 'unknown',
                    'context' => $aiData['context'] ?? null,
                    'source' => 'Mistral 7B Holistic AI',
                    'timestamp' => now()->toISOString()
                ]);
            } else {
                throw new \Exception('AI service returned: ' . $response->status());
            }

        } catch (\Exception $e) {
            Log::error('Chat AI service error: ' . $e->getMessage());
            
            // Return fallback wisdom instead of error
            $fallbackWisdom = $this->getFallbackChatWisdom($validated['question'] ?? 'farming question');
            
            return response()->json([
                'success' => true,
                'answer' => $fallbackWisdom,
                'wisdom' => 'Fallback wisdom - AI service temporarily unavailable',
                'moon_phase' => 'unknown',
                'source' => 'Fallback System',
                'timestamp' => now()->toISOString()
            ]);
        }
    }

    /**
     * Generate fallback wisdom when AI is unavailable
     */
    private function getFallbackChatWisdom(string $message): string
    {
        $fallbackResponses = [
            'planting' => 'For optimal planting, consider soil temperature, moisture levels, and local frost dates. Each crop has specific requirements for successful germination.',
            'harvest' => 'Harvest timing depends on crop maturity indicators. Look for size, color, and texture changes that signal peak readiness.',
            'spacing' => 'Proper plant spacing prevents competition and disease. Follow seed packet recommendations and adjust for your growing conditions.',
            'companion' => 'Companion planting can improve soil health, deter pests, and maximize garden space efficiency.',
            'soil' => 'Healthy soil is the foundation of successful farming. Test pH, add organic matter, and ensure proper drainage.',
            'season' => 'Work with your local growing season. Know your frost dates and choose varieties suited to your climate zone.',
            'water' => 'Consistent moisture is key. Water deeply but less frequently to encourage strong root systems.',
            'default' => 'Successful farming combines traditional wisdom with observation of your specific growing conditions.'
        ];

        // Match keywords to appropriate responses
        $message = strtolower($message);
        
        foreach ($fallbackResponses as $keyword => $response) {
            if (strpos($message, $keyword) !== false) {
                return $response;
            }
        }
        
        return $fallbackResponses['default'];
    }

    /**
     * Wake up AI service to avoid cold start delays
     */
    private function wakeUpAIService()
    {
        try {
            // Send a quick wake-up ping to the AI service with minimal timeout
            // Make it completely non-blocking to prevent any 502 issues
            Http::timeout(1)->connectTimeout(1)->post(env('AI_SERVICE_URL', 'http://localhost:8005') . '/ask', [
                'question' => 'wake up',
                'context' => 'system_wake'
            ]);
            
            Log::debug('AI service wake-up ping sent successfully');
            
        } catch (\Exception $e) {
            // Completely silent fail - don't even log unless in debug mode
            // This prevents any possibility of causing 502 errors
            if (config('app.debug')) {
                Log::debug('AI wake-up ping failed (non-critical): ' . $e->getMessage());
            }
        }
    }

    /**
     * Get AI service status
     */
    public function getAIStatus(): JsonResponse
    {
        try {
            $response = Http::timeout(3)->get(env('AI_SERVICE_URL', 'http://localhost:8005') . '/health');
            
            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'status' => 'online',
                    'health' => $data['status'] ?? 'healthy',
                    'model' => $data['model'] ?? 'llama31-8b',
                    'response_time' => '< 3s',
                    'last_check' => now()->format('H:i:s')
                ]);
            } else {
                throw new \Exception('Health check failed: ' . $response->status());
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'offline',
                'health' => 'unavailable',
                'model' => 'unknown',
                'error' => $e->getMessage(),
                'last_check' => now()->format('H:i:s')
            ]);
        }
    }

    /**
     * Manual wake-up AI endpoint
     */
    public function wakeUpAI(): JsonResponse
    {
        try {
            $this->wakeUpAIService();
            
            // Test if AI is responding after wake-up
            $response = Http::timeout(5)->post(env('AI_SERVICE_URL', 'http://localhost:8005') . '/ask', [
                'question' => 'test response',
                'context' => 'wake_up_test'
            ]);
            
            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'AI service is now awake and responding',
                    'status' => 'online',
                    'model' => 'llama31-8b'
                ]);
            } else {
                throw new \Exception('AI test failed: ' . $response->status());
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'AI service wake-up failed: ' . $e->getMessage(),
                'status' => 'offline'
            ], 500);
        }
    }

    /**
     * Get harvest window analysis from HuggingFace AI service
     */
    private function getHuggingFaceHarvestWindow(string $cropType, string $variety = null, array $contextualData = []): array
    {
        try {
            // Build detailed question for AI about harvest window
            $varietyText = $variety && $variety !== 'Standard' ? " variety $variety" : '';
            $question = "For $cropType$varietyText succession planting, what is the optimal harvest window duration in days, how many succession plantings should I do, and how many days between each planting? Please provide specific numbers for Brussels Sprouts growing.";
            
            // Add context
            if (!empty($contextualData)) {
                $contextText = '';
                if (isset($contextualData['succession_context'])) {
                    $contextText .= ' ' . $contextualData['succession_context'];
                }
                if (isset($contextualData['current_season_performance'])) {
                    $contextText .= ' ' . $contextualData['current_season_performance'];
                }
                $question .= $contextText;
            }

            // Call HuggingFace AI service
            $response = Http::timeout(10)
                ->connectTimeout(10)
                ->retry(2, 2000)
                ->post(env('AI_SERVICE_URL', 'http://localhost:8005') . '/ask', [
                    'question' => $question,
                    'context' => 'harvest_window_analysis'
                ]);

            if ($response->successful()) {
                $aiData = $response->json();
                
                if (isset($aiData['answer'])) {
                    // Parse AI response for specific values
                    $answer = $aiData['answer'];
                    
                    // Extract numbers from AI response
                    $harvestDays = $this->extractNumberFromText($answer, ['harvest window', 'harvest period', 'harvesting period'], 60); // default 60 days for Brussels Sprouts
                    $successions = $this->extractNumberFromText($answer, ['succession', 'planting'], 3); // default 3 successions
                    $daysBetween = $this->extractNumberFromText($answer, ['between', 'interval', 'apart'], 21); // default 21 days
                    
                    Log::info('HuggingFace harvest window AI response', [
                        'crop' => $cropType,
                        'variety' => $variety,
                        'ai_answer' => $answer,
                        'extracted_harvest_days' => $harvestDays,
                        'extracted_successions' => $successions,
                        'extracted_interval' => $daysBetween
                    ]);

                    return [
                        'success' => true,
                        'optimal_harvest_days' => $harvestDays,
                        'recommended_successions' => $successions,
                        'days_between_plantings' => $daysBetween,
                        'peak_harvest_days' => $harvestDays,
                        'ai_confidence' => 'huggingface_llama',
                        'source' => 'HuggingFace Llama 3.1 8B',
                        'raw_answer' => $answer
                    ];
                }
            }

            // If we get here, the AI call failed
            Log::warning('HuggingFace harvest window analysis failed', [
                'crop' => $cropType,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return ['success' => false, 'error' => 'AI service unavailable'];

        } catch (\Exception $e) {
            Log::error('HuggingFace harvest window error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Extract numeric values from AI text response
     */
    private function extractNumberFromText(string $text, array $keywords, int $defaultValue): int
    {
        $text = strtolower($text);
        
        foreach ($keywords as $keyword) {
            // Look for patterns like "harvest window: 45 days" or "45 day harvest window"
            $pattern = '/(?:' . preg_quote($keyword, '/') . '.*?(\d+)|(\d+).*?' . preg_quote($keyword, '/') . ')/i';
            if (preg_match($pattern, $text, $matches)) {
                $number = !empty($matches[1]) ? intval($matches[1]) : intval($matches[2]);
                if ($number > 0 && $number < 500) { // Sanity check
                    return $number;
                }
            }
        }
        
        return $defaultValue;
    }

    /**
     * Get bed occupancy data for timeline visualization
     */
    public function getBedOccupancy(Request $request)
    {
        try {
            $startDate = $request->get('start_date', now()->startOfYear()->format('Y-m-d'));
            $endDate = $request->get('end_date', now()->endOfYear()->format('Y-m-d'));

            $data = $this->farmOSApi->getBedOccupancy($startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get bed occupancy data: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to load real bed occupancy data from FarmOS. Please check your FarmOS connection and credentials.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
