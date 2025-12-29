<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\BrandSetting;
use App\Models\VarietyAuditResult;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use App\Jobs\ProcessRagFile;

class SettingsController extends Controller
{
    /**
     * Display the settings page
     */
    public function index()
    {
        // Get current settings from database or defaults
        $settings = $this->getAllSettings();
        
        // Get pending variety audit results
        $auditResults = VarietyAuditResult::with('variety')
            ->where('status', 'pending')
            ->orderBy('severity', 'desc')
            ->orderBy('confidence', 'desc')
            ->take(100)
            ->get();
        
        $auditStats = [
            'total_pending' => VarietyAuditResult::where('status', 'pending')->count(),
            'critical' => VarietyAuditResult::where('status', 'pending')->where('severity', 'critical')->count(),
            'warning' => VarietyAuditResult::where('status', 'pending')->where('severity', 'warning')->count(),
            'high_confidence' => VarietyAuditResult::where('status', 'pending')->where('confidence', 'high')->count(),
        ];
        
        // Check if audit is currently running
        $auditRunning = false;
        $auditProgress = null;
        $pidFile = '/tmp/variety-audit.pid';
        $progressFile = storage_path('logs/variety-audit/progress.json');
        
        if (file_exists($pidFile)) {
            $pid = trim(file_get_contents($pidFile));
            $output = shell_exec("ps -p $pid -o pid= 2>/dev/null");
            $auditRunning = !empty(trim($output));
        }
        
        if (file_exists($progressFile)) {
            $auditProgress = json_decode(file_get_contents($progressFile), true);
        }
        
        // Check RAG ingestion progress
        $ragRunning = false;
        $ragProgress = null;
        $ragPidFile = storage_path('logs/rag-ingestion/process.pid');
        $ragProgressFile = storage_path('logs/rag-ingestion/progress.json');
        
        if (file_exists($ragPidFile)) {
            $pid = trim(file_get_contents($ragPidFile));
            $output = shell_exec("ps -p $pid -o pid= 2>/dev/null");
            $ragRunning = !empty(trim($output));
        }
        
        if (file_exists($ragProgressFile)) {
            $ragProgress = json_decode(file_get_contents($ragProgressFile), true);
        }
        
        // Check if queue worker is running for RAG processing
        $queueRunning = false;
        $queueOutput = shell_exec('ps aux | grep "queue:work" | grep -v grep');
        if (!empty(trim($queueOutput))) {
            $queueRunning = true;
            // If queue is running but no traditional RAG progress, show queue status
            if (!$ragRunning && !$ragProgress && !empty($queuedRagFiles)) {
                $jobsRemaining = DB::table('jobs')->count();
                $jobsCompleted = count($queuedRagFiles) - $jobsRemaining;
                $ragProgress = [
                    'status' => 'processing',
                    'processed_files' => $jobsCompleted,
                    'total_files' => count($queuedRagFiles),
                    'processed_chunks' => 0, // We don't track chunks for queue processing
                    'total_chunks' => 0,
                    'started_at' => now()->toDateTimeString(),
                    'current_file' => 'Processing queued files...',
                    'errors' => []
                ];
                $ragRunning = true;
            }
        }
        
        // Get queued RAG files - FIX THE TIMESTAMP ISSUE
        $queuedRagFiles = [];
        $queuedJobs = DB::table('jobs')->get();
        
        foreach ($queuedJobs as $job) {
            $payload = json_decode($job->payload, true);
            if (isset($payload['displayName']) && $payload['displayName'] === 'App\\Jobs\\ProcessRagFile') {
                $data = $payload['data'];
                if (isset($data['command'])) {
                    try {
                        $unserialized = unserialize($data['command']);
                        if ($unserialized instanceof \App\Jobs\ProcessRagFile) {
                            $fileInfo = $unserialized->getFileInfo();
                            
                            // Fix timestamp - use available_at which is actually populated
                            $queuedAt = $job->available_at ?? time();
                            
                            $queuedRagFiles[] = [
                                'id' => $job->id,
                                'filename' => $fileInfo['filename'],
                                'title' => $fileInfo['title'],
                                'queued_at' => date('Y-m-d H:i:s', $queuedAt),
                                'type' => strtoupper($fileInfo['extension']),
                                'file_id' => $fileInfo['fileId'] ?? null,
                                'progress' => [
                                    'status' => 'queued',
                                    'processed_chunks' => 0,
                                    'total_chunks' => 0
                                ]
                            ];
                        }
                    } catch (\Exception $e) {
                        Log::error("Error parsing job: " . $e->getMessage());
                    }
                }
            }
        }
        
        // Get branding settings
        $branding = BrandSetting::active();
        
        return view('admin.settings.index', compact('settings', 'auditResults', 'auditStats', 'auditRunning', 'auditProgress', 'ragRunning', 'ragProgress', 'queuedRagFiles', 'branding'));
    }
    
