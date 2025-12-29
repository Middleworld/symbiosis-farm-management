<?php
namespace App\Services;

use Carbon\Carbon;

class PlantingRecommendationService
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('planting_schedule');
    }

    public function forWeek(?int $isoWeek = null): array
    {
        $now = Carbon::now($this->config['season']['timezone'] ?? config('app.timezone'));
        $week = $isoWeek ?: (int)$now->isoWeek();
        $year = (int)$now->year;

        $rangeStart = $now->copy()->startOfWeek();
        $rangeEnd = $now->copy()->endOfWeek();

        $crops = $this->config['crops'];
        $season = $this->config['season'];
        $lastFrost = Carbon::parse($season['last_frost']);

        $direct = [];
        $transplantOut = [];
        $startInTrays = [];
        $warnings = [];

        foreach ($crops as $key => $crop) {
            if ($week < $crop['earliest_week'] || $week > $crop['latest_week']) {
                continue;
            }
            $interval = $crop['succession_interval_weeks'] ?? null;
            $due = $interval ? (($week - $crop['earliest_week']) % $interval === 0) : ($week === $crop['earliest_week']);
            if (!$due) continue;

            $tender = ($crop['frost_tolerance'] ?? 'tender') === 'tender';
            if ($tender && $now->lt($lastFrost->copy()->addDays(7))) {
                $warnings[] = $crop['name'] . ' skipped (frost risk)';
                continue;
            }

            // Transplant lead weeks logic
            $lead = $crop['transplant_lead_weeks'] ?? null;
            $supportsTransplant = in_array('transplant', $crop['methods']);
            $supportsDirect = in_array('direct', $crop['methods']);

            if ($supportsDirect) {
                $direct[] = $this->buildCropEntry($key, $crop);
            }
            if ($supportsTransplant) {
                // If due week is within main window, we might either be setting out or starting trays
                if ($lead) {
                    // Start in trays for crops whose transplanting would be in future due weeks
                    $startInTrays[] = $this->buildCropEntry($key, $crop);
                } else {
                    $transplantOut[] = $this->buildCropEntry($key, $crop);
                }
            }
        }

        return [
            'week' => $week,
            'year' => $year,
            'date_range' => $rangeStart->format('Y-m-d') . ' to ' . $rangeEnd->format('Y-m-d'),
            'generated_at' => $now->toIso8601String(),
            'direct_sow' => $direct,
            'transplant_out' => $transplantOut,
            'start_in_trays' => $startInTrays,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    protected function buildCropEntry(string $key, array $crop): array
    {
        $densityPerM2 = $this->estimatePlantsPerM2($crop);
        return [
            'key' => $key,
            'name' => $crop['name'],
            'family' => $crop['family'] ?? null,
            'method_options' => $crop['methods'],
            'row_spacing_cm' => $crop['row_spacing_cm'] ?? null,
            'in_row_spacing_cm' => $crop['in_row_spacing_cm'] ?? null,
            'est_plants_per_m2' => $densityPerM2,
            'notes' => $crop['notes'] ?? null,
        ];
    }

    protected function estimatePlantsPerM2(array $crop): ?int
    {
        $row = $crop['row_spacing_cm'] ?? null;
        $inRow = $crop['in_row_spacing_cm'] ?? null;
        if (!$row || !$inRow) return null;
        $areaPerPlantCm2 = $row * $inRow; // cm^2
        if ($areaPerPlantCm2 <= 0) return null;
        $cm2PerM2 = 10000;
        return (int)floor($cm2PerM2 / $areaPerPlantCm2);
    }
}
