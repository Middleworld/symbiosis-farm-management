
    /**
     * Get plantings for a specific plan from farmOS
     */
    private function getPlanPlantings($planId)
    {
        return DB::connection('farmos')
            ->table('plan_record as pr')
            ->leftJoin('plan_record__plant as p', 'pr.id', '=', 'p.entity_id')
            ->leftJoin('asset_field_data as a', 'p.plant_target_id', '=', 'a.id')
            ->leftJoin('plan_record__seeding_date as sd', 'pr.id', '=', 'sd.entity_id')
            ->leftJoin('plan_record__transplant_days as td', 'pr.id', '=', 'td.entity_id')
            ->leftJoin('plan_record__maturity_days as md', 'pr.id', '=', 'md.entity_id')
            ->leftJoin('plan_record__harvest_days as hd', 'pr.id', '=', 'hd.entity_id')
            ->where('pr.plan', $planId)
            ->where('pr.type', 'crop_planting')
            ->select(
                'pr.id as plan_record_id',
                'a.id as asset_id',
                'a.name as asset_name',
                'sd.seeding_date_value as seeding_date',
                'td.transplant_days_value as transplant_days',
                'md.maturity_days_value as maturity_days',
                'hd.harvest_days_value as harvest_days'
            )
            ->get()
            ->map(function ($planting) {
                // Calculate derived dates
                $seedingDate = $planting->seeding_date ? Carbon::parse($planting->seeding_date) : null;

                $planting->transplant_date = null;
                $planting->harvest_start_date = null;
                $planting->harvest_end_date = null;

                if ($seedingDate) {
                    if ($planting->transplant_days) {
                        $planting->transplant_date = $seedingDate->copy()->addDays($planting->transplant_days);
                    }
                    if ($planting->maturity_days) {
                        $planting->harvest_start_date = $seedingDate->copy()->addDays($planting->maturity_days);
                    }
                    if ($planting->harvest_days && $planting->harvest_start_date) {
                        $planting->harvest_end_date = $planting->harvest_start_date->copy()->addDays($planting->harvest_days);
                    }
                }

                return $planting;
            });
    }

    /**
     * Get available crop plans from farmOS
     */
    private function getCropPlans()
    {
        try {
            // Try to get real plans from farmOS database
            $plans = DB::connection('farmos')
                ->table('plan_record')
                ->where('type', 'plan')
                ->where('status', 1)
                ->select('id', 'name', 'created', 'changed')
                ->orderBy('changed', 'desc')
                ->get()
                ->map(function ($plan) {
                    return (object) [
                        'id' => $plan->id,
                        'name' => $plan->name ?? 'Plan ' . $plan->id,
                        'description' => 'Crop plan from farmOS',
                        'status' => 'active',
                        'created' => $plan->created,
                    ];
                });

            if ($plans->isNotEmpty()) {
                return $plans;
            }

            // Fallback to sample plans if no real plans found
            return collect([
                (object) [
                    'id' => 1,
                    'name' => 'Sample Crop Plan',
                    'description' => 'Sample plan (farmOS connection failed)',
                    'status' => 'sample',
                    'created' => now()->format('Y-m-d'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get crop plans: ' . $e->getMessage());
            // Return sample data as last resort
            return collect([
                (object) [
                    'id' => 1,
                    'name' => 'Sample Crop Plan',
                    'description' => 'Sample plan (farmOS connection failed)',
                    'status' => 'sample',
                    'created' => now()->format('Y-m-d'),
                ],
            ]);
        }
    }
}