    /**
     * Update settings
     */
    public function update(Request $request)
    {
        // Minimal validation - just check required types, no strict value validation
        $validated = $request->validate([
            '*' => 'nullable', // Accept any field as nullable by default
        ]);
        
        // Store settings in database with defaults
        $settingsData = [
            // Company Information
            'company_type' => [
                'value' => $request->company_type,
                'type' => 'string',
                'description' => 'Type of business entity (CIC, Ltd, PLC, etc.)'
            ],
            'company_number' => [
                'value' => $request->company_number,
                'type' => 'string',
                'description' => 'Companies House registration number'
            ],
            'tax_year_end' => [
                'value' => $request->tax_year_end ?? '30-09',
                'type' => 'string',
                'description' => 'Tax year end date (MM-DD format)'
            ],
            'vat_registered' => [
                'value' => (bool) ($request->vat_registered ?? 0),
                'type' => 'boolean',
                'description' => 'Whether business is VAT registered'
            ],
            // Farm Season Settings
            'farm_name' => [
                'value' => $request->farm_name ?? 'Middle World Farms',
                'type' => 'string',
                'description' => 'Name of the farm/CSA operation'
            ],
            'season_start_date' => [
                'value' => $request->season_start_date,
                'type' => 'date',
                'description' => 'Start date of the growing/delivery season'
            ],
            'season_end_date' => [
                'value' => $request->season_end_date,
                'type' => 'date',
                'description' => 'End date of the growing/delivery season'
            ],
            'season_weeks' => [
                'value' => (int) ($request->season_weeks ?? 33),
                'type' => 'integer',
                'description' => 'Number of weeks in the season'
            ],
            'delivery_days' => [
                'value' => json_encode($request->delivery_days ?? ['Thursday']),
                'type' => 'json',
                'description' => 'Days of the week for deliveries'
            ],
            'fortnightly_week_a_start' => [
                'value' => $request->fortnightly_week_a_start,
                'type' => 'date',
                'description' => 'Reference start date for Week A (fortnightly schedule)'
            ],
            'closure_start_date' => [
                'value' => $request->closure_start_date,
                'type' => 'date',
                'description' => 'Start of seasonal closure period'
            ],
            'closure_end_date' => [
                'value' => $request->closure_end_date,
                'type' => 'date',
                'description' => 'End of seasonal closure period'
            ],
            'resume_billing_date' => [
                'value' => $request->resume_billing_date,
                'type' => 'date',
                'description' => 'Date to resume billing after closure'
            ],
            // Printing Settings
            'packing_slips_per_page' => [
                'value' => (int) ($request->packing_slips_per_page ?? 2),
                'type' => 'integer',
                'description' => 'Number of packing slips per printed page (1-6)'
            ],
            'auto_print_mode' => [
                'value' => (bool) ($request->has('auto_print_mode') ? 1 : 0),
                'type' => 'boolean',
                'description' => 'Skip print preview and send directly to printer'
            ],
            'print_company_logo' => [
                'value' => (bool) ($request->has('print_company_logo') ? 1 : 0),
                'type' => 'boolean',
                'description' => 'Include company logo on packing slips'
            ],
            'default_printer_paper_size' => [
                'value' => $request->default_printer_paper_size ?? 'A4',
                'type' => 'string',
                'description' => 'Default paper size for printing (A4 or Letter)'
            ],
            'enable_route_optimization' => [
                'value' => (bool) ($request->has('enable_route_optimization') ? 1 : 0),
                'type' => 'boolean',
                'description' => 'Enable route planning and optimization features'
            ],
            'delivery_time_slots' => [
                'value' => (bool) ($request->has('delivery_time_slots') ? 1 : 0),
                'type' => 'boolean',
                'description' => 'Enable delivery time slot selection'
            ],
            'delivery_cutoff_day' => [
                'value' => $request->delivery_cutoff_day ?? 'Thursday',
                'type' => 'string',
                'description' => 'Day of week for delivery cut-off'
            ],
            'delivery_cutoff_time' => [
                'value' => $request->delivery_cutoff_time ?? '10:00',
                'type' => 'string',
                'description' => 'Time of day for delivery cut-off'
            ],
            'collection_cutoff_day' => [
                'value' => $request->collection_cutoff_day ?? 'Friday',
                'type' => 'string',
                'description' => 'Day of week for collection cut-off'
            ],
            'collection_cutoff_time' => [
                'value' => $request->collection_cutoff_time ?? '12:00',
                'type' => 'string',
                'description' => 'Time of day for collection cut-off'
            ],
            'collection_reminder_hours' => [
                'value' => (int) ($request->collection_reminder_hours ?? 24),
                'type' => 'integer',
                'description' => 'Hours before collection to send reminder email'
            ],
            'email_notifications' => [
                'value' => (bool) ($request->has('email_notifications') ? 1 : 0),
                'type' => 'boolean',
                'description' => 'Enable email notifications for customers'
            ],
            'sms_notifications' => [
                'value' => (bool) ($request->has('sms_notifications') ? 1 : 0),
                'type' => 'boolean',
                'description' => 'Enable SMS notifications for customers'
            ],
            'sms_welcome_back_enabled' => [
                'value' => (bool) ($request->has('sms_welcome_back_enabled') ? 1 : 0),
                'type' => 'boolean',
                'description' => 'Enable welcome back SMS campaigns for lapsed customers'
            ],
            'sms_special_offers_enabled' => [
                'value' => (bool) ($request->has('sms_special_offers_enabled') ? 1 : 0),
                'type' => 'boolean',
                'description' => 'Enable special offer SMS campaigns'
            ],
            'solidarity_min_percent' => [
                'value' => (int) ($request->solidarity_min_percent ?? 70),
                'type' => 'integer',
                'description' => 'Default minimum percentage for solidarity pricing (% of recommended price)'
            ],
            'solidarity_max_percent' => [
                'value' => (int) ($request->solidarity_max_percent ?? 167),
                'type' => 'integer',
                'description' => 'Default maximum percentage for solidarity pricing (% of recommended price)'
            ],
            'email_client_enabled' => [
                'value' => (bool) ($request->has('email_client_enabled') ? 1 : 0),
                'type' => 'boolean',
                'description' => 'Enable email client functionality in admin panel'
            ],
            'email_auto_sync' => [
                'value' => (bool) ($request->has('email_auto_sync') ? 1 : 0),
                'type' => 'boolean',
                'description' => 'Automatically sync emails from inbox'
            ],
            'threecx_enable_tapi' => [
                'value' => (bool) ($request->has('threecx_enable_tapi') ? 1 : 0),
                'type' => 'boolean',
                'description' => 'Enable TAPI (Telephony Application Programming Interface) for desktop integration'
            ],
            'mwf_logging_enabled' => [
                'value' => (bool) ($request->has('mwf_logging_enabled') ? 1 : 0),
                'type' => 'boolean',
                'description' => 'Enable MWF integration transaction and error logging'
            ],
        ];
        
        // API keys are now managed in .env only - not stored in database
        // Self-hosters edit .env directly, hosted customers have it configured by admin
        
        // Add AI settings (non-encrypted)
        $aiSettings = [
            'ollama_primary_url' => $request->ollama_primary_url,
            'ollama_primary_model' => $request->ollama_primary_model,
            'ollama_primary_timeout' => $request->ollama_primary_timeout,
            'ollama_primary_enabled' => $request->has('ollama_primary_enabled') ? 1 : 0,
            'ollama_processing_url' => $request->ollama_processing_url,
            'ollama_processing_model' => $request->ollama_processing_model,
            'ollama_processing_timeout' => $request->ollama_processing_timeout,
            'ollama_processing_enabled' => $request->has('ollama_processing_enabled') ? 1 : 0,
            'ollama_rag_url' => $request->ollama_rag_url,
            'ollama_rag_model' => $request->ollama_rag_model,
            'ollama_rag_timeout' => $request->ollama_rag_timeout,
            'ollama_rag_enabled' => $request->has('ollama_rag_enabled') ? 1 : 0,
            'ai_chatbot_enabled' => $request->has('ai_chatbot_enabled') ? 1 : 0,
            'ai_succession_planner' => $request->has('ai_succession_planner') ? 1 : 0,
            'ai_harvest_planning' => $request->has('ai_harvest_planning') ? 1 : 0,
            'ai_crop_recommendations' => $request->has('ai_crop_recommendations') ? 1 : 0,
            'ai_data_analysis' => $request->has('ai_data_analysis') ? 1 : 0,
        ];
        
        foreach ($aiSettings as $key => $value) {
            if ($value !== null) {
                $type = is_bool($value) || in_array($key, ['ollama_primary_enabled', 'ollama_processing_enabled', 'ollama_rag_enabled', 'ai_chatbot_enabled', 'ai_succession_planner', 'ai_harvest_planning', 'ai_crop_recommendations', 'ai_data_analysis']) ? 'boolean' : (is_int($value) ? 'integer' : 'string');
                $settingsData[$key] = [
                    'value' => $value,
                    'type' => $type,
                    'description' => $this->getAiSettingDescription($key)
                ];
            }
        }
        
        // Add RAG settings (non-encrypted)
        $ragSettings = [
            'rag_ingestion_enabled' => $request->has('rag_ingestion_enabled') ? 1 : 0,
            'rag_watch_directory' => $request->rag_watch_directory,
            'rag_processed_directory' => $request->rag_processed_directory,
            'rag_chunk_size' => $request->rag_chunk_size,
            'rag_chunk_overlap' => $request->rag_chunk_overlap,
            'rag_supported_formats' => $request->rag_supported_formats,
            'rag_embedding_model' => $request->rag_embedding_model,
            'rag_ingestion_schedule' => $request->rag_ingestion_schedule,
        ];
        
        foreach ($ragSettings as $key => $value) {
            if ($value !== null) {
                $type = is_bool($value) || $key === 'rag_ingestion_enabled' ? 'boolean' : (is_int($value) ? 'integer' : 'string');
                $settingsData[$key] = [
                    'value' => $value,
                    'type' => $type,
                    'description' => $this->getRagSettingDescription($key)
                ];
            }
        }
        
        // Add POS settings (non-encrypted)
        $posSettings = [
            'pos_card_reader_type' => $request->pos_card_reader_type ?? 'manual',
            'pos_stripe_publishable_key' => $request->pos_stripe_publishable_key ?? '',
            'pos_stripe_location_id' => $request->pos_stripe_location_id ?? '',
            'pos_currency' => $request->pos_currency ?? 'gbp',
            'pos_email_receipts' => $request->has('pos_email_receipts') ? 1 : 0,
            'pos_require_customer_email' => $request->has('pos_require_customer_email') ? 1 : 0,
        ];
        
        foreach ($posSettings as $key => $value) {
            if ($value !== null) {
                $type = is_bool($value) || in_array($key, [
                    'pos_email_receipts', 'pos_require_customer_email'
                ]) ? 'boolean' : (is_int($value) ? 'integer' : 'string');
                $settingsData[$key] = [
                    'value' => $value,
                    'type' => $type,
                    'description' => $this->getPosSettingDescription($key)
                ];
            }
        }
        
        // Handle branding settings
        if ($request->has('brand_company_name')) {
            $brandData = [
                'company_name' => $request->brand_company_name,
                'tagline' => $request->brand_tagline,
                'primary_color' => $request->brand_primary_color,
                'secondary_color' => $request->brand_secondary_color,
                'accent_color' => $request->brand_accent_color,
                'contact_email' => $request->brand_contact_email,
                'contact_phone' => $request->brand_contact_phone,
                'address' => $request->brand_address,
                'logo_alt_text' => $request->brand_logo_alt_text,
                'social_links' => [
                    'facebook' => $request->brand_social_facebook,
                    'instagram' => $request->brand_social_instagram,
                    'twitter' => $request->brand_social_twitter,
                ],
            ];
            
            // Handle logo uploads
            if ($request->hasFile('brand_logo_main')) {
                $brandData['logo_path'] = $request->file('brand_logo_main')->store('brand/logos', 'public');
            }
            if ($request->hasFile('brand_logo_small')) {
                $brandData['logo_small_path'] = $request->file('brand_logo_small')->store('brand/logos', 'public');
            }
            if ($request->hasFile('brand_logo_white')) {
                $brandData['logo_white_path'] = $request->file('brand_logo_white')->store('brand/logos', 'public');
            }
            
            // Get existing branding or create new
            $branding = BrandSetting::active();
            if ($branding) {
                $branding->update($brandData);
            } else {
                BrandSetting::create(array_merge($brandData, ['is_active' => true]));
            }
            
            // Clear branding cache after update
            Cache::forget('active_branding');
            Cache::forget('branding_css_variables');
        }
        
        Setting::setMultiple($settingsData);
        
        // Sync season settings to farmOS if configured
        if ($request->filled('season_start_date') || $request->filled('season_end_date') || $request->filled('season_weeks')) {
            $this->syncSeasonToFarmOS($request);
        }
        
        return redirect()->route('admin.settings')->with('success', 'Settings and API keys updated successfully!');
    }
    
    /**
     * Reset settings to defaults
     */
    public function reset()
    {
        // Reset all settings to defaults by storing the default values in database
        $defaults = $this->getDefaultSettingsWithTypes();
        Setting::setMultiple($defaults);
        
        return redirect()->route('admin.settings')->with('success', 'Settings reset to defaults successfully and saved to database!');
    }
    
    /**
     * Get API endpoint for settings (for JavaScript)
     */
    public function api()
    {
        $settings = $this->getAllSettings();
        
        return response()->json([
            'success' => true,
            'settings' => $settings
        ]);
    }
    
