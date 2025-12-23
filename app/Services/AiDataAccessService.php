<?php
namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Unified read-only data access surface for AI features.
 * Wraps farmOS, WooCommerce, and internal planting schedule.
 */
class AiDataAccessService
{
    public function __construct(
        protected FarmOSApiService $farmOs,
        protected WpApiService $wp,
        protected PlantingRecommendationService $planting
    ) {}

    /** Get geo features (cached by underlying service). */
    public function getFarmosGeometry(): array
    {
        return $this->farmOs->getGeometryAssets() ?: [];
    }

    /** Get active plant assets (basic list). */
    public function getFarmosPlantAssets(): array
    {
        return $this->farmOs->getPlantAssets() ?: [];
    }

    /** Get recent customers (limit configurable). */
    public function getWooCustomersRecent(int $limit = 25): array
    {
        return $this->wp->getRecentUsers($limit)->values()->all();
    }

    /** Basic delivery schedule summary if method exists. */
    public function getWooScheduleSummary(): array
    {
        if (method_exists($this->wp, 'getDeliveryScheduleData')) {
            try { return $this->wp->getDeliveryScheduleData(); } catch (\Throwable $e) { return ['error' => $e->getMessage()]; }
        }
        return ['warning' => 'getDeliveryScheduleData not implemented'];
    }

    /** Planting recommendations wrapper. */
    public function getPlantingWeek(?int $week = null): array
    {
        return $this->planting->forWeek($week);
    }

    /** Data catalog passthrough. */
    public function catalog(): array
    {
        return config('ai_data_catalog');
    }
}
