<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PlantVariety;
use App\Models\VarietyAuditResult;
use App\Services\AI\SymbiosisAIService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AuditVarieties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'varieties:audit 
                            {--start-id= : Start from specific variety ID}
                            {--limit= : Limit number of varieties to process}
                            {--fix : Automatically fix issues where possible}
                            {--dry-run : Show what would be done without making changes}
                            {--category= : Only process specific category (e.g., "broad bean", "lettuce")}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'AI-powered audit of all plant varieties - validates data, flags issues, and optionally fixes them';

    protected $ai;
    protected $logFile;
    protected $issuesFile;
    protected $fixedFile;
    protected $startTime;
    protected $stats = [
        'total' => 0,
        'processed' => 0,
        'skipped' => 0,
        'issues_found' => 0,
        'auto_fixed' => 0,
        'needs_review' => 0,
        'errors' => 0
    ];

    public function __construct(SymbiosisAIService $ai)
    {
        parent::__construct();
        $this->ai = $ai;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->startTime = now();
        $timestamp = $this->startTime->format('Y-m-d_H-i-s');
        
        // Setup log files
        $logDir = storage_path('logs/variety-audit');
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $this->logFile = $logDir . "/audit_{$timestamp}.log";
        $this->issuesFile = $logDir . "/issues_{$timestamp}.log";
        $this->fixedFile = $logDir . "/fixed_{$timestamp}.log";
        
        $this->info("🔍 Starting AI Variety Audit");
        $this->info("📊 Log files:");
        $this->info("   Main: {$this->logFile}");
        $this->info("   Issues: {$this->issuesFile}");
        $this->info("   Fixed: {$this->fixedFile}");
        $this->newLine();
        
        // Check for existing progress - USE DB AS SOURCE OF TRUTH
        $progressFile = storage_path('logs/variety-audit/progress.json');
        $resumeFromId = null;
        
        // Get actual progress from database (most reliable source)
        $dbMaxVarietyId = VarietyAuditResult::max('variety_id');
        $dbDistinctCount = VarietyAuditResult::distinct()->count('variety_id');
        
        if (file_exists($progressFile) && !$this->option('start-id')) {
            $progress = json_decode(file_get_contents($progressFile), true);
            
            // Validate progress file against DB
            $fileProcessed = $progress['processed'] ?? 0;
            $fileLastId = $progress['last_processed_id'] ?? 0;
            
            // If progress file and DB don't match, use DB values
            if ($dbMaxVarietyId && abs($fileLastId - $dbMaxVarietyId) > 10) {
                $this->warn("⚠️  Progress file out of sync with database!");
                $this->info("   File shows: last ID {$fileLastId}, processed {$fileProcessed}");
                $this->info("   DB shows: max ID {$dbMaxVarietyId}, distinct varieties {$dbDistinctCount}");
                $this->info("   Using DATABASE values (more reliable)");
                $progress['last_processed_id'] = $dbMaxVarietyId;
                $progress['processed'] = $dbDistinctCount;
                $progress['timestamp'] = now()->format('Y-m-d H:i:s');
            }
            
            $this->warn("⏸️  Previous audit found!");
            $this->info("   Last processed: Variety ID {$progress['last_processed_id']}");
            $this->info("   Processed: {$progress['processed']} varieties");
            if (isset($progress['timestamp'])) {
                $this->info("   Timestamp: {$progress['timestamp']}");
            }
            $this->newLine();
            
            if ($this->confirm('Resume from where you left off?', true)) {
                $resumeFromId = $progress['last_processed_id'] + 1;
                $this->info("▶️  Resuming from ID: {$resumeFromId}");
            } else {
                $this->info("🔄 Starting fresh audit");
                unlink($progressFile);
            }
            $this->newLine();
        } elseif ($dbMaxVarietyId && !$this->option('start-id')) {
            // No progress file but DB has results - offer to resume from DB
            $this->warn("⏸️  No progress file but database shows previous audit!");
            $this->info("   DB shows: max variety ID {$dbMaxVarietyId}");
            $this->info("   Processed: {$dbDistinctCount} distinct varieties");
            $this->newLine();
            
            if ($this->confirm('Resume from database state?', true)) {
                $resumeFromId = $dbMaxVarietyId + 1;
                $this->info("▶️  Resuming from ID: {$resumeFromId}");
                $this->newLine();
            }
        }
        
        // Build query - process varieties starting from specified ID or resume point
        $query = PlantVariety::query();
        
        // Use start-id if specified, otherwise start from resume point or beginning
        if ($startId = $this->option('start-id')) {
            $query->where('id', '>=', $startId);
            $this->info("▶️  Starting from ID: {$startId}");
        } elseif ($resumeFromId) {
            $query->where('id', '>=', $resumeFromId);
            $this->info("▶️  Resuming from ID: {$resumeFromId}");
        }
        
        if ($category = $this->option('category')) {
            $query->where(function($q) use ($category) {
                $q->where('name', 'LIKE', "%{$category}%")
                  ->orWhere('plant_type', 'LIKE', "%{$category}%")
                  ->orWhere('crop_family', 'LIKE', "%{$category}%");
            });
            $this->info("🔎 Category filter: {$category}");
        }
        
        if ($limit = $this->option('limit')) {
            $query->limit($limit);
            $this->info("⏱️  Processing limit: {$limit} varieties");
        }
        
        // Count remaining varieties to process (for progress bar)
        $remainingCount = $query->count();
        
        // Set total to FULL variety count
        $this->stats['total'] = PlantVariety::count();
        
        // Get actual count of already processed varieties from database
        $this->stats['processed'] = VarietyAuditResult::distinct('variety_id')->count();
        
        $this->info("📦 Total varieties: {$this->stats['total']}");
        $this->info("✅ Already audited: {$this->stats['processed']}");
        $this->info("🎯 To be audited: " . ($this->stats['total'] - $this->stats['processed']));
        $this->newLine();
        
        if ($this->option('dry-run')) {
            $this->warn("🔍 DRY RUN MODE - No changes will be saved");
            $this->newLine();
        }
        
        $progressBar = $this->output->createProgressBar($this->stats['total']);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        
        // If resuming, advance progress bar to current position
        if ($resumeFromId && $this->stats['processed'] > 0) {
            $progressBar->setProgress($this->stats['processed']);
        }
        
        // Log start
        $this->log("=== AI VARIETY AUDIT STARTED ===");
        $this->log("Time: " . $this->startTime->toDateTimeString());
        $this->log("Total varieties: {$this->stats['total']}");
        $this->log("Options: " . json_encode($this->options()));
        $this->log("");
        
        // Process each variety
        $progressFile = storage_path('logs/variety-audit/progress.json');
        foreach ($query->cursor() as $variety) {
            try {
                $this->processVariety($variety);

                // Only count as processed if we actually did work on it
                $wasSkipped = VarietyAuditResult::where('variety_id', $variety->id)->exists();
                if (!$wasSkipped) {
                    $this->stats['processed']++;

                    // Save progress every 10 varieties for resume capability
                    if ($this->stats['processed'] % 10 == 0) {
                        file_put_contents($progressFile, json_encode([
                            'last_processed_id' => $variety->id,
                            'processed' => $this->stats['processed'],
                            'timestamp' => now()->toDateTimeString(),
                            'stats' => $this->stats
                        ]));
                    }
                } else {
                    $this->stats['skipped']++;
                }
            } catch (\Exception $e) {
                $this->stats['errors']++;
                $this->logError($variety, "Processing error: " . $e->getMessage());
                Log::error("Variety audit error for ID {$variety->id}: " . $e->getMessage());
            }
            
            $progressBar->advance();
            
            // Small delay to avoid overwhelming AI service
            usleep(100000); // 0.1 second delay
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        // Final summary
        $this->showSummary();
        
        return Command::SUCCESS;
    }

    protected function processVariety($variety)
    {
        // Skip if already audited to avoid wasting API calls
        $existingAudit = VarietyAuditResult::where('variety_id', $variety->id)->exists();
        if ($existingAudit) {
            $this->log("⏭️  SKIPPED: [{$variety->id}] {$variety->name} (already audited)");
            return;
        }

        $this->log("Processing: [{$variety->id}] {$variety->name}");
        
        // Build AI prompt for this variety
        $prompt = $this->buildAuditPrompt($variety);
        
        // Get AI analysis
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are an expert horticulturist auditing plant variety data for UK growing conditions. You MUST respond ONLY with valid JSON. No explanatory text before or after the JSON.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];
        
        try {
            // Add extra logging for debugging
            Log::info("Audit variety {$variety->id}: Calling AI", [
                'variety_id' => $variety->id,
                'variety_name' => $variety->name,
                'messages_count' => count($messages)
            ]);
            
            // Use Claude API (Anthropic) for highest quality results
            // Falls back to Mistral if no API key configured
            $claudeApiKey = env('CLAUDE_API_KEY');
            
            if ($claudeApiKey && $claudeApiKey !== 'your_claude_api_key_here') {
                // Claude Sonnet 4 - latest available model (verified working)
                $response = $this->ai->chat($messages, [
                    'max_tokens' => 1024,
                    'temperature' => 0.1,
                    'model' => 'claude-sonnet-4-20250514',
                    'provider' => 'anthropic',
                    'api_key' => $claudeApiKey
                ]);
            } else {
                // Fallback to local Mistral 7B on port 8006
                $this->warn("No Claude API key found - using Mistral 7B (lower quality)");
                $response = $this->ai->chat($messages, [
                    'max_tokens' => 800, 
                    'temperature' => 0.1,
                    'model' => 'mistral:7b',
                    'base_url' => 'http://localhost:8006/api'
                ]);
            }
            
            if (!isset($response['choices'][0]['message']['content'])) {
                throw new \Exception('Invalid AI response structure: ' . json_encode($response));
            }
            
            $analysis = $response['choices'][0]['message']['content'];
            
            // Parse AI response and take action
            $this->processAIResponse($variety, $analysis);
            
        } catch (\Exception $e) {
            $errorMsg = "AI request failed: " . $e->getMessage();
            $this->logError($variety, $errorMsg);
            $this->stats['errors']++;
            
            // Careful logging to avoid "Array to string conversion" errors
            try {
                Log::error("Variety audit error for ID {$variety->id}", [
                    'variety_id' => $variety->id,
                    'variety_name' => $variety->name,
                    'error_message' => $e->getMessage(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine()
                ]);
            } catch (\Exception $logError) {
                // Silently fail logging if it causes issues
            }
        }
    }

    protected function buildAuditPrompt($variety): string
    {
        // Helper to safely convert values to string
        $safeString = function($value) {
            if (is_array($value) || is_object($value)) {
                return json_encode($value);
            }
            return $value ?: 'MISSING';
        };
        
        $prompt = "Audit this plant variety data:\n\n";
        $prompt .= "ID: {$variety->id}\n";
        $prompt .= "Name: " . $safeString($variety->name) . "\n";
        $prompt .= "Plant Type: " . $safeString($variety->plant_type) . "\n";
        $prompt .= "Crop Family: " . $safeString($variety->crop_family) . "\n";
        $prompt .= "Description: " . $safeString($variety->description) . "\n";
        $prompt .= "Harvest Notes: " . $safeString($variety->harvest_notes) . "\n";
        
        // Core timing & spacing
        $prompt .= "maturityDays (current): " . $safeString($variety->maturity_days) . "\n";
        $prompt .= "harvestWindowDays (current): " . $safeString($variety->harvest_window_days) . "\n";
        $prompt .= "seasonType (current): " . $safeString($variety->season_type) . " (early/mid/late/all-season)\n";
        $prompt .= "inRowSpacing (current): " . $safeString($variety->in_row_spacing_cm) . " cm\n";
        $prompt .= "betweenRowSpacing (current): " . $safeString($variety->between_row_spacing_cm) . " cm\n";
        
        // Germination data
        $prompt .= "germinationDaysMin (current): " . $safeString($variety->germination_days_min) . "\n";
        $prompt .= "germinationDaysMax (current): " . $safeString($variety->germination_days_max) . "\n";
        $prompt .= "germinationTempOptimal (current): " . $safeString($variety->germination_temp_optimal) . " °C\n";
        $prompt .= "plantingDepthInches (current): " . $safeString($variety->planting_depth_inches) . " inches\n";
        
        // Production data
        $prompt .= "frostTolerance (current): " . $safeString($variety->frost_tolerance) . " (hardy/half-hardy/tender)\n";
        $prompt .= "harvestMethod (current): " . $safeString($variety->harvest_method) . " (cut-and-come-again/single-harvest/continuous)\n";
        $prompt .= "expectedYieldPerPlant (current): " . $safeString($variety->expected_yield_per_plant) . "\n";
        
        $prompt .= "Planting Method: " . $safeString($variety->planting_method) . "\n\n";
        
        $prompt .= "RESPOND WITH ONLY THIS JSON (no other text):\n";
        $prompt .= "{\n";
        $prompt .= "  \"issues\": [],\n";
        $prompt .= "  \"severity\": \"info\",\n";
        $prompt .= "  \"suggestions\": {},\n";
        $prompt .= "  \"confidence\": \"medium\"\n";
        $prompt .= "}\n\n";
        $prompt .= "CRITICAL FIELD NAME RULES:\n";
        $prompt .= "- Use EXACT field names: maturityDays, harvestWindowDays, seasonType, inRowSpacing, betweenRowSpacing, description, germinationDaysMin, germinationDaysMax, germinationTempOptimal, plantingDepthInches, frostTolerance, harvestMethod, expectedYieldPerPlant\n";
        $prompt .= "- DO NOT use 'spacing' - always use inRowSpacing and betweenRowSpacing separately\n";
        $prompt .= "- DO NOT add spaces or capitals (e.g., 'In-row Spacing' is WRONG, use 'inRowSpacing')\n";
        $prompt .= "- DO NOT use nested JSON objects - each suggestion value must be a simple string/number\n\n";
        $prompt .= "CRITICAL RULES:\n";
        $prompt .= "1. List issues ONLY if data is wrong/missing (empty array if OK)\n";
        $prompt .= "2. severity: critical (missing required), warning (questionable), info (minor)\n";
        $prompt .= "3. confidence: high (certain from knowledge), medium (likely), low (unsure)\n";
        $prompt .= "4. Focus on UK growing conditions, realistic spacing, accurate timing\n";
        $prompt .= "5. Season type enables succession planning - CHECK IF MISSING for succession crops\n";
        $prompt .= "6. If crop is succession-friendly (brassicas, lettuce, beans, etc.) and has NO season_type, this is CRITICAL\n\n";
        $prompt .= "SUGGESTION VALUE REQUIREMENTS:\n";
        $prompt .= "- For maturityDays: ONLY THE NUMBER, no units (e.g., '90' NOT '90 days')\n";
        $prompt .= "- For harvestWindowDays: ONLY THE NUMBER, no units (e.g., '30' NOT '30 days')\n";
        $prompt .= "- For seasonType: ONLY 'early', 'mid', 'late', or 'all-season' (lowercase, no quotes)\n";
        $prompt .= "- For inRowSpacing: ONLY THE NUMBER, no units (e.g., '30' NOT '30 cm' or '30.0 cm')\n";
        $prompt .= "- For betweenRowSpacing: ONLY THE NUMBER, no units (e.g., '30' NOT '30 cm' or '30.0 cm')\n";
        $prompt .= "- For germinationDaysMin/Max: ONLY THE NUMBER (e.g., '7' or '14')\n";
        $prompt .= "- For germinationTempOptimal: ONLY THE NUMBER in Celsius (e.g., '18' NOT '18°C')\n";
        $prompt .= "- For plantingDepthInches: ONLY THE NUMBER (e.g., '0.25' or '0.5')\n";
        $prompt .= "- For frostTolerance: ONLY 'hardy', 'half-hardy', or 'tender' (lowercase)\n";
        $prompt .= "- For harvestMethod: ONLY 'cut-and-come-again', 'single-harvest', or 'continuous' (lowercase)\n";
        $prompt .= "- For expectedYieldPerPlant: NUMBER with optional unit (e.g., '500g' or '12 heads' or '2kg')\n";
        $prompt .= "- For descriptions: Provide COMPLETE SENTENCES with variety details\n";
        $prompt .= "- NEVER add units to numeric-only fields like days, spacing, depth\n";
        $prompt .= "- NEVER suggest vague instructions like 'Determine based on...' or 'Please provide...'\n";
        $prompt .= "- NEVER use nested JSON objects - each field should be a simple value\n";
        $prompt .= "- ALWAYS provide the actual value you would use\n";
        $prompt .= "- If you don't know exact value, give your best estimate based on similar plants\n\n";
        $prompt .= "SEASON TYPE GUIDELINES:\n";
        $prompt .= "- early: maturity < 100 days (quick crops for early season)\n";
        $prompt .= "- mid: maturity 100-140 days (standard main season)\n";
        $prompt .= "- late: maturity > 140 days (long season, winter storage)\n";
        $prompt .= "- all-season: Can be planted continuously (lettuce, radish, herbs)\n";
        $prompt .= "- MISSING season type is CRITICAL severity for succession-friendly crops\n";
        $prompt .= "- Examples: Brussels sprouts Churchill (90d) = early, Doric (184d) = late\n\n";
        $prompt .= "CROPS THAT REQUIRE SEASON TYPE (succession-friendly vegetables ONLY):\n";
        $prompt .= "- All brassicas: Brussels sprouts, cabbage, cauliflower, broccoli, kale\n";
        $prompt .= "- Lettuce and salad greens\n";
        $prompt .= "- Beans (runner, French, broad)\n";
        $prompt .= "- Peas\n";
        $prompt .= "- Carrots, beets, turnips\n";
        $prompt .= "- Squash, courgettes, cucumbers\n";
        $prompt .= "- Onions, leeks\n";
        $prompt .= "- Corn/sweetcorn\n";
        $prompt .= "- Tomatoes (some varieties)\n";
        $prompt .= "If crop is in this list and season_type is MISSING, this is CRITICAL severity\n\n";
        $prompt .= "DO NOT SUGGEST season_type FOR:\n";
        $prompt .= "- ANY flowers (Antirrhinum, Begonia, Aster, Zinnia, Cosmos, Celosia, etc.)\n";
        $prompt .= "- ANY ornamentals or cut flowers\n";
        $prompt .= "- Perennial herbs (except culinary varieties with seasonal harvests)\n";
        $prompt .= "- Asparagus, rhubarb (perennials)\n";
        $prompt .= "- Microgreens\n";
        $prompt .= "Season types are ONLY for VEGETABLES used in succession planting\n\n";
        $prompt .= "KNOWLEDGE BASE:\n";
        $prompt .= "- Annuals typically: 60-90 days maturity, 14-30 days harvest window\n";
        $prompt .= "- Perennials typically: 90-120 days to first harvest, 30-60 days harvest window\n";
        $prompt .= "- Vegetables: 45-90 days maturity, 7-21 days harvest window\n";
        $prompt .= "- Flowers: 60-120 days maturity, 14-45 days harvest window\n";
        $prompt .= "- Small plants: 15-30cm spacing\n";
        $prompt .= "- Medium plants: 30-60cm spacing\n";
        $prompt .= "- Large plants: 60-120cm spacing\n";
        $prompt .= "- Germination: Most vegetables 7-14 days, brassicas 5-10 days, slow seeds 14-21 days\n";
        $prompt .= "- Planting depth: General rule = 2-3x seed diameter (tiny seeds 0.125\", medium 0.25-0.5\", large 1\")\n";
        $prompt .= "- Frost tolerance: hardy (survives frost), half-hardy (light frost OK), tender (no frost)\n";
        $prompt .= "- Harvest method: cut-and-come-again (lettuce, chard), single-harvest (cabbage), continuous (tomatoes, beans)\n";
        $prompt .= "- Transplant timing: Usually 4-6 weeks for most vegetables, 8-10 weeks for slow growers\n";
        $prompt .= "- Germination temps: Cool crops 10-18°C, warm crops 18-24°C, heat lovers 24-30°C\n\n";
        $prompt .= "EXAMPLE VALID SUGGESTIONS FORMAT:\n";
        $prompt .= "\"suggestions\": {\n";
        $prompt .= "  \"maturityDays\": \"90\",\n";
        $prompt .= "  \"harvestDays\": \"21\",\n";
        $prompt .= "  \"inRowSpacing\": \"30\",\n";
        $prompt .= "  \"betweenRowSpacing\": \"30\"\n";
        $prompt .= "}\n\n";
        $prompt .= "Respond ONLY with valid JSON containing ACTUAL VALUES, nothing else";
        
        return $prompt;
    }

    protected function processAIResponse($variety, string $analysis)
    {
        // Try to extract JSON from response
        $json = $this->extractJSON($analysis);
        
        if (!$json) {
            $this->logError($variety, "Could not parse AI response as JSON");
            $this->log("Raw response: " . substr($analysis, 0, 200));
            return;
        }
        
        // Debug: Log what we got
        Log::info("AI JSON Response", ['variety_id' => $variety->id, 'json' => $json]);
        
        // Ensure arrays are arrays and strings are strings
        $issues = $json['issues'] ?? [];
        if (!is_array($issues)) {
            $issues = [$issues];
        }
        
        $severity = $json['severity'] ?? 'info';
        if (is_array($severity)) {
            Log::warning("Severity is array", ['severity' => $severity, 'variety_id' => $variety->id]);
            $severity = $severity[0] ?? 'info'; // Take first element if array
        }
        $severity = (string)$severity; // Force to string
        
        $suggestions = $json['suggestions'] ?? [];
        if (!is_array($suggestions)) {
            $suggestions = [];
        }
        
        $confidence = $json['confidence'] ?? 'low';
        if (is_array($confidence)) {
            Log::warning("Confidence is array", ['confidence' => $confidence, 'variety_id' => $variety->id]);
            $confidence = $confidence[0] ?? 'low'; // Take first element if array
        }
        $confidence = (string)$confidence; // Force to string
        
        // Save to database for each issue/suggestion
        if (!empty($issues) || !empty($suggestions)) {
            $this->stats['issues_found']++;
            $this->logIssue($variety, $severity, $issues, $suggestions, $confidence);
            $this->saveToDatabase($variety, $issues, $severity, $suggestions, $confidence);
            
            // Auto-fix if enabled and confidence is high
            if ($this->option('fix') && $confidence === 'high' && !$this->option('dry-run')) {
                $this->autoFix($variety, $suggestions);
            } elseif (!empty($suggestions)) {
                $this->stats['needs_review']++;
            }
        } else {
            $this->log("  ✅ No issues found");
        }
    }

    protected function extractJSON(string $text): ?array
    {
        // Try to find JSON in the response
        if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $text, $matches)) {
            try {
                return json_decode($matches[0], true);
            } catch (\Exception $e) {
                return null;
            }
        }
        
        // Try parsing the whole thing
        try {
            return json_decode($text, true);
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function autoFix($variety, array $suggestions)
    {
        $updates = [];
        $changes = [];

        foreach ($suggestions as $field => $value) {
            if ($value === null || $value === '') continue;

            // CRITICAL: Normalize AI field names to database columns
            $dbField = $this->normalizeFieldName($field);

            if (!$dbField) {
                $this->log("  ⚠️  SKIPPED: Invalid field '$field' (not in database)");
                continue;
            }

            // Only update if current value is missing or clearly wrong
            if (empty($variety->$dbField) || $this->shouldReplace($variety->$dbField, $value)) {
                $updates[$dbField] = $value;
                $changes[] = "{$field} → {$dbField}: '{$variety->$dbField}' → '{$value}'";
            }
        }

        if (!empty($updates)) {
            PlantVariety::where('id', $variety->id)->update($updates);
            $this->stats['auto_fixed']++;

            $fixLog = "[{$variety->id}] {$variety->name}\n";
            $fixLog .= "  Changes: " . implode(", ", $changes) . "\n";
            $this->logFixed($fixLog);

            $this->log("  🔧 AUTO-FIXED: " . count($updates) . " fields");
        }
    }

    protected function shouldReplace($current, $suggested): bool
    {
        // Replace if current contains placeholder text
        $placeholders = ['MISSING', 'Estimated', 'Please verify', 'N/A', 'Unknown'];
        foreach ($placeholders as $placeholder) {
            if (stripos($current, $placeholder) !== false) {
                return true;
            }
        }
        return false;
    }

    protected function saveToDatabase($variety, array $issues, string $severity, array $suggestions, string $confidence)
    {
        try {
            // Combine issues into description
            $issueDescriptions = [];
            foreach ($issues as $issue) {
                if (is_array($issue)) {
                    $issueDescriptions[] = $issue['description'] ?? json_encode($issue);
                } else {
                    $issueDescriptions[] = $issue;
                }
            }
            $issueDescription = implode('; ', $issueDescriptions);
            
            // Create one audit result per suggestion field
            if (!empty($suggestions)) {
                foreach ($suggestions as $field => $suggestedValue) {
                    // Special handling for spacing field that might contain JSON
                    if ($field === 'spacing' && is_array($suggestedValue)) {
                        // Parse spacing JSON and create separate entries
                        if (isset($suggestedValue['inRow'])) {
                            $currentValue = $this->getCurrentFieldValue($variety, 'inRowSpacing');
                            VarietyAuditResult::create([
                                'variety_id' => $variety->id,
                                'issue_description' => $issueDescription,
                                'severity' => $severity,
                                'confidence' => $confidence,
                                'suggested_field' => 'in_row_spacing_cm', // Use snake_case for database
                                'current_value' => $currentValue,
                                'suggested_value' => $suggestedValue['inRow'],
                                'status' => 'pending',
                            ]);
                        }
                        if (isset($suggestedValue['betweenRow'])) {
                            $currentValue = $this->getCurrentFieldValue($variety, 'betweenRowSpacing');
                            VarietyAuditResult::create([
                                'variety_id' => $variety->id,
                                'issue_description' => $issueDescription,
                                'severity' => $severity,
                                'confidence' => $confidence,
                                'suggested_field' => 'between_row_spacing_cm', // Use snake_case for database
                                'current_value' => $currentValue,
                                'suggested_value' => $suggestedValue['betweenRow'],
                                'status' => 'pending',
                            ]);
                        }
                        continue; // Skip the normal creation below
                    }
                    
                    // Get current value from variety
                    $currentValue = $this->getCurrentFieldValue($variety, $field);
                    
                    // Clean up suggested value (remove .0 decimals from spacing fields)
                    $cleanedValue = $this->cleanValue($field, $suggestedValue);
                    
                    // Convert camelCase field name to snake_case database column
                    $dbField = $this->mapFieldToColumn($field);

                    // Skip invalid fields that don't exist in database
                    if (!$dbField) {
                        $this->log("  ⚠️  SKIPPED AUDIT RESULT: Invalid field '$field'");
                        continue;
                    }
                    
                    VarietyAuditResult::create([
                        'variety_id' => $variety->id,
                        'issue_description' => $issueDescription,
                        'severity' => $severity,
                        'confidence' => $confidence,
                        'suggested_field' => $dbField, // Use mapped database column name
                        'current_value' => is_array($currentValue) ? json_encode($currentValue) : $currentValue,
                        'suggested_value' => is_array($cleanedValue) ? json_encode($cleanedValue) : $cleanedValue,
                        'status' => 'pending',
                    ]);
                }
            } else {
                // No specific suggestions, just log the issue
                VarietyAuditResult::create([
                    'variety_id' => $variety->id,
                    'issue_description' => $issueDescription,
                    'severity' => $severity,
                    'confidence' => $confidence,
                    'status' => 'pending',
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to save audit result to database", [
                'variety_id' => $variety->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    protected function cleanValue(string $field, $value)
    {
        // For spacing fields, remove unnecessary .0 decimals
        if (in_array($field, ['inRowSpacing', 'betweenRowSpacing']) && is_numeric($value)) {
            // Convert to float, then to int if it's a whole number
            $floatValue = (float)$value;
            if ($floatValue == (int)$floatValue) {
                return (string)(int)$floatValue;
            }
        }
        return $value;
    }
    
    protected function getCurrentFieldValue($variety, string $field)
    {
        // Map suggestion field names to variety model properties
        $fieldMap = [
            'maturityDays' => 'maturity_days',
            'harvestWindowDays' => 'harvest_window_days',
            'inRowSpacing' => 'in_row_spacing_cm',
            'betweenRowSpacing' => 'between_row_spacing_cm',
            'plantingMethod' => 'planting_method',
            'description' => 'description',
            'harvestNotes' => 'harvest_notes',
            'seasonType' => 'season_type',
            'germinationDaysMin' => 'germination_days_min',
            'germinationDaysMax' => 'germination_days_max',
            'germinationTempOptimal' => 'germination_temp_optimal',
            'plantingDepthInches' => 'planting_depth_inches',
            'frostTolerance' => 'frost_tolerance',
            'harvestMethod' => 'harvest_method',
            'expectedYieldPerPlant' => 'expected_yield_per_plant',
            'transplantDays' => 'transplant_days',
        ];
        
        $propertyName = $fieldMap[$field] ?? $field;
        return $variety->$propertyName ?? null;
    }

    /**
     * Map camelCase field names from AI to snake_case database column names
     */
    protected function mapFieldToColumn(string $field): ?string
    {
        $fieldMap = [
            'maturityDays' => 'maturity_days',
            'harvestWindowDays' => 'harvest_window_days',
            'inRowSpacing' => 'in_row_spacing_cm',
            'betweenRowSpacing' => 'between_row_spacing_cm',
            'plantingMethod' => 'planting_method',
            'description' => 'description',
            'harvestNotes' => 'harvest_notes',
            'seasonType' => 'season_type',
            'germinationDaysMin' => 'germination_days_min',
            'germinationDaysMax' => 'germination_days_max',
            'germinationTempOptimal' => 'germination_temp_optimal',
            'plantingDepthInches' => 'planting_depth_inches',
            'frostTolerance' => 'frost_tolerance',
            'harvestMethod' => 'harvest_method',
            'expectedYieldPerPlant' => 'expected_yield_per_plant',
            'plantType' => 'plant_type',
            // Invalid fields that don't exist in database:
            'transplantDays' => null, // transplant_days doesn't exist
            'spacing' => null, // handled separately as JSON
        ];

        $mapped = $fieldMap[$field] ?? $field;

        // Validate that the mapped field exists in the database
        if ($mapped) {
            $columns = \Illuminate\Support\Facades\Schema::getColumnListing('plant_varieties');
            if (!in_array($mapped, $columns)) {
                $this->log("  ⚠️  INVALID FIELD: '$field' maps to '$mapped' (not in database)");
                return null;
            }
        }

        return $mapped;
    }

    protected function logIssue($variety, string $severity, array $issues, array $suggestions, string $confidence)
    {
        $log = "⚠️  [{$variety->id}] {$variety->name}\n";
        $log .= "  Severity: {$severity} | Confidence: {$confidence}\n";
        $log .= "  Issues:\n";
        foreach ($issues as $issue) {
            $issueText = is_array($issue) ? json_encode($issue) : $issue;
            $log .= "    - {$issueText}\n";
        }
        if (!empty($suggestions)) {
            $log .= "  Suggestions:\n";
            foreach ($suggestions as $field => $value) {
                $displayValue = is_array($value) ? json_encode($value) : $value;
                $log .= "    {$field}: {$displayValue}\n";
            }
        }
        $log .= "\n";
        
        file_put_contents($this->issuesFile, $log, FILE_APPEND);
        $this->log("  ⚠️  Issues: " . count($issues) . " ({$severity})");
    }

    protected function logError($variety, string $message)
    {
        $log = "❌ [{$variety->id}] {$variety->name}: {$message}\n";
        file_put_contents($this->issuesFile, $log, FILE_APPEND);
    }

    protected function logFixed(string $message)
    {
        file_put_contents($this->fixedFile, $message . "\n", FILE_APPEND);
    }

    protected function log(string $message)
    {
        file_put_contents($this->logFile, $message . "\n", FILE_APPEND);
    }

    protected function showSummary()
    {
        $duration = $this->startTime->diffInSeconds(now());
        $perSecond = $this->stats['processed'] > 0 ? $duration / $this->stats['processed'] : 0;
        
        $this->info("=== AUDIT COMPLETE ===");
        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Varieties', $this->stats['total']],
                ['Processed', $this->stats['processed']],
                ['Skipped', $this->stats['skipped']],
                ['Issues Found', $this->stats['issues_found']],
                ['Auto-Fixed', $this->stats['auto_fixed']],
                ['Needs Review', $this->stats['needs_review']],
                ['Errors', $this->stats['errors']],
                ['Duration', gmdate('H:i:s', $duration)],
                ['Avg Time/Variety', number_format($perSecond, 2) . 's'],
            ]
        );
        
        $this->newLine();
        $this->info("📄 Review the log files:");
        $this->info("   {$this->issuesFile}");
        if ($this->stats['auto_fixed'] > 0) {
            $this->info("   {$this->fixedFile}");
        }
        
        // Log summary
        $this->log("\n=== SUMMARY ===");
        $this->log("Processed: {$this->stats['processed']}/{$this->stats['total']}");
        $this->log("Issues: {$this->stats['issues_found']}");
        $this->log("Fixed: {$this->stats['auto_fixed']}");
        $this->log("Needs Review: {$this->stats['needs_review']}");
        $this->log("Errors: {$this->stats['errors']}");
        $this->log("Duration: " . gmdate('H:i:s', $duration));
    }

    /**
     * Field mapping from AI responses to database columns
     * Prevents API waste from invalid field names
     */
    protected $fieldMapping = [
        // AI camelCase -> database snake_case
        'maturityDays' => 'maturity_days',
        'harvestDays' => 'harvest_window_days',
        'harvestWindowDays' => 'harvest_window_days',
        'inRowSpacing' => 'in_row_spacing_cm',
        'betweenRowSpacing' => 'between_row_spacing_cm',
        'plantingMethod' => 'planting_method',
        'harvestNotes' => 'harvest_notes',
        'seasonType' => 'season_type',
        'expectedYieldPerPlant' => 'expected_yield_per_plant',
        'plantType' => 'plant_type',
        'transplantDays' => null, // Invalid - doesn't exist in DB
        'spacing' => null, // Complex JSON field, handled separately
        // Add more mappings as needed
    ];

    /**
     * Normalize AI field names to database column names
     * Returns null if field doesn't exist in database
     */
    protected function normalizeFieldName(string $aiFieldName): ?string
    {
        // First try direct mapping
        if (isset($this->fieldMapping[$aiFieldName])) {
            return $this->fieldMapping[$aiFieldName];
        }

        // Try snake_case conversion
        $snakeCase = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $aiFieldName));
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('plant_varieties');
        if (in_array($snakeCase, $columns)) {
            return $snakeCase;
        }

        // Try lowercase with underscores
        $lowercase = str_replace(' ', '_', strtolower($aiFieldName));
        if (in_array($lowercase, $columns)) {
            return $lowercase;
        }

        // Field not found in database
        return null;
    }
}