    /**
     * Get default settings
     */
    private function getDefaultSettings()
    {
        return [
            // Farm/Season Settings
            'farm_name' => 'Middle World Farms',
            'season_start_date' => '2025-04-01',     // Season start date
            'season_end_date' => '2025-11-30',       // Season end date
            'season_weeks' => 33,                     // Number of weeks in season
            'closure_start_date' => null,            // Optional closure period start
            'closure_end_date' => null,              // Optional closure period end
            'resume_billing_date' => null,           // Date to resume billing after closure
            'delivery_days' => ['Thursday'],         // Default delivery days
            'fortnightly_week_a_start' => null,      // Start date for Week A (fortnightly)
            
            // Printing Settings
            'packing_slips_per_page' => 1,           // 1-6 slips per page
            'auto_print_mode' => true,               // Skip preview, direct to printer
            'print_company_logo' => true,            // Include farm logo on slips
            'default_printer_paper_size' => 'A4',   // A4 or Letter
            'enable_route_optimization' => true,     // Enable route planning features
            'delivery_time_slots' => false,         // Enable time slot selection
            'delivery_cutoff_time' => '10:00',      // 10am Thursday cut-off for deliveries
            'collection_cutoff_time' => '12:00',    // 12 noon Friday cut-off for collections
            'collection_reminder_hours' => 24,      // Hours before collection to send reminder
            'email_notifications' => true,          // Send email notifications
            'sms_notifications' => false,           // SMS notifications (off by default)
            'sms_welcome_back_enabled' => false,    // Welcome back SMS campaigns
            'sms_special_offers_enabled' => false,  // Special offer SMS campaigns
            'updated_at' => now()->toISOString(),
        ];
    }
    
    /**
     * Get all settings from database or defaults
     */
    private function getAllSettings()
    {
        $defaults = $this->getDefaultSettings();
        $dbSettings = Setting::getAll();
        
        // Merge defaults with database settings, preferring database values
        $settings = array_merge($defaults, $dbSettings);
        
        // Add decrypted API keys to settings
        $apiKeys = self::getAllApiKeys();
        $settings = array_merge($settings, $apiKeys);
        
        // Ensure updated_at is set
        if (!isset($settings['updated_at'])) {
            $settings['updated_at'] = now()->toISOString();
        }
        
        return $settings;
    }
    
    /**
     * Get default settings with type information for database storage
     */
    private function getDefaultSettingsWithTypes()
    {
        return [
            // Farm/Season Settings
            'farm_name' => [
                'value' => 'Middle World Farms',
                'type' => 'string',
                'description' => 'Name of the farm/CSA operation'
            ],
            'season_start_date' => [
                'value' => '2025-04-01',
                'type' => 'date',
                'description' => 'Start date of the growing/delivery season'
            ],
            'season_end_date' => [
                'value' => '2025-11-30',
                'type' => 'date',
                'description' => 'End date of the growing/delivery season'
            ],
            'season_weeks' => [
                'value' => 33,
                'type' => 'integer',
                'description' => 'Number of weeks in the season'
            ],
            'closure_start_date' => [
                'value' => null,
                'type' => 'date',
                'description' => 'Start of seasonal closure period (optional)'
            ],
            'closure_end_date' => [
                'value' => null,
                'type' => 'date',
                'description' => 'End of seasonal closure period (optional)'
            ],
            'resume_billing_date' => [
                'value' => null,
                'type' => 'date',
                'description' => 'Date to resume billing after closure'
            ],
            'delivery_days' => [
                'value' => ['Thursday'],
                'type' => 'array',
                'description' => 'Days of the week for deliveries'
            ],
            'fortnightly_week_a_start' => [
                'value' => null,
                'type' => 'date',
                'description' => 'Start date for Week A (fortnightly schedule)'
            ],
            
            // Printing Settings
            'packing_slips_per_page' => [
                'value' => 1,
                'type' => 'integer',
                'description' => 'Number of packing slips per printed page (1-6)'
            ],
            'auto_print_mode' => [
                'value' => true,
                'type' => 'boolean',
                'description' => 'Skip print preview and send directly to printer'
            ],
            'print_company_logo' => [
                'value' => true,
                'type' => 'boolean',
                'description' => 'Include company logo on packing slips'
            ],
            'default_printer_paper_size' => [
                'value' => 'A4',
                'type' => 'string',
                'description' => 'Default paper size for printing (A4 or Letter)'
            ],
            'enable_route_optimization' => [
                'value' => true,
                'type' => 'boolean',
                'description' => 'Enable route planning and optimization features'
            ],
            'delivery_time_slots' => [
                'value' => false,
                'type' => 'boolean',
                'description' => 'Enable delivery time slot selection'
            ],
            'delivery_cutoff_time' => [
                'value' => '10:00',
                'type' => 'string',
                'description' => 'Cut-off time for deliveries (Thursday)'
            ],
            'collection_cutoff_time' => [
                'value' => '12:00',
                'type' => 'string',
                'description' => 'Cut-off time for collections (Friday)'
            ],
            'collection_reminder_hours' => [
                'value' => 24,
                'type' => 'integer',
                'description' => 'Hours before collection to send reminder email'
            ],
            'email_notifications' => [
                'value' => true,
                'type' => 'boolean',
                'description' => 'Enable email notifications for customers'
            ],
            'sms_notifications' => [
                'value' => false,
                'type' => 'boolean',
                'description' => 'Enable SMS notifications for customers'
            ],
            'sms_welcome_back_enabled' => [
                'value' => false,
                'type' => 'boolean',
                'description' => 'Enable welcome back SMS campaigns for lapsed customers'
            ],
            'sms_special_offers_enabled' => [
                'value' => false,
                'type' => 'boolean',
                'description' => 'Enable special offer SMS campaigns'
            ],
            'email_client_enabled' => [
                'value' => true,
                'type' => 'boolean',
                'description' => 'Enable email client functionality in admin panel'
            ],
            'email_auto_sync' => [
                'value' => false,
                'type' => 'boolean',
                'description' => 'Automatically sync emails from inbox'
            ],
        ];
    }
    
