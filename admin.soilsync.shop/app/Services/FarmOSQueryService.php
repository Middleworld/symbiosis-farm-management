<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
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

        // Join custom fields
        foreach ($withFields as $field) {
            $table = "taxonomy_term__field_{$field}";
            $query->leftJoin("{$table} as {$field}", 't.tid', '=', "{$field}.entity_id")
                  ->addSelect("{$field}.field_{$field}_value as {$field}");
        }

        $query->select('t.tid', 't.name', 't.description__value as description', 't.status');

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

        // Join custom fields
        foreach ($withFields as $field) {
            $table = "taxonomy_term__field_{$field}";
            $query->leftJoin("{$table} as {$field}", 't.tid', '=', "{$field}.entity_id")
                  ->addSelect("{$field}.field_{$field}_value as {$field}");
        }

        $query->select('t.tid', 't.name', 't.description__value as description', 't.status');

        return $query->get()->keyBy('tid');
    }

    /**
     * Get plant types (parent categories like "Vegetables", "Herbs")
     * 
     * @return Collection
     */
    public function getPlantTypes(): Collection
    {
        return DB::connection('farmos')
            ->table('taxonomy_term_field_data as t')
            ->leftJoin('taxonomy_term__parent as p', 't.tid', '=', 'p.entity_id')
            ->where('t.vid', 'plant_type')
            ->where('t.status', 1)
            ->where(function($q) {
                $q->whereNull('p.parent_target_id')
                  ->orWhere('p.parent_target_id', 0); // 0 = top-level in farmOS
            })
            ->select('t.tid', 't.name', 't.description__value as description')
            ->orderBy('t.name')
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
     * Get planting assets (plant assets)
     * 
     * @param array $options - Filtering options
     * @return Collection
     */
    public function getPlantings(array $options = []): Collection
    {
        // Query transplanting logs to get bed occupancy with dates
        $query = DB::connection('farmos')
            ->table('log as l')
            ->join('log_field_data as lfd', 'l.id', '=', 'lfd.id')
            ->leftJoin('log__asset as la', 'l.id', '=', 'la.entity_id') // Plant asset reference
            ->leftJoin('asset_field_data as afd', 'la.asset_target_id', '=', 'afd.id') // Plant asset details
            ->leftJoin('log__location as ll', 'l.id', '=', 'll.entity_id') // Bed location
            ->leftJoin('asset_field_data as bed', 'll.location_target_id', '=', 'bed.id') // Bed name
            ->leftJoin('asset__plant_type as pt', 'afd.id', '=', 'pt.entity_id') // Plant variety
            ->leftJoin('taxonomy_term_field_data as variety', 'pt.plant_type_target_id', '=', 'variety.tid')
            ->where('l.type', 'transplanting')
            ->where('lfd.status', 'done')
            ->whereNotNull('ll.location_target_id'); // Must have bed location

        // Date range filter (transplant date)
        if (!empty($options['start_date'])) {
            $query->where('lfd.timestamp', '>=', strtotime($options['start_date']));
        }
        if (!empty($options['end_date'])) {
            $query->where('lfd.timestamp', '<=', strtotime($options['end_date']));
        }

        $results = $query->select(
            'l.id as log_id',
            'lfd.name as log_name',
            DB::raw('FROM_UNIXTIME(lfd.timestamp) as transplant_date'),
            'afd.name as plant_name',
            'bed.name as bed_id', // Frontend expects bed_id to be bed name (e.g., "B1/1")
            'bed.name as bed_name',
            'variety.name as variety_name',
            'variety.tid as variety_id'
        )
        ->orderBy('lfd.timestamp', 'desc')
        ->get();

        // Transform to match expected frontend format
        return $results->map(function($planting) {
            return [
                'bed_id' => $planting->bed_name, // Frontend filters by bed_id === bed.name
                'bed_name' => $planting->bed_name,
                'crop' => $planting->plant_name,
                'variety' => $planting->variety_name,
                'transplant_date' => $planting->transplant_date ? date('Y-m-d', strtotime($planting->transplant_date)) : null,
                'start_date' => $planting->transplant_date ? date('Y-m-d', strtotime($planting->transplant_date)) : null,
                'is_direct_seeded' => false, // Transplanting logs are never direct seeded
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
}
