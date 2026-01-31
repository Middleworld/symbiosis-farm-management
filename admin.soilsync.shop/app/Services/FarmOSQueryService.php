<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;

/**
 * FarmOSQueryService - Direct database queries to farmOS
 * 
 * CRITICAL: This service queries farmOS database directly for READ operations only.
 * - 100x faster than API calls (50ms vs 2-30 seconds)
 * - Always up-to-date (no sync needed)
 * - Single source of truth (farmOS database)
 * 
 * For WRITE operations, use FarmOSApi service (validates via Drupal hooks).
 * 
 * @uses \Illuminate\Support\Facades\DB
 */
class FarmOSQueryService
{
    /**
     * Get all plant varieties/types from farmOS taxonomy
     * 
     * @param array $options - Filtering options
     * @return Collection
     */
    public function getPlantVarieties(array $options = []): Collection
    {
        $query = DB::connection('farmos')
            ->table('taxonomy_term_field_data as t')
            ->where('t.vid', 'plant_type')
            ->where('t.status', 1);

        // Join custom fields if requested
        if (!empty($options['with_fields'])) {
            foreach ($options['with_fields'] as $field) {
                $table = "taxonomy_term__field_{$field}";
                $query->leftJoin("{$table} as {$field}", 't.tid', '=', "{$field}.entity_id")
                      ->addSelect("{$field}.field_{$field}_value as {$field}");
            }
        }

        // Filter by active status
        if (isset($options['active_only']) && $options['active_only']) {
            $query->where('t.status', 1);
        }

        // Filter by parent (plant type)
        if (!empty($options['parent_id'])) {
            $query->leftJoin('taxonomy_term__parent as p', 't.tid', '=', 'p.entity_id')
                  ->where('p.parent_target_id', $options['parent_id']);
        }

        // Search by name
        if (!empty($options['search'])) {
            $query->where('t.name', 'like', '%' . $options['search'] . '%');
        }

        // Ordering
        $orderBy = $options['order_by'] ?? 'name';
        $orderDir = $options['order_dir'] ?? 'asc';
        $query->orderBy("t.{$orderBy}", $orderDir);

        // Limit
        if (!empty($options['limit'])) {
            $query->limit($options['limit']);
        }

        $query->select('t.tid', 't.name', 't.description__value as description', 't.status');

        return $query->get();
    }

    /**
     * Get a single plant variety by ID
     * 
     * @param int $tid - Taxonomy term ID
     * @param array $withFields - Custom fields to include
     * @return object|null
     */
    public function getPlantVarietyById(int $tid, array $withFields = []): ?object
    {
        $query = DB::connection('farmos')
            ->table('taxonomy_term_field_data as t')
            ->where('t.tid', $tid)
            ->where('t.vid', 'plant_type');

        $selectFields = ['t.tid', 't.name', 't.description__value as description', 't.status'];

        // Join custom fields
        foreach ($withFields as $field) {
            $table = "taxonomy_term__{$field}";
            $query->leftJoin("{$table} as {$field}", 't.tid', '=', "{$field}.entity_id");
            $selectFields[] = "{$field}.{$field}_value as {$field}";
        }

        $query->select($selectFields);

        return $query->first();
    }

    /**
     * Get plant varieties by multiple IDs
     * 
     * @param array $tids - Array of taxonomy term IDs
     * @param array $withFields - Custom fields to include
     * @return Collection (keyed by tid)
     */
    public function getPlantVarietiesByIds(array $tids, array $withFields = []): Collection
    {
        $query = DB::connection('farmos')
            ->table('taxonomy_term_field_data as t')
            ->whereIn('t.tid', $tids)
            ->where('t.vid', 'plant_type');

        $selectFields = ['t.tid', 't.name', 't.description__value as description', 't.status'];

        // Join custom fields
        foreach ($withFields as $field) {
            $table = "taxonomy_term__{$field}";
            $query->leftJoin("{$table} as {$field}", 't.tid', '=', "{$field}.entity_id");
            $selectFields[] = "{$field}.{$field}_value as {$field}";
        }

        $query->select($selectFields);

        return $query->get()->keyBy('tid');
    }