    /**
     * Get server performance metrics for monitoring IONOS I/O throttling
     */
    public function serverMetrics()
    {
        try {
            // Add debug logging
            \Log::info('Server metrics requested', [
                'session_authenticated' => Session::get('admin_authenticated', false),
                'session_id' => session()->getId(),
                'ip' => request()->ip()
            ]);
            
            $metrics = [
                'cpu_usage' => $this->getCpuUsage(),
                'memory_usage' => $this->getMemoryUsage(),
                'disk_io_speed' => $this->getDiskIOSpeed(),
                'load_average' => $this->getLoadAverage(),
                'response_time' => $this->getAverageResponseTime(),
                'timestamp' => now()->toISOString(),
                'debug_info' => [
                    'authenticated' => Session::get('admin_authenticated', false),
                    'session_id' => substr(session()->getId(), 0, 8) . '...'
                ]
            ];
            
            return response()->json([
                'success' => true,
                'metrics' => $metrics
            ]);
        } catch (\Exception $e) {
            \Log::error('Server metrics error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Test disk I/O speed to detect IONOS throttling
     */
    public function testIOSpeed()
    {
        try {
            $testFileSize = 10 * 1024 * 1024; // 10MB test file
            $testData = str_repeat('A', $testFileSize);
            $testFile = storage_path('logs/io_speed_test.tmp');
            
            // Test write speed
            $writeStart = microtime(true);
            file_put_contents($testFile, $testData);
            $writeEnd = microtime(true);
            $writeTime = $writeEnd - $writeStart;
            $writeSpeed = round(($testFileSize / 1024 / 1024) / $writeTime, 2);
            
            // Test read speed
            $readStart = microtime(true);
            $readData = file_get_contents($testFile);
            $readEnd = microtime(true);
            $readTime = $readEnd - $readStart;
            $readSpeed = round(($testFileSize / 1024 / 1024) / $readTime, 2);
            
            // Clean up
            @unlink($testFile);
            
            return response()->json([
                'success' => true,
                'write_speed' => $writeSpeed,
                'read_speed' => $readSpeed,
                'test_file_size' => '10MB',
                'write_time' => round($writeTime * 1000, 2) . 'ms',
                'read_time' => round($readTime * 1000, 2) . 'ms'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Test database performance
     */
    public function testDatabasePerformance()
    {
        try {
            $startTime = microtime(true);
            
            // Test connection time
            $connectionStart = microtime(true);
            \DB::connection()->getPdo();
            $connectionTime = round((microtime(true) - $connectionStart) * 1000, 2);
            
            // Test simple queries
            $queryStart = microtime(true);
            $testQueries = 10;
            
            for ($i = 0; $i < $testQueries; $i++) {
                \DB::select('SELECT 1 as test');
            }
            
            $queryEnd = microtime(true);
            $totalQueryTime = round(($queryEnd - $queryStart) * 1000, 2);
            $avgQueryTime = round($totalQueryTime / $testQueries, 2);
            
            return response()->json([
                'success' => true,
                'connection_time' => $connectionTime,
                'query_time' => $totalQueryTime,
                'test_queries' => $testQueries,
                'avg_query_time' => $avgQueryTime
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get CPU usage percentage
     */
    private function getCpuUsage()
    {
        try {
            // Method 1: Try reading from /proc/stat (Linux)
            if (file_exists('/proc/stat')) {
                static $lastCpuStats = null;
                static $lastTime = null;
                
                $currentTime = microtime(true);
                $stat = file_get_contents('/proc/stat');
                
                if (preg_match('/^cpu\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/m', $stat, $matches)) {
                    $user = $matches[1];
                    $nice = $matches[2];
                    $system = $matches[3];
                    $idle = $matches[4];
                    $iowait = isset($matches[5]) ? $matches[5] : 0;
                    
                    $total = $user + $nice + $system + $idle + $iowait;
                    $currentStats = ['total' => $total, 'idle' => $idle];
                    
                    // If we have previous stats, calculate usage
                    if ($lastCpuStats !== null && $lastTime !== null) {
                        $totalDiff = $currentStats['total'] - $lastCpuStats['total'];
                        $idleDiff = $currentStats['idle'] - $lastCpuStats['idle'];
                        
                        if ($totalDiff > 0) {
                            $usage = (($totalDiff - $idleDiff) / $totalDiff) * 100;
                            $lastCpuStats = $currentStats;
                            $lastTime = $currentTime;
                            return round($usage, 1);
                        }
                    }
                    
                    // Store current stats for next call
                    $lastCpuStats = $currentStats;
                    $lastTime = $currentTime;
                }
            }
            
            // Method 2: Use load average as fallback
            if (function_exists('sys_getloadavg')) {
                $load = sys_getloadavg();
                $cpuCount = $this->getCpuCount();
                if ($cpuCount > 0) {
                    return round(($load[0] / $cpuCount) * 100, 1);
                }
            }
            
            return 0;
        } catch (\Exception $e) {
            \Log::warning('CPU usage detection failed: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get memory usage percentage (IONOS hosting compatible)
     */
    private function getMemoryUsage()
    {
        try {
            // Use PHP's memory functions (these work on shared hosting)
            $memoryLimit = $this->convertToBytes(ini_get('memory_limit'));
            $memoryUsed = memory_get_usage(true);
            
            if ($memoryLimit > 0) {
                return round(($memoryUsed / $memoryLimit) * 100, 1);
            }
            
            // If no memory limit is set, estimate based on peak usage
            $peakMemory = memory_get_peak_usage(true);
            $currentMemory = memory_get_usage(true);
            
            // Assume reasonable default limits based on hosting
            $estimatedLimit = 512 * 1024 * 1024; // 512MB default for shared hosting
            
            return round(($currentMemory / $estimatedLimit) * 100, 1);
            
        } catch (\Exception $e) {
            \Log::warning('Memory usage detection failed: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get disk I/O speed (IONOS hosting compatible)
     */
    private function getDiskIOSpeed()
    {
        try {
            // Use a smaller test size for shared hosting
            $testSize = 256 * 1024; // 256KB instead of 1MB
            $testData = str_repeat('X', $testSize);
            
            // Use storage path which should be within allowed paths
            $testFile = storage_path('app/io_test_' . uniqid() . '.tmp');
            
            $start = microtime(true);
            
            // Test write speed
            $writeStart = microtime(true);
            if (file_put_contents($testFile, $testData) === false) {
                throw new \Exception('Write test failed');
            }
            $writeTime = microtime(true) - $writeStart;
            
            // Test read speed
            $readStart = microtime(true);
            if (file_get_contents($testFile) === false) {
                throw new \Exception('Read test failed');
            }
            $readTime = microtime(true) - $readStart;
            
            // Clean up
            @unlink($testFile);
            
            $avgTime = ($writeTime + $readTime) / 2;
            $speed = ($testSize / 1024 / 1024) / $avgTime; // MB/s
            
            return round($speed, 1);
            
        } catch (\Exception $e) {
            \Log::warning('Disk I/O speed test failed: ' . $e->getMessage());
            // Return a reasonable estimate for shared hosting
            return 50.0; // Typical shared hosting I/O speed
        }
    }
    
    /**
     * Get system load average
     */
    private function getLoadAverage()
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return round($load[0], 2);
        }
        
        // Linux fallback
        if (file_exists('/proc/loadavg')) {
            $load = file_get_contents('/proc/loadavg');
            $load = explode(' ', $load);
            return round((float)$load[0], 2);
        }
        
        return 0;
    }
    
    /**
     * Get average response time (estimated)
     */
    private function getAverageResponseTime()
    {
        // Measure current request processing time
        if (defined('LARAVEL_START')) {
            $currentTime = (microtime(true) - LARAVEL_START) * 1000;
            return round($currentTime, 2);
        }
        
        return 0;
    }
    
    /**
     * Get CPU core count
     */
    private function getCpuCount()
    {
        if (file_exists('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            preg_match_all('/^processor/m', $cpuinfo, $matches);
            return count($matches[0]);
        }
        
        return 1; // Default fallback
    }
    
    /**
     * Convert memory string to bytes
     */
    private function convertToBytes($value)
    {
        if (is_numeric($value)) {
            return $value;
        }
        
        $unit = strtolower(substr($value, -1));
        $value = (int) $value;
        
        switch ($unit) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
    
    /**
     * Encrypt API key for secure storage
     */
    private function encryptApiKey(string $key): string
    {
        return encrypt($key);
    }
    
    /**
     * Decrypt API key for use
     */
    private function decryptApiKey(string $encryptedKey): string
    {
        try {
            return decrypt($encryptedKey);
        } catch (\Exception $e) {
            // If decryption fails, return empty string
            return '';
        }
    }
    
    /**
     * Get API key description
     */
    private function getApiKeyDescription(string $key): string
    {
        $descriptions = [
            'farmos_api_url' => 'FarmOS server URL (e.g., https://farmos.middleworldfarms.org)',
            'farmos_username' => 'FarmOS admin username for API authentication',
            'farmos_password' => 'FarmOS admin password for API authentication',
            'farmos_oauth_client_id' => 'FarmOS OAuth2 client ID for API access',
            'farmos_oauth_client_secret' => 'FarmOS OAuth2 client secret for API access',
            'woocommerce_consumer_key' => 'WooCommerce REST API consumer key',
            'woocommerce_consumer_secret' => 'WooCommerce REST API consumer secret',
            'mwf_api_key' => 'Middle World Farms integration API key',
            'google_maps_api_key' => 'Google Maps JavaScript API key',
            'met_office_api_key' => 'UK Met Office Weather API key',
            'met_office_land_observations_key' => 'Met Office Land Observations API key for soil moisture and temperature data',
            'met_office_site_specific_key' => 'Met Office Site-Specific Forecast API key for detailed local weather',
            'met_office_atmospheric_key' => 'Met Office Atmospheric Models API key for weather model data',
            'met_office_map_images_key' => 'Met Office Map Images API key for weather radar and satellite imagery',
            'openweather_api_key' => 'OpenWeatherMap API key',
            'huggingface_api_key' => 'Hugging Face Inference API key',
            'claude_api_key' => 'Anthropic Claude API key for AI assistance',
            'stripe_key' => 'Stripe publishable key (pk_...)',
            'stripe_secret' => 'Stripe secret key (sk_...)',
            // 3CX Phone System
            'threecx_server_url' => '3CX server URL (e.g., https://pineappletelecoms2.3cx.uk:5001)',
            'threecx_extension' => '3CX extension number',
            'threecx_did' => '3CX DID (Direct Inward Dialing) public phone number',
            'threecx_mobile' => 'Mobile phone number linked to 3CX',
            'threecx_username' => '3CX username/friendly name for Talk and Meet URLs',
            'threecx_crm_url' => 'CRM integration URL to open when receiving calls - use {phone} placeholder for caller number',
            // Twilio SMS API
            'twilio_sid' => 'Twilio Account SID (AC...)',
            'twilio_token' => 'Twilio Auth Token',
            'twilio_from' => 'Twilio phone number (+44XXXXXXXXXX)',
            // IMAP Email Client
            'imap_host' => 'IMAP server hostname (e.g., imap.gmail.com)',
            'imap_port' => 'IMAP server port (993 for SSL, 143 for non-SSL)',
            'imap_username' => 'IMAP username/email address',
            'imap_password' => 'IMAP password or app password',
            'imap_encryption' => 'IMAP encryption type (ssl, tls, or none)',
            'smtp_host' => 'SMTP server hostname (e.g., smtp.gmail.com)',
            'smtp_port' => 'SMTP server port (465 for SSL, 587 for TLS)',
            'smtp_username' => 'SMTP username/email address',
            'smtp_password' => 'SMTP password or app password',
            'smtp_encryption' => 'SMTP encryption type (ssl, tls, or none)',
        ];
        
        return $descriptions[$key] ?? 'API key for external service integration';
    }
    
    /**
     * Get AI setting description
     */
    private function getAiSettingDescription(string $key): string
    {
        $descriptions = [
            'ollama_primary_url' => 'Primary Ollama instance URL for fast chatbot responses',
            'ollama_primary_model' => 'Model name for primary Ollama instance (e.g., phi3)',
            'ollama_primary_timeout' => 'Request timeout in seconds for primary instance',
            'ollama_primary_enabled' => 'Enable primary Ollama instance',
            'ollama_processing_url' => 'Processing Ollama instance URL for quality analysis',
            'ollama_processing_model' => 'Model name for processing instance (e.g., mistral:7b)',
            'ollama_processing_timeout' => 'Request timeout in seconds for processing instance',
            'ollama_processing_enabled' => 'Enable processing Ollama instance',
            'ollama_rag_url' => 'RAG Ollama instance URL for document-based responses',
            'ollama_rag_model' => 'Model name for RAG instance',
            'ollama_rag_timeout' => 'Request timeout in seconds for RAG instance',
            'ollama_rag_enabled' => 'Enable RAG Ollama instance',
            'ai_chatbot_enabled' => 'Enable AI chatbot feature',
            'ai_succession_planner' => 'Enable AI succession planner feature',
            'ai_harvest_planning' => 'Enable AI harvest planning feature',
            'ai_crop_recommendations' => 'Enable AI crop recommendation feature',
            'ai_data_analysis' => 'Enable AI data analysis feature',
        ];
        
        return $descriptions[$key] ?? 'AI system setting';
    }
    
    /**
     * Get RAG setting description
     */
    private function getRagSettingDescription(string $key): string
    {
        $descriptions = [
            'rag_ingestion_enabled' => 'Enable automatic RAG document ingestion',
            'rag_watch_directory' => 'Directory to watch for new documents to ingest',
            'rag_processed_directory' => 'Directory to move processed documents',
            'rag_chunk_size' => 'Size of text chunks for embedding (in characters)',
            'rag_chunk_overlap' => 'Overlap between chunks (in characters)',
            'rag_supported_formats' => 'Comma-separated list of supported file formats',
            'rag_embedding_model' => 'Model name for generating embeddings',
            'rag_ingestion_schedule' => 'Schedule for automatic ingestion (cron format)',
        ];
        
        return $descriptions[$key] ?? 'RAG ingestion setting';
    }
    
    /**
     * Get POS setting description
     */
    private function getPosSettingDescription(string $key): string
    {
        $descriptions = [
            // Card reader settings
            'pos_card_reader_type' => 'Type of card reader (manual, stripe_terminal)',
            'pos_stripe_publishable_key' => 'Stripe publishable key for terminal integration',
            'pos_stripe_location_id' => 'Stripe location ID for terminal',
            'pos_currency' => 'POS currency (gbp, usd, eur)',
            
            // Email receipt settings
            'pos_email_receipts' => 'Send receipts via email instead of printing',
            'pos_require_customer_email' => 'Require customer email address for transactions',
        ];
        
        return $descriptions[$key] ?? 'POS hardware setting';
    }
    
    /**
     * Get decrypted API key from database
     */
    public static function getApiKey(string $key): string
    {
        $encryptedKey = Setting::get($key, '');
        if (empty($encryptedKey)) {
            return '';
        }
        
        try {
            return decrypt($encryptedKey);
        } catch (\Exception $e) {
            return '';
        }
    }
    
    /**
     * Get all API keys as decrypted values
     */
    public static function getAllApiKeys(): array
    {
        $apiKeyFields = [
            'farmos_api_url',
            'farmos_username',
            'farmos_password', 
            'farmos_oauth_client_id',
            'farmos_oauth_client_secret',
            'woocommerce_consumer_key',
            'woocommerce_consumer_secret',
            'mwf_api_key',
            'google_maps_api_key',
            'met_office_api_key',
            'met_office_land_observations_key',
            'met_office_site_specific_key',
            'met_office_atmospheric_key',
            'met_office_map_images_key',
            'openweather_api_key',
            'huggingface_api_key',
            'claude_api_key',
            'stripe_key',
            'stripe_secret',
            // 3CX Phone System
            'threecx_server_url',
            'threecx_extension',
            'threecx_did',
            'threecx_mobile',
            'threecx_username',
            'threecx_crm_url',
            // Twilio SMS API
            'twilio_sid',
            'twilio_token',
            'twilio_from',
            // IMAP Email Client
            'imap_host',
            'imap_port',
            'imap_username',
            'imap_password',
            'imap_encryption',
            'smtp_host',
            'smtp_port',
            'smtp_username',
            'smtp_password',
            'smtp_encryption',
        ];
        
        $apiKeys = [];
        foreach ($apiKeyFields as $field) {
            // Try database first, then fall back to .env
            $value = self::getApiKey($field);
            if (empty($value)) {
                // Fallback to .env for FarmOS settings
                $envKey = strtoupper($field);
                $value = env($envKey, '');
            }
            $apiKeys[$field] = $value;
        }
        
        return $apiKeys;
    }
    
    // ============================================
    // VARIETY AUDIT REVIEW METHODS
    // ============================================
    
    /**
     * Approve a single audit suggestion
     */
    public function approveAudit($id)
    {
        $result = VarietyAuditResult::findOrFail($id);
        $result->status = 'approved';
        $result->save();
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Update the suggested value for an audit result
     */
    public function updateSuggestion(Request $request, $id)
    {
        $result = VarietyAuditResult::findOrFail($id);
        $result->suggested_value = $request->input('suggested_value');
        $result->save();
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Reject a single audit suggestion
     */
    public function rejectAudit($id)
    {
        $result = VarietyAuditResult::findOrFail($id);
        $result->status = 'rejected';
        $result->save();
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Bulk approve audit suggestions
     */
    public function bulkApproveAudit(Request $request)
    {
        $ids = $request->input('ids', []);
        $count = VarietyAuditResult::whereIn('id', $ids)->update(['status' => 'approved']);
        
        return response()->json(['success' => true, 'count' => $count]);
    }
    
    /**
     * Bulk reject audit suggestions
     */
    public function bulkRejectAudit(Request $request)
    {
        $ids = $request->input('ids', []);
        $count = VarietyAuditResult::whereIn('id', $ids)->update(['status' => 'rejected']);
        
        return response()->json(['success' => true, 'count' => $count]);
    }
    
    /**
     * Approve all high confidence suggestions
     */
    public function approveHighConfidence()
    {
        $count = VarietyAuditResult::where('status', 'pending')
            ->where('confidence', 'high')
            ->update(['status' => 'approved']);
        
        return response()->json(['success' => true, 'count' => $count]);
    }
    
    /**
     * Apply all approved suggestions to the database
     */
    public function applyApprovedAudit()
    {
        $approved = VarietyAuditResult::with('variety')
            ->where('status', 'approved')
            ->whereNotNull('suggested_field')
            ->get();
        
        $count = 0;
        foreach ($approved as $result) {
            try {
                // Map field names
                $fieldMap = [
                    'maturityDays' => 'maturity_days',
                    'harvestDays' => 'harvest_days',
                    'inRowSpacing' => 'in_row_spacing_cm',
                    'betweenRowSpacing' => 'between_row_spacing_cm',
                    'plantingMethod' => 'planting_method',
                    'description' => 'description',
                    'harvestNotes' => 'harvest_notes',
                ];
                
                $dbField = $fieldMap[$result->suggested_field] ?? $result->suggested_field;
                
                // Update the variety
                $result->variety->$dbField = $result->suggested_value;
                $result->variety->save();
                
                // Mark as applied
                $result->status = 'applied';
                $result->applied_at = now();
                $result->save();
                
                $count++;
            } catch (\Exception $e) {
                \Log::error("Failed to apply audit result {$result->id}: " . $e->getMessage());
            }
        }
        
        return response()->json(['success' => true, 'count' => $count]);
    }
    
    /**
     * Get current audit stats
     */
    public function auditStats()
    {
        return response()->json([
            'total_pending' => VarietyAuditResult::where('status', 'pending')->count(),
            'critical' => VarietyAuditResult::where('status', 'pending')->where('severity', 'critical')->count(),
            'warning' => VarietyAuditResult::where('status', 'pending')->where('severity', 'warning')->count(),
            'high_confidence' => VarietyAuditResult::where('status', 'pending')->where('confidence', 'high')->count(),
        ]);
    }
    
    /**
     * Get audit status (running, paused, etc.)
     */
    public function auditStatus()
    {
        $pidFile = '/tmp/variety-audit.pid';
        $progressFile = storage_path('logs/variety-audit/progress.json');
        
        $isRunning = false;
        $progress = null;
        
        // Check if process is running
        if (file_exists($pidFile)) {
            $pid = trim(file_get_contents($pidFile));
            // Check if PID exists
            $output = shell_exec("ps -p $pid -o pid= 2>/dev/null");
            $isRunning = !empty(trim($output));
        }
        
        // Get DB-based progress (most reliable)
        $dbMaxVarietyId = \App\Models\VarietyAuditResult::max('variety_id');
        $dbDistinctCount = \App\Models\VarietyAuditResult::distinct()->count('variety_id');
        
        // Check for saved progress file
        if (file_exists($progressFile)) {
            $progress = json_decode(file_get_contents($progressFile), true);
            
            // Validate against DB - if mismatch, use DB values
            $fileLastId = $progress['last_processed_id'] ?? 0;
            $fileProcessed = $progress['processed'] ?? 0;
            
            if ($dbMaxVarietyId && abs($fileLastId - $dbMaxVarietyId) > 10) {
                // Progress file is out of sync - use DB values instead
                $progress['last_processed_id'] = $dbMaxVarietyId;
                $progress['processed'] = $dbDistinctCount;
                $progress['source'] = 'database'; // Flag for UI
            }
        } elseif ($dbMaxVarietyId) {
            // No progress file but DB has data - create progress from DB
            $progress = [
                'last_processed_id' => $dbMaxVarietyId,
                'processed' => $dbDistinctCount,
                'timestamp' => now()->toDateTimeString(),
                'source' => 'database'
            ];
        }
        
        $total = 2959;
        $processed = $progress['processed'] ?? 0;
        $percent = $total > 0 ? round(($processed / $total) * 100, 1) : 0;
        
        return response()->json([
            'is_running' => $isRunning,
            'can_resume' => !$isRunning && $progress !== null,
            'processed' => $processed,
            'total' => $total,
            'progress_percent' => $percent,
            'last_id' => $progress['last_processed_id'] ?? null,
            'timestamp' => $progress['timestamp'] ?? null,
            'avg_time' => 60, // Average seconds per variety
            'source' => $progress['source'] ?? 'file', // Show where data came from
        ]);
    }
    
    /**
     * Start the audit
     */
    public function auditStart()
    {
        $pidFile = '/tmp/variety-audit.pid';
        
        // Check if already running
        if (file_exists($pidFile)) {
            $pid = trim(file_get_contents($pidFile));
            $output = shell_exec("ps -p $pid -o pid= 2>/dev/null");
            if (!empty(trim($output))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Audit is already running'
                ]);
            }
        }
        
        // Start the audit in background
        $command = 'cd ' . base_path() . ' && nohup php artisan varieties:audit > /tmp/variety-audit.log 2>&1 & echo $!';
        $pid = trim(shell_exec($command));
        
        if ($pid) {
            file_put_contents($pidFile, $pid);
            return response()->json(['success' => true, 'pid' => $pid]);
        }
        
        return response()->json(['success' => false, 'message' => 'Failed to start audit']);
    }
    
    /**
     * Pause the audit
     */
    public function auditPause()
    {
        $pidFile = '/tmp/variety-audit.pid';
        
        if (!file_exists($pidFile)) {
            return response()->json(['success' => false, 'message' => 'No audit is running']);
        }
        
        $pid = trim(file_get_contents($pidFile));
        
        // Send SIGTERM to gracefully stop
        shell_exec("kill -SIGTERM $pid 2>/dev/null");
        sleep(2);
        
        // Check if still running, force kill if necessary
        $output = shell_exec("ps -p $pid -o pid= 2>/dev/null");
        if (!empty(trim($output))) {
            shell_exec("kill -9 $pid 2>/dev/null");
        }
        
        unlink($pidFile);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Resume the audit
     */
    public function auditResume()
    {
        return $this->auditStart(); // Same as start, will auto-resume from progress file
    }
    
    /**
     * Start RAG ingestion process
     */
    public function ragStart(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'rows_per_chunk' => 'nullable|integer|min:1|max:100'
        ]);
        
        $ragPidFile = storage_path('logs/rag-ingestion/process.pid');
        
        // Check if already running
        if (file_exists($ragPidFile)) {
            $pid = trim(file_get_contents($ragPidFile));
            $output = shell_exec("ps -p $pid -o pid= 2>/dev/null");
            if (!empty(trim($output))) {
                return response()->json(['success' => false, 'message' => 'RAG ingestion is already running']);
            }
        }
        
        $path = $request->path;
        $rowsPerChunk = $request->rows_per_chunk ?? 10;
        
        // Start the ingestion in background
        $command = "php artisan rag:ingest-csv \"{$path}\" --rows-per-chunk={$rowsPerChunk} --background";
        shell_exec("{$command} > /dev/null 2>&1 &");
        
        return response()->json(['success' => true, 'message' => 'RAG ingestion started']);
    }
    
    /**
     * Stop RAG ingestion process
     */
    public function ragStop()
    {
        $ragPidFile = storage_path('logs/rag-ingestion/process.pid');
        
        if (!file_exists($ragPidFile)) {
            return response()->json(['success' => false, 'message' => 'No RAG ingestion is running']);
        }
        
        $pid = trim(file_get_contents($ragPidFile));
        
        // Send SIGTERM to gracefully stop
        shell_exec("kill -SIGTERM $pid 2>/dev/null");
        sleep(2);
        
        // Check if still running, force kill if necessary
        $output = shell_exec("ps -p $pid -o pid= 2>/dev/null");
        if (!empty(trim($output))) {
            shell_exec("kill -9 $pid 2>/dev/null");
        }
        
        // Clean up files
        if (file_exists($ragPidFile)) {
            unlink($ragPidFile);
        }
        
        $progressFile = storage_path('logs/rag-ingestion/progress.json');
        if (file_exists($progressFile)) {
            $progress = json_decode(file_get_contents($progressFile), true);
            $progress['status'] = 'stopped';
            $progress['stopped_at'] = now()->toDateTimeString();
            file_put_contents($progressFile, json_encode($progress, JSON_PRETTY_PRINT));
        }
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Start queue processing for RAG files
     */
    public function startQueueProcessing()
    {
        // Check if queue worker is already running
        $output = shell_exec('ps aux | grep "queue:work" | grep -v grep');
        if (!empty(trim($output))) {
            return response()->json(['success' => false, 'message' => 'Queue worker is already running']);
        }
        
        // Check if there are jobs in the queue
        $jobCount = DB::table('jobs')->count();
        if ($jobCount === 0) {
            return response()->json(['success' => false, 'message' => 'No jobs in the queue to process']);
        }
        
        // Start the queue worker in the background
        $command = 'cd ' . base_path() . ' && nohup php artisan queue:work --tries=3 --timeout=90 --sleep=3 --max-jobs=1000 > /dev/null 2>&1 &';
        exec($command);
        
        // Give it a moment to start
        sleep(2);
        
        // Verify it started
        $verifyOutput = shell_exec('ps aux | grep "queue:work" | grep -v grep');
        if (empty(trim($verifyOutput))) {
            return response()->json(['success' => false, 'message' => 'Failed to start queue worker']);
        }
        
        return response()->json([
            'success' => true, 
            'message' => 'Queue processing started successfully',
            'jobs_queued' => $jobCount
        ]);
    }
    
    /**
     * Upload and process RAG files
     */
    public function ragUpload(Request $request)
    {
        Log::info('RAG Upload initiated', [
            'has_files' => $request->hasFile('files'),
            'files_count' => $request->hasFile('files') ? count($request->file('files')) : 0,
            'rows_per_chunk' => $request->rows_per_chunk
        ]);

        $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'file|mimes:csv,txt,pdf|max:512000', // 500MB max per file
            'rows_per_chunk' => 'nullable|integer|min:1|max:100'
        ]);

        Log::info('RAG Upload validation passed');

        $rowsPerChunk = $request->rows_per_chunk ?? 10;
        $uploadedPaths = [];

        // Create upload directory in public storage
        $uploadSubDir = 'rag-uploads/' . date('Y-m-d');
        $uploadDir = storage_path('app/public/' . $uploadSubDir);
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
            chown($uploadDir, 'www-data');
            chgrp($uploadDir, 'www-data');
        }

        // Handle all files
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                try {
                    $filename = time() . '_' . $file->getClientOriginalName();
                    
                    // Log file upload attempt
                    Log::info('Attempting to store file', [
                        'filename' => $filename,
                        'size' => $file->getSize(),
                        'mime' => $file->getMimeType(),
                        'temp_path' => $file->getPathname(),
                        'dest_dir' => $uploadSubDir
                    ]);
                    
                    $storedPath = $file->storeAs('public/' . $uploadSubDir, $filename);
                    
                    if ($storedPath === false) {
                        Log::error('storeAs() returned false', [
                            'filename' => $filename,
                            'dest_dir' => 'public/' . $uploadSubDir
                        ]);
                        continue;
                    }
                    
                    Log::info('storeAs() returned path', [
                        'filename' => $filename,
                        'stored_path' => $storedPath
                    ]);
                    
                    $fullPath = storage_path('app/' . $storedPath);
                    
                    // Verify file was actually stored
                    if (!file_exists($fullPath)) {
                        Log::error('File not found after upload', [
                            'filename' => $filename,
                            'expected_path' => $fullPath,
                            'stored_path' => $storedPath,
                            'temp_file_exists' => file_exists($file->getPathname()),
                            'temp_file_size' => file_exists($file->getPathname()) ? filesize($file->getPathname()) : 0,
                            'dest_dir_writable' => is_writable($uploadDir),
                            'dest_dir_exists' => is_dir($uploadDir)
                        ]);
                        
                        // Try manual move as fallback
                        $manualPath = $uploadDir . '/' . $filename;
                        if (move_uploaded_file($file->getPathname(), $manualPath)) {
                            Log::info('Manual move succeeded', ['path' => $manualPath]);
                            $uploadedPaths[] = $manualPath;
                            chmod($manualPath, 0664);
                            continue;
                        } else {
                            Log::error('Manual move also failed');
                            continue;
                        }
                    }
                    
                    $uploadedPaths[] = $fullPath;
                    Log::info('File uploaded successfully', [
                        'filename' => $filename,
                        'path' => $fullPath,
                        'size' => filesize($fullPath)
                    ]);
                } catch (\Exception $e) {
                    Log::error('Exception during file upload', [
                        'filename' => $file->getClientOriginalName(),
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        if (empty($uploadedPaths)) {
            return response()->json(['success' => false, 'message' => 'No files uploaded']);
        }

        // Create progress tracking file
        $progressDir = storage_path('logs/rag-ingestion');
        if (!is_dir($progressDir)) {
            mkdir($progressDir, 0775, true);
            chown($progressDir, 'www-data');
            chgrp($progressDir, 'www-data');
        }
        $progressFile = $progressDir . '/progress_' . time() . '.json';

        // Initialize progress tracking
        $progress = [
            'status' => 'queued',
            'started_at' => now()->toDateTimeString(),
            'total_files' => count($uploadedPaths),
            'processed_files' => 0,
            'total_chunks' => 0,
            'processed_chunks' => 0,
            'current_file' => null,
            'current_chunk' => 0,
            'errors' => [],
            'queued_jobs' => count($uploadedPaths)
        ];
        file_put_contents($progressFile, json_encode($progress, JSON_PRETTY_PRINT));
        chown($progressFile, 'www-data');
        chgrp($progressFile, 'www-data');
        chmod($progressFile, 0664);

        // Dispatch jobs for each file
        foreach ($uploadedPaths as $filePath) {
            $title = pathinfo($filePath, PATHINFO_FILENAME);
            ProcessRagFile::dispatch($filePath, $title, $rowsPerChunk, $progressFile);
        }

        return response()->json([
            'success' => true,
            'message' => 'Files uploaded and queued for processing',
            'files_count' => count($uploadedPaths),
            'progress_file' => basename($progressFile)
        ]);
    }

    /**
     * Get list of RAG documents with their processing status
     */
    public function ragDocuments()
    {
        try {
            $watchDir = storage_path('app/rag/documents');
            $processedDir = storage_path('app/rag/processed');
            
            // Also check existing RAG upload directories
            $existingUploadsDir = storage_path('app/public/rag-uploads');
            $privateUploadsDir = storage_path('app/private/public/rag-uploads');
            
            // Create directories if they don't exist
            if (!is_dir($watchDir)) {
                mkdir($watchDir, 0755, true);
            }
            if (!is_dir($processedDir)) {
                mkdir($processedDir, 0755, true);
            }
            
            $pending = [];
            $processed = [];
            
            // Get pending documents from watch directory
            if (is_dir($watchDir)) {
                $files = array_diff(scandir($watchDir), ['.', '..']);
                foreach ($files as $file) {
                    $filePath = $watchDir . '/' . $file;
                    if (is_file($filePath)) {
                        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        $pending[] = [
                            'name' => $file,
                            'size' => filesize($filePath),
                            'extension' => $extension,
                            'created_at' => date('Y-m-d H:i:s', filectime($filePath)),
                        ];
                    }
                }
            }
            
            // Helper function to scan a directory recursively
            $scanDirectory = function($dir) use (&$processed) {
                if (!is_dir($dir)) return;
                
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::SELF_FIRST
                );
                
                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $extension = strtolower($file->getExtension());
                        if (in_array($extension, ['pdf', 'txt', 'docx', 'doc', 'md'])) {
                            $fileName = $file->getFilename();
                            
                            // Remove timestamp prefix if present (e.g., "1761347279_")
                            $displayName = preg_replace('/^\d+_/', '', $fileName);
                            
                            // Try to get chunk count from database
                            $chunks = null;
                            $fileNameBase = pathinfo($fileName, PATHINFO_FILENAME);
                            
                            // Query the rag_documents table if it exists
                            try {
                                $docRecord = DB::table('rag_documents')
                                    ->where('filename', 'like', '%' . $displayName . '%')
                                    ->orWhere('filename', 'like', '%' . $fileNameBase . '%')
                                    ->first();
                                if ($docRecord) {
                                    $chunks = DB::table('rag_chunks')
                                        ->where('document_id', $docRecord->id)
                                        ->count();
                                }
                            } catch (\Exception $e) {
                                // Table might not exist yet
                            }
                            
                            $processed[] = [
                                'name' => $displayName,
                                'size' => $file->getSize(),
                                'extension' => $extension,
                                'processed_at' => date('Y-m-d H:i:s', $file->getMTime()),
                                'chunks' => $chunks,
                                'path' => $file->getPathname(),
                            ];
                        }
                    }
                }
            };
            
            // Scan processed directory
            $scanDirectory($processedDir);
            
            // Scan existing RAG upload directories
            $scanDirectory($existingUploadsDir);
            $scanDirectory($privateUploadsDir);
            
            // Remove duplicates based on name
            $processed = array_values(array_reduce($processed, function($carry, $item) {
                $carry[$item['name']] = $item;
                return $carry;
            }, []));
            
            // Sort by date (newest first)
            usort($pending, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            usort($processed, function($a, $b) {
                return strtotime($b['processed_at']) - strtotime($a['processed_at']);
            });
            
            return response()->json([
                'success' => true,
                'documents' => [
                    'pending' => $pending,
                    'processed' => $processed,
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching RAG documents: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch documents: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add files to queue for processing
     */
    public function addToQueue(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
            'rows_per_chunk' => 'nullable|integer|min:1|max:100'
        ]);

        $path = $request->path;
        $rowsPerChunk = $request->rows_per_chunk ?? 10;

        // Check if path exists
        if (!file_exists($path) && !is_dir($path)) {
            return response()->json(['success' => false, 'message' => 'Path does not exist: ' . $path]);
        }

        $files = [];
        if (is_dir($path)) {
            // Get all CSV and PDF files from directory
            $csvFiles = glob($path . '/*.csv');
            $pdfFiles = glob($path . '/*.pdf');
            $files = array_merge($csvFiles, $pdfFiles);
        } else {
            // Single file
            $files = [$path];
        }

        if (empty($files)) {
            return response()->json(['success' => false, 'message' => 'No CSV or PDF files found at: ' . $path]);
        }

        // Create progress tracking file
        $progressDir = storage_path('logs/rag-ingestion');
        if (!is_dir($progressDir)) {
            mkdir($progressDir, 0775, true);
            chown($progressDir, 'www-data');
            chgrp($progressDir, 'www-data');
        }
        $progressFile = $progressDir . '/progress_' . time() . '.json';

        // Initialize progress tracking
        $progress = [
            'status' => 'queued',
            'started_at' => now()->toDateTimeString(),
            'total_files' => count($files),
            'processed_files' => 0,
            'total_chunks' => 0,
            'processed_chunks' => 0,
            'current_file' => null,
            'current_chunk' => 0,
            'errors' => [],
            'queued_jobs' => count($files)
        ];
        file_put_contents($progressFile, json_encode($progress, JSON_PRETTY_PRINT));
        chown($progressFile, 'www-data');
        chgrp($progressFile, 'www-data');
        chmod($progressFile, 0664);

        // Dispatch jobs for each file
        foreach ($files as $filePath) {
            $title = pathinfo($filePath, PATHINFO_FILENAME);
            ProcessRagFile::dispatch($filePath, $title, $rowsPerChunk, $progressFile);
        }

        return response()->json([
            'success' => true,
            'message' => 'Files added to queue successfully',
            'files_added' => count($files),
            'progress_file' => basename($progressFile)
        ]);
    }

    /**
     * Pause queue processing
     */
    public function pauseQueueProcessing()
    {
        // Find and kill the queue worker process
        $output = shell_exec('ps aux | grep "queue:work" | grep -v grep');
        if (empty(trim($output))) {
            return response()->json(['success' => false, 'message' => 'No queue worker is currently running']);
        }

        // Kill the queue worker
        exec('pkill -f "queue:work"');

        // Give it a moment to stop
        sleep(1);

        // Verify it stopped
        $verifyOutput = shell_exec('ps aux | grep "queue:work" | grep -v grep');
        if (!empty(trim($verifyOutput))) {
            return response()->json(['success' => false, 'message' => 'Failed to pause queue worker']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Queue processing paused successfully'
        ]);
    }

    /**
     * Resume queue processing
     */
    public function resumeQueueProcessing()
    {
        // Check if queue worker is already running
        $output = shell_exec('ps aux | grep "queue:work" | grep -v grep');
        if (!empty(trim($output))) {
            return response()->json(['success' => false, 'message' => 'Queue worker is already running']);
        }

        // Check if there are jobs in the queue
        $jobCount = DB::table('jobs')->count();
        if ($jobCount === 0) {
            return response()->json(['success' => false, 'message' => 'No jobs in the queue to process']);
        }

        // Start the queue worker in the background
        $command = 'cd ' . base_path() . ' && nohup php artisan queue:work --tries=3 --timeout=90 --sleep=3 --max-jobs=1000 > /dev/null 2>&1 &';
        exec($command);

        // Give it a moment to start
        sleep(2);

        // Verify it started
        $verifyOutput = shell_exec('ps aux | grep "queue:work" | grep -v grep');
        if (empty(trim($verifyOutput))) {
            return response()->json(['success' => false, 'message' => 'Failed to resume queue worker']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Queue processing resumed successfully',
            'jobs_queued' => $jobCount
        ]);
    }

    /**
     * Get RAG processing status
     */
    public function ragStatus()
    {
        $ragProgressFile = storage_path('logs/rag-ingestion/progress.json');
        $ragProgress = [];
        
        if (file_exists($ragProgressFile)) {
            $ragProgress = json_decode(file_get_contents($ragProgressFile), true) ?? [];
        }

        // Get actual queue status
        $queuedCount = DB::table('jobs')->count();
        $failedCount = DB::table('failed_jobs')->count();
        
        // Check if queue worker is running
        $isProcessing = false;
        $output = [];
        exec("ps aux | grep 'queue:work' | grep -v grep", $output);
        $isProcessing = !empty($output);

        // Get queued files with progress
        $queuedFiles = [];
        $queuedJobs = DB::table('jobs')->get();
        
        foreach ($queuedJobs as $job) {
            $payload = json_decode($job->payload, true);
            if (isset($payload['displayName']) && $payload['displayName'] === 'App\\Jobs\\ProcessRagFile') {
                try {
                    $data = $payload['data'];
                    $unserialized = unserialize($data['command']);
                    if ($unserialized instanceof \App\Jobs\ProcessRagFile) {
                        $fileInfo = $unserialized->getFileInfo();
                        $fileId = $fileInfo['fileId'] ?? null;
                        
                        // Get progress for this specific file
                        $fileProgress = [
                            'status' => 'queued',
                            'processed_chunks' => 0,
                            'total_chunks' => 0
                        ];
                        
                        if ($fileId && isset($ragProgress['files'][$fileId])) {
                            $fileProgress = $ragProgress['files'][$fileId];
                        }
                        
                        $queuedFiles[] = [
                            'id' => $job->id,
                            'filename' => $fileInfo['filename'],
                            'title' => $fileInfo['title'],
                            'queued_at' => date('Y-m-d H:i:s', $job->available_at ?? time()),
                            'type' => strtoupper($fileInfo['extension']),
                            'file_id' => $fileId,
                            'progress' => $fileProgress
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error("Error parsing job for status: " . $e->getMessage());
                }
            }
        }

        return response()->json([
            'status' => $isProcessing ? 'processing' : ($queuedCount > 0 ? 'queued' : 'stopped'),
            'queued_count' => $queuedCount,
            'failed_count' => $failedCount,
            'is_processing' => $isProcessing,
            'current_file' => $ragProgress['current_file'] ?? null,
            'processed_chunks' => $ragProgress['processed_chunks'] ?? 0,
            'total_chunks' => $ragProgress['total_chunks'] ?? 0,
            'processed_files' => $ragProgress['processed_files'] ?? 0,
            'queued_files' => $queuedFiles,
        ]);
    }

    /**
     * Get list of imported datasets
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDatasets()
    {
        try {
            $databaseName = config('database.connections.mysql.database');
            
            // Query information_schema to get all tables starting with 'ds_'
            $tables = DB::select("
                SELECT 
                    TABLE_NAME as table_name,
                    TABLE_ROWS as row_count,
                    (SELECT COUNT(*) 
                     FROM information_schema.COLUMNS 
                     WHERE TABLE_SCHEMA = ? 
                     AND TABLE_NAME = t.TABLE_NAME) as column_count,
                    CREATE_TIME as created_at,
                    UPDATE_TIME as updated_at
                FROM information_schema.TABLES t
                WHERE TABLE_SCHEMA = ?
                AND TABLE_NAME LIKE 'ds_%'
                ORDER BY TABLE_NAME
            ", [$databaseName, $databaseName]);
            
            // Get accurate row counts for each table (TABLE_ROWS is approximate)
            $datasets = [];
            foreach ($tables as $table) {
                $actualCount = DB::table($table->table_name)->count();
                
                $datasets[] = [
                    'table_name' => $table->table_name,
                    'row_count' => $actualCount,
                    'column_count' => $table->column_count,
                    'created_at' => $table->created_at,
                    'updated_at' => $table->updated_at,
                ];
            }
            
            return response()->json([
                'success' => true,
                'datasets' => $datasets,
                'count' => count($datasets),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching datasets: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch datasets: ' . $e->getMessage(),
                'datasets' => [],
            ], 500);
        }
    }

    /**
     * Delete a dataset table
     * 
     * @param string $tableName
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteDataset($tableName)
    {
        try {
            // Security: Only allow deletion of tables starting with 'ds_'
            if (!str_starts_with($tableName, 'ds_')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid table name. Only dataset tables (starting with ds_) can be deleted.',
                ], 403);
            }
            
            // Security: Validate table name format (alphanumeric and underscores only)
            if (!preg_match('/^ds_[a-zA-Z0-9_]+$/', $tableName)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid table name format.',
                ], 400);
            }
            
            // Check if table exists
            $databaseName = config('database.connections.mysql.database');
            $exists = DB::select("
                SELECT COUNT(*) as count 
                FROM information_schema.TABLES 
                WHERE TABLE_SCHEMA = ? 
                AND TABLE_NAME = ?
            ", [$databaseName, $tableName]);
            
            if ($exists[0]->count == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dataset table not found.',
                ], 404);
            }
            
            // Drop the table
            DB::statement("DROP TABLE IF EXISTS `{$tableName}`");
            
            Log::info("Dataset table deleted: {$tableName}");
            
            return response()->json([
                'success' => true,
                'message' => "Dataset {$tableName} has been deleted successfully.",
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error deleting dataset {$tableName}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete dataset: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync season settings to farmOS
     * Creates or updates a "Season Plan" in farmOS with the configured dates
     * 
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    private function syncSeasonToFarmOS($request)
    {
        try {
            $farmosApi = app(\App\Services\FarmOSApi::class);
            
            $farmName = $request->farm_name ?? 'Middle World Farms';
            $seasonStart = $request->season_start_date;
            $seasonEnd = $request->season_end_date;
            $seasonWeeks = $request->season_weeks ?? 33;
            
            // Build the plan name and notes
            $planName = $farmName . ' - Season Configuration Plan';
            
            $notes = "**Growing Season Configuration**\n\n";
            $notes .= "- **Start Date:** " . ($seasonStart ? \Carbon\Carbon::parse($seasonStart)->format('F j, Y') : 'Not set') . "\n";
            $notes .= "- **End Date:** " . ($seasonEnd ? \Carbon\Carbon::parse($seasonEnd)->format('F j, Y') : 'Not set') . "\n";
            $notes .= "- **Season Length:** {$seasonWeeks} weeks\n\n";
            
            if ($request->filled('delivery_days')) {
                $deliveryDays = is_array($request->delivery_days) ? implode(', ', $request->delivery_days) : $request->delivery_days;
                $notes .= "- **Delivery Days:** {$deliveryDays}\n";
            }
            
            if ($request->filled('closure_start_date') && $request->filled('closure_end_date')) {
                $closureStart = \Carbon\Carbon::parse($request->closure_start_date)->format('F j, Y');
                $closureEnd = \Carbon\Carbon::parse($request->closure_end_date)->format('F j, Y');
                $notes .= "\n**Seasonal Closure:**\n";
                $notes .= "- **Closure Period:** {$closureStart} - {$closureEnd}\n";
                
                if ($request->filled('resume_billing_date')) {
                    $resumeDate = \Carbon\Carbon::parse($request->resume_billing_date)->format('F j, Y');
                    $notes .= "- **Resume Billing:** {$resumeDate}\n";
                }
            }
            
            $notes .= "\n*Last updated: " . now()->format('F j, Y \a\t g:i A') . "*";
            $notes .= "\n*Synced from admin settings panel.*";
            
            // Check if a Season Configuration plan already exists
            $existingPlans = $farmosApi->getCropPlans(['name' => 'Season Configuration']);
            
            if (!empty($existingPlans)) {
                // Update the existing plan
                $existingPlan = $existingPlans[0];
                $planId = $existingPlan['id'];
                
                $updateData = [
                    'name' => $planName,
                    'notes' => $notes,
                    'status' => 'active'
                ];
                
                $farmosApi->updateCropPlan($planId, $updateData);
                
                Log::info('Season settings updated in farmOS', [
                    'farm_name' => $farmName,
                    'season_start' => $seasonStart,
                    'season_end' => $seasonEnd,
                    'season_weeks' => $seasonWeeks,
                    'plan_id' => $planId,
                    'action' => 'updated'
                ]);
                
            } else {
                // Create a new plan
                $planData = [
                    'crop' => ['name' => $farmName],
                    'type' => 'Season Configuration',
                    'notes' => $notes,
                    'status' => 'active'
                ];
                
                $result = $farmosApi->createCropPlan($planData);
                
                Log::info('Season settings synced to farmOS', [
                    'farm_name' => $farmName,
                    'season_start' => $seasonStart,
                    'season_end' => $seasonEnd,
                    'season_weeks' => $seasonWeeks,
                    'plan_id' => $result['data']['id'] ?? 'unknown',
                    'action' => 'created'
                ]);
            }
            
        } catch (\Exception $e) {
            // Log the error but don't fail the settings save
            Log::warning('Failed to sync season settings to farmOS: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Test farmOS connection (both API and Direct DB)
     */
    public function testFarmOSConnection(Request $request)
    {
        $results = [
            'api' => ['status' => 'untested', 'message' => ''],
            'database' => ['status' => 'untested', 'message' => ''],
        ];
        
        // Test API Connection
        try {
            $farmosService = app(\App\Services\FarmOSApi::class);
            
            // Authenticate first
            if (!$farmosService->authenticate()) {
                throw new \Exception('Authentication failed');
            }
            
            // Try to get plant types as a simple test
            $plantTypes = $farmosService->getPlantTypes();
            
            if ($plantTypes) {
                $results['api'] = [
                    'status' => 'success',
                    'message' => 'API connection successful',
                    'plant_types' => count($plantTypes),
                    'auth_method' => config('services.farmos.client_id') ? 'OAuth2' : 'Basic Auth'
                ];
            } else {
                $results['api'] = [
                    'status' => 'error',
                    'message' => 'No data returned from API'
                ];
            }
        } catch (\Exception $e) {
            $results['api'] = [
                'status' => 'error',
                'message' => 'API Error: ' . $e->getMessage()
            ];
        }
        
        // Test Direct Database Connection
        try {
            // Try to query farmOS database directly
            $varietyCount = DB::connection('farmos')
                ->table('taxonomy_term_field_data')
                ->where('vid', 'plant_type')
                ->count();
            
            $bedCount = DB::connection('farmos')
                ->table('asset__land_type')
                ->where('land_type_value', 'bed')
                ->count();
            
            $results['database'] = [
                'status' => 'success',
                'message' => 'Direct database connection successful',
                'varieties' => $varietyCount,
                'beds' => $bedCount
            ];
        } catch (\Exception $e) {
            $results['database'] = [
                'status' => 'error',
                'message' => 'Database Error: ' . $e->getMessage()
            ];
        }
        
        // Determine overall success
        $overallSuccess = $results['api']['status'] === 'success' || $results['database']['status'] === 'success';
        
        return response()->json([
            'success' => $overallSuccess,
            'message' => $overallSuccess ? 'farmOS connection verified' : 'farmOS connection failed',
            'results' => $results
        ], $overallSuccess ? 200 : 500);
    }
}