    /**
     * Get plant types (crop categories like "Lettuce", "Carrot", "Tomato")
     * These are identified as taxonomy terms without spaces in their names
     *
     * @return Collection
     */
    public function getPlantTypes(): Collection
    {
        return DB::connection('farmos')
            ->table('taxonomy_term_field_data')
            ->where('vid', 'plant_type')
            ->where('status', 1)
            ->where('name', 'not like', '% %') // Crop types don't have spaces in their names
            ->select('tid', 'name', 'description__value as description')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get all beds (land assets with type 'bed')
     * 
     * @param array $options - Filtering options
     * @return Collection
     */
    public function getBeds(array $options = []): Collection
    {
        $query = DB::connection('farmos')
            ->table('asset_field_data as afd')
            ->join('asset as a', 'afd.id', '=', 'a.id')
            ->where('afd.type', 'land')
            // Filter out parent locations (Block 1, Block 2, SoilSync, Block Unknown)
            ->where('afd.name', 'NOT LIKE', 'Block %')
            ->where('afd.name', '!=', 'SoilSync');

        // Filter by location
        if (!empty($options['location_id'])) {
            $query->join('asset__location as loc', 'afd.id', '=', 'loc.entity_id')
                  ->where('loc.location_target_id', $options['location_id']);
        }

        // Search by name
        if (!empty($options['search'])) {
            $query->where('afd.name', 'like', '%' . $options['search'] . '%');
        }

        $query->select('afd.id', 'a.uuid', 'afd.name', 'afd.status', 'afd.type as land_type')
              ->distinct()
              ->orderByRaw("
                  SUBSTRING_INDEX(afd.name, '/', 1) ASC,
                  CAST(SUBSTRING_INDEX(afd.name, '/', -1) AS UNSIGNED) ASC
              ");

        return $query->get();
    }

    /**
     * Get bed by ID
     * 
     * @param int $bedId
     * @return object|null
     */
    public function getBedById(int $bedId): ?object
    {
        return DB::connection('farmos')
            ->table('asset as a')
            ->join('asset_field_data as afd', 'a.id', '=', 'afd.id')
            ->join('asset__land_type as lt', 'a.id', '=', 'lt.entity_id')
            ->where('a.id', $bedId)
            ->where('lt.land_type_value', 'bed')
            ->select('a.id', 'a.uuid', 'afd.name', 'afd.status', 'lt.land_type_value as land_type')
            ->first();
    }

    /**
     * Get harvest logs within date range
     * 
     * @param string|null $startDate
     * @param string|null $endDate
     * @param array $options - Additional filters
     * @return Collection
     */
    public function getHarvestLogs(?string $startDate = null, ?string $endDate = null, array $options = []): Collection
    {
        $query = DB::connection('farmos')
            ->table('log as l')
            ->where('l.type', 'harvest')
            ->where('l.status', 'done');

        // Date range filter
        if ($startDate) {
            $query->where('l.timestamp', '>=', strtotime($startDate));
        }
        if ($endDate) {
            $query->where('l.timestamp', '<=', strtotime($endDate));
        }

        // Filter by asset (bed/planting)
        if (!empty($options['asset_id'])) {
            $query->join('log__asset as la', 'l.id', '=', 'la.entity_id')
                  ->where('la.asset_target_id', $options['asset_id']);
        }

        // Join quantities for harvest amounts
        if (!empty($options['with_quantities'])) {
            $query->leftJoin('log__quantity as lq', 'l.id', '=', 'lq.entity_id')
                  ->leftJoin('quantity as q', 'lq.quantity_target_id', '=', 'q.id')
                  ->addSelect('q.value as quantity_value', 'q.measure as quantity_measure');
        }

        $query->select('l.id', 'l.uuid', 'l.name', 'l.timestamp', 'l.status', 'l.notes__value as notes')
              ->orderBy('l.timestamp', 'desc');

        return $query->get();
    }

    /**
     * Get seeding logs within date range
     * 
     * @param string|null $startDate
     * @param string|null $endDate
     * @return Collection
     */
    public function getSeedingLogs(?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = DB::connection('farmos')
            ->table('log as l')
            ->where('l.type', 'seeding')
            ->where('l.status', 'done');

        if ($startDate) {
            $query->where('l.timestamp', '>=', strtotime($startDate));
        }
        if ($endDate) {
            $query->where('l.timestamp', '<=', strtotime($endDate));
        }

        return $query->select('l.id', 'l.uuid', 'l.name', 'l.timestamp', 'l.status', 'l.notes__value as notes')
                     ->orderBy('l.timestamp', 'desc')
                     ->get();
    }

    /**
     * Get transplanting logs within date range
     * 
     * @param string|null $startDate
     * @param string|null $endDate
     * @return Collection
     */
    public function getTransplantingLogs(?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = DB::connection('farmos')
            ->table('log as l')
            ->where('l.type', 'transplanting')
            ->where('l.status', 'done');

        if ($startDate) {
            $query->where('l.timestamp', '>=', strtotime($startDate));
        }
        if ($endDate) {
            $query->where('l.timestamp', '<=', strtotime($endDate));
        }

        return $query->select('l.id', 'l.uuid', 'l.name', 'l.timestamp', 'l.status', 'l.notes__value as notes')
                     ->orderBy('l.timestamp', 'desc')
                     ->get();
    }

    /**
     * Get planting logs (both seeding and transplanting) for timeline occupancy
     * 
     * @param array $options - Filtering options
     * @return Collection
     */
    public function getPlantings(array $options = []): Collection
    {
        // Query BOTH seeding and transplanting logs to get bed occupancy with dates
        // IMPORTANT: Include BOTH 'done' and 'pending' logs to show planned successions on timeline
        $query = DB::connection('farmos')
            ->table('log as l')
            ->join('log_field_data as lfd', 'l.id', '=', 'lfd.id')
            ->leftJoin('log__asset as la', 'l.id', '=', 'la.entity_id') // Plant asset reference
            ->leftJoin('asset_field_data as afd', 'la.asset_target_id', '=', 'afd.id') // Plant asset details
            ->leftJoin('log__location as ll', 'l.id', '=', 'll.entity_id') // Bed location
            ->leftJoin('asset_field_data as bed', 'll.location_target_id', '=', 'bed.id') // Bed name
            ->leftJoin('asset__plant_type as pt', 'afd.id', '=', 'pt.entity_id') // Plant variety
            ->leftJoin('taxonomy_term_field_data as variety', 'pt.plant_type_target_id', '=', 'variety.tid')
            ->leftJoin('taxonomy_term__maturity_days as md', 'variety.tid', '=', 'md.entity_id')
            ->whereIn('l.type', ['seeding', 'transplanting']) // Include BOTH seeding (direct-sown) AND transplanting
            ->whereIn('lfd.status', ['done', 'pending']) // Show both completed AND planned plantings
            ->whereNotNull('ll.location_target_id'); // Must have bed location

        // Optional harvest window field (varies by farmOS setup)
        $harvestWindowSelect = DB::raw('NULL as harvest_window_days');
        if (Schema::connection('farmos')->hasTable('taxonomy_term__harvest_window_days')) {
            $query->leftJoin('taxonomy_term__harvest_window_days as hw', 'variety.tid', '=', 'hw.entity_id');
            $harvestWindowSelect = DB::raw('hw.harvest_window_days_value as harvest_window_days');
        } elseif (Schema::connection('farmos')->hasTable('taxonomy_term__harvest_days')) {
            $query->leftJoin('taxonomy_term__harvest_days as hw', 'variety.tid', '=', 'hw.entity_id');
            $harvestWindowSelect = DB::raw('hw.harvest_days_value as harvest_window_days');
        }

        // Date range filter (transplant date)
        if (!empty($options['start_date'])) {
            $query->where('lfd.timestamp', '>=', strtotime($options['start_date']));
        }
        if (!empty($options['end_date'])) {
            $query->where('lfd.timestamp', '<=', strtotime($options['end_date']));
        }

        $results = $query->select(
            'l.id as log_id',
            'l.type as log_type',
            'lfd.name as log_name',
            DB::raw('FROM_UNIXTIME(lfd.timestamp) as log_date'),
            'afd.name as plant_name',
            'bed.name as bed_id', // Frontend expects bed_id to be bed name (e.g., "B1/1")
            'bed.name as bed_name',
            'variety.name as variety_name',
            'variety.tid as variety_id',
            'md.maturity_days_value as maturity_days',
            $harvestWindowSelect
        )
        ->orderBy('lfd.timestamp', 'desc')
        ->get();

        // Transform to match expected frontend format
        return $results->map(function($planting) {
            $isDirectSeeded = $planting->log_type === 'seeding';
            $startDate = $planting->log_date ? date('Y-m-d', strtotime($planting->log_date)) : null;
            
            return [
                'bed_id' => $planting->bed_name, // Frontend filters by bed_id === bed.name
                'bed_name' => $planting->bed_name,
                'crop' => $planting->plant_name,
                'variety' => $planting->variety_name,
                'transplant_date' => !$isDirectSeeded ? $startDate : null,
                'seed_date' => $isDirectSeeded ? $startDate : null,
                'seeding_date' => $isDirectSeeded ? $startDate : null, // Add seeding_date field
                'start_date' => $startDate, // Use log date as start date (seeding or transplanting)
                'is_direct_seeded' => $isDirectSeeded,
                'maturity_days' => $planting->maturity_days, // Days from seeding to harvest
                'harvest_window_days' => $planting->harvest_window_days, // Harvest duration
                'harvest_date' => null, // TODO: Get from harvest logs if needed
                'end_date' => null, // TODO: Calculate from maturity days
                'notes' => $planting->log_name
            ];
        });
    }

    /**
     * Get crop families taxonomy
     * 
     * @return Collection
     */
    public function getCropFamilies(): Collection
    {
        return DB::connection('farmos')
            ->table('taxonomy_term_field_data')
            ->where('vid', 'crop_family')
            ->where('status', 1)
            ->select('tid', 'name', 'description__value as description')
            ->orderBy('name')
            ->get();
    }

    /**
     * Search across plant varieties, beds, and logs
     * 
     * @param string $searchTerm
     * @return array
     */
    public function searchAll(string $searchTerm): array
    {
        return [
            'varieties' => $this->getPlantVarieties(['search' => $searchTerm, 'limit' => 10]),
            'beds' => $this->getBeds(['search' => $searchTerm]),
            'harvest_logs' => $this->getHarvestLogs(null, null, ['search' => $searchTerm]),
        ];
    }

    /**
     * Get geometry assets (land/beds) with geometry data for mapping
     * 
     * @param array $options - Filtering options
     * @return Collection
     */
    public function getGeometryAssets(array $options = []): Collection
    {
        $query = DB::connection('farmos')
            ->table('asset_field_data as afd')
            ->join('asset as a', 'afd.id', '=', 'a.id')
            ->leftJoin('asset__intrinsic_geometry as geom', 'afd.id', '=', 'geom.entity_id')
            ->where('afd.type', 'land')
            ->where('afd.status', 'active')
            ->whereNotNull('geom.intrinsic_geometry_value');

        // Filter by location
        if (!empty($options['location_id'])) {
            $query->join('asset__location as loc', 'afd.id', '=', 'loc.entity_id')
                  ->where('loc.location_target_id', $options['location_id']);
        }

        // Search by name
        if (!empty($options['search'])) {
            $query->where('afd.name', 'like', '%' . $options['search'] . '%');
        }

        $results = $query->select(
            'afd.id',
            'a.uuid',
            'afd.name',
            'afd.status',
            'afd.type as land_type',
            'geom.intrinsic_geometry_value as geometry_value',
            'geom.intrinsic_geometry_geo_type as geo_type'
        )->get();

        // Transform to match API format
        return $results->map(function($asset) {
            $geometry = null;
            if ($asset->geometry_value && $asset->geo_type) {
                // Convert WKT to GeoJSON format (simplified)
                if (strtoupper($asset->geo_type) === 'POLYGON') {
                    $geometry = $this->convertWktToGeoJson($asset->geometry_value, $asset->geo_type);
                }
            }

            return [
                'id' => $asset->id,
                'name' => $asset->name,
                'status' => $asset->status,
                'land_type' => $asset->land_type,
                'geometry' => $geometry
            ];
        });
    }

    /**
     * Get plant assets for crop planning
     * 
     * @param array $options - Filtering options
     * @return Collection
     */
    public function getPlantAssets(array $options = []): Collection
    {
        $query = DB::connection('farmos')
            ->table('asset_field_data as afd')
            ->join('asset as a', 'afd.id', '=', 'a.id')
            ->leftJoin('asset__plant_type as pt', 'afd.id', '=', 'pt.entity_id')
            ->leftJoin('taxonomy_term_field_data as variety', 'pt.plant_type_target_id', '=', 'variety.tid')
            ->where('afd.type', 'plant')
            ->where('afd.status', 'active');

        // Filter by location
        if (!empty($options['location_id'])) {
            $query->join('asset__location as loc', 'afd.id', '=', 'loc.entity_id')
                  ->where('loc.location_target_id', $options['location_id']);
        }

        // Search by name
        if (!empty($options['search'])) {
            $query->where('afd.name', 'like', '%' . $options['search'] . '%');
        }

        $results = $query->select(
            'afd.id as farmos_asset_id',
            'a.uuid',
            'afd.name as variety',
            'afd.status',
            'variety.name as variety_name',
            'variety.tid as variety_id',
            DB::raw('FROM_UNIXTIME(afd.created) as created_at'),
            DB::raw('FROM_UNIXTIME(afd.changed) as updated_at')
        )->get();

        // Transform to match API format
        return $results->map(function($asset) {
            return [
                'farmos_asset_id' => $asset->farmos_asset_id,
                'crop_type' => 'vegetable', // Default crop type
                'variety' => $asset->variety ?: $asset->variety_name ?: 'Unknown',
                'status' => $asset->status,
                'created_at' => $asset->created_at,
                'updated_at' => $asset->updated_at,
            ];
        });
    }

    /**
     * Simple WKT to GeoJSON conversion (simplified version)
     */
    private function convertWktToGeoJson(string $wkt, string $geoType): ?array
    {
        if (strtoupper($geoType) === 'POLYGON') {
            // Simple POLYGON parsing - extract coordinates
            if (preg_match('/^POLYGON\s*\(\((.*)\)\)$/i', $wkt, $matches)) {
                $coordinateString = $matches[1];
                $coordinates = $this->parseCoordinateString($coordinateString);
                
                return [
                    'type' => 'Polygon',
                    'coordinates' => [$coordinates]
                ];
            }
        }
        
        return null;
    }

    /**
     * Parse coordinate string from WKT
     */
    private function parseCoordinateString(string $coordString): array
    {
        $coordinates = [];
        $pairs = explode(',', trim($coordString));
        
        foreach ($pairs as $pair) {
            $pair = trim($pair);
            if (preg_match('/^([-\d.]+)\s+([-\d.]+)$/', $pair, $matches)) {
                $coordinates[] = [(float)$matches[1], (float)$matches[2]];
            }
        }
        
        return $coordinates;
    }
}
