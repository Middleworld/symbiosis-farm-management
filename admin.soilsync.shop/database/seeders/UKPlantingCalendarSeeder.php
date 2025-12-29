<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UKPlantingCalendarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        
        // Typical UK frost dates (general - southern UK)
        $lastFrost = Carbon::create(2025, 5, 15);
        $firstFrost = Carbon::create(2025, 10, 15);
        
        $calendar = [
            // ===== BRUSSELS SPROUTS =====
            [
                'crop_name' => 'Brussels Sprouts',
                'crop_family' => 'Brassica',
                'variety_type' => 'early',
                'indoor_seed_months' => 'Feb-Mar',
                'transplant_months' => 'Apr-May',
                'harvest_months' => 'Sep-Dec',
                'frost_hardy' => true,
                'uk_hardiness_zone' => 'H5', // Hardy down to -15°C
                'typical_last_frost' => $lastFrost,
                'typical_first_frost' => $firstFrost,
                'uk_region' => 'general',
                'seasonal_notes' => 'Early varieties for autumn harvest. Sow under glass Feb-Mar, transplant after last frost.',
                'uk_specific_advice' => 'Frost improves flavour. Can harvest through winter in mild areas. Protect from pigeons with netting.',
                'needs_cloche' => false,
                'needs_fleece' => false,
                'needs_polytunnel' => false,
                'source' => 'RHS Growing Guide + Moles Seeds data',
                'confidence_score' => 9,
            ],
            [
                'crop_name' => 'Brussels Sprouts',
                'crop_family' => 'Brassica',
                'variety_type' => 'maincrop',
                'indoor_seed_months' => 'Mar-Apr',
                'transplant_months' => 'May-Jun',
                'harvest_months' => 'Nov-Mar',
                'frost_hardy' => true,
                'uk_hardiness_zone' => 'H5',
                'typical_last_frost' => $lastFrost,
                'typical_first_frost' => $firstFrost,
                'uk_region' => 'general',
                'seasonal_notes' => 'Maincrop for winter harvest. Transplant 60x60cm spacing. Button up as they mature.',
                'uk_specific_advice' => 'F1 Doric ideal for December-February harvest. Very frost hardy. Pick buttons from bottom up as they size.',
                'source' => 'Moles Seeds F1 Doric variety notes',
                'confidence_score' => 10,
            ],
            
            // ===== CAULIFLOWER =====
            [
                'crop_name' => 'Cauliflower',
                'crop_family' => 'Brassica',
                'variety_type' => 'summer',
                'indoor_seed_months' => 'Mar-May',
                'transplant_months' => 'May-Jun',
                'harvest_months' => 'Jul-Sep',
                'frost_hardy' => false,
                'uk_hardiness_zone' => 'H2', // Tolerates 1-5°C
                'typical_last_frost' => $lastFrost,
                'typical_first_frost' => $firstFrost,
                'uk_region' => 'general',
                'seasonal_notes' => 'Summer varieties need consistent moisture. Transplant after last frost risk.',
                'uk_specific_advice' => 'Protect curds by bending leaves over when curds appear. Water regularly - cauliflower sensitive to dry spells.',
                'needs_cloche' => false,
                'needs_fleece' => false,
                'source' => 'RHS Cauliflower Guide',
                'confidence_score' => 9,
            ],
            [
                'crop_name' => 'Cauliflower',
                'crop_family' => 'Brassica',
                'variety_type' => 'autumn',
                'indoor_seed_months' => 'Apr-May',
                'transplant_months' => 'Jun-Jul',
                'harvest_months' => 'Oct-Dec',
                'frost_hardy' => true,
                'uk_hardiness_zone' => 'H4',
                'typical_last_frost' => $lastFrost,
                'typical_first_frost' => $firstFrost,
                'uk_region' => 'general',
                'seasonal_notes' => 'Autumn varieties more tolerant of cold. Can stand light frosts.',
                'uk_specific_advice' => 'Graffiti F1 (purple) particularly good for autumn. Colour intensifies with cold weather.',
                'source' => 'Moles Seeds variety descriptions',
                'confidence_score' => 9,
            ],
            [
                'crop_name' => 'Cauliflower',
                'crop_family' => 'Brassica',
                'variety_type' => 'winter',
                'indoor_seed_months' => 'May',
                'transplant_months' => 'Jul',
                'harvest_months' => 'Jan-May',
                'frost_hardy' => true,
                'uk_hardiness_zone' => 'H4',
                'typical_last_frost' => $lastFrost,
                'typical_first_frost' => $firstFrost,
                'uk_region' => 'general',
                'seasonal_notes' => 'Overwinter for spring harvest. Most frost-hardy cauliflower type.',
                'uk_specific_advice' => 'Needs mild winter or protection. Best in southwest England or protected spots. Fleece in hard frost.',
                'needs_fleece' => true,
                'source' => 'UK vegetable growers association',
                'confidence_score' => 8,
            ],
            
            // ===== CABBAGE =====
            [
                'crop_name' => 'Cabbage',
                'crop_family' => 'Brassica',
                'variety_type' => 'spring',
                'indoor_seed_months' => null,
                'outdoor_seed_months' => 'Jul-Aug',
                'transplant_months' => 'Sep-Oct',
                'harvest_months' => 'Mar-May',
                'frost_hardy' => true,
                'uk_hardiness_zone' => 'H5',
                'typical_last_frost' => $lastFrost,
                'typical_first_frost' => $firstFrost,
                'uk_region' => 'general',
                'seasonal_notes' => 'Sow in summer, overwinter, harvest in spring. Very hardy.',
                'uk_specific_advice' => 'Spring cabbage (spring greens) very reliable UK crop. Can harvest as greens before heading.',
                'source' => 'Traditional UK growing calendar',
                'confidence_score' => 10,
            ],
            [
                'crop_name' => 'Cabbage',
                'crop_family' => 'Brassica',
                'variety_type' => 'summer',
                'indoor_seed_months' => 'Feb-Mar',
                'transplant_months' => 'Apr-May',
                'harvest_months' => 'Jul-Sep',
                'frost_hardy' => false,
                'uk_hardiness_zone' => 'H2',
                'typical_last_frost' => $lastFrost,
                'typical_first_frost' => $firstFrost,
                'uk_region' => 'general',
                'seasonal_notes' => 'Quick-maturing summer varieties. Sow under glass in late winter.',
                'uk_specific_advice' => 'Hispi F1 and similar pointed types excellent for summer. Quick to mature (90 days).',
                'source' => 'RHS cabbage varieties',
                'confidence_score' => 9,
            ],
            [
                'crop_name' => 'Cabbage',
                'crop_family' => 'Brassica',
                'variety_type' => 'winter',
                'indoor_seed_months' => 'Apr-May',
                'transplant_months' => 'Jun-Jul',
                'harvest_months' => 'Nov-Feb',
                'frost_hardy' => true,
                'uk_hardiness_zone' => 'H5',
                'typical_last_frost' => $lastFrost,
                'typical_first_frost' => $firstFrost,
                'uk_region' => 'general',
                'seasonal_notes' => 'Storage types for winter. Very frost hardy. Improves with frost.',
                'uk_specific_advice' => 'January King types traditional UK winter cabbage. Can stand in ground until needed.',
                'source' => 'Heritage UK varieties',
                'confidence_score' => 10,
            ],
            
            // ===== BROCCOLI / CALABRESE =====
            [
                'crop_name' => 'Calabrese',
                'crop_family' => 'Brassica',
                'variety_type' => 'summer',
                'indoor_seed_months' => 'Mar-May',
                'transplant_months' => 'May-Jun',
                'harvest_months' => 'Jul-Oct',
                'frost_hardy' => false,
                'uk_hardiness_zone' => 'H2',
                'typical_last_frost' => $lastFrost,
                'typical_first_frost' => $firstFrost,
                'uk_region' => 'general',
                'seasonal_notes' => 'Fast-growing heading broccoli. Succession sow for continuous harvest.',
                'uk_specific_advice' => 'Cut main head when tight, before flowering. Side shoots will develop for 4-6 weeks more harvest.',
                'source' => 'UK market garden practice',
                'confidence_score' => 9,
            ],
            [
                'crop_name' => 'Broccoli',
                'crop_family' => 'Brassica',
                'variety_type' => 'purple sprouting',
                'indoor_seed_months' => 'Apr-May',
                'transplant_months' => 'Jun-Jul',
                'harvest_months' => 'Feb-May',
                'frost_hardy' => true,
                'uk_hardiness_zone' => 'H5',
                'typical_last_frost' => $lastFrost,
                'typical_first_frost' => $firstFrost,
                'uk_region' => 'general',
                'seasonal_notes' => 'Overwinter for spring harvest. One of hardiest brassicas. Long harvest period.',
                'uk_specific_advice' => 'Classic UK crop - "hungry gap" vegetable. Pick shoots regularly to encourage more. Extremely reliable.',
                'source' => 'Traditional UK growing',
                'confidence_score' => 10,
            ],
            
            // ===== KALE =====
            [
                'crop_name' => 'Kale',
                'crop_family' => 'Brassica',
                'variety_type' => 'general',
                'indoor_seed_months' => 'Mar-May',
                'transplant_months' => 'May-Jul',
                'harvest_months' => 'Oct-Mar',
                'frost_hardy' => true,
                'uk_hardiness_zone' => 'H7', // Hardy down to -20°C
                'typical_last_frost' => $lastFrost,
                'typical_first_frost' => $firstFrost,
                'uk_region' => 'general',
                'seasonal_notes' => 'Extremely hardy. Flavour improves after frost. Can harvest all winter.',
                'uk_specific_advice' => 'Most reliable winter green for UK. Cavolo nero, curly, red Russian all excellent. Pick and come again.',
                'source' => 'RHS winter vegetables',
                'confidence_score' => 10,
            ],
            
            // ===== LEGUMES =====
            [
                'crop_name' => 'Peas',
                'crop_family' => 'Fabaceae',
                'variety_type' => 'early',
                'indoor_seed_months' => 'Feb-Mar',
                'outdoor_seed_months' => 'Mar-Apr',
                'transplant_months' => null,
                'harvest_months' => 'Jun-Jul',
                'frost_hardy' => true,
                'uk_hardiness_zone' => 'H4',
                'typical_last_frost' => $lastFrost,
                'typical_first_frost' => $firstFrost,
                'uk_region' => 'general',
                'seasonal_notes' => 'Direct sow or start in guttering. Early varieties tolerate cool soil.',
                'uk_specific_advice' => 'Feltham First, Kelvedon Wonder traditional early types. Sow in guttering under glass for transplanting.',
                'needs_cloche' => true,
                'source' => 'Traditional UK pea growing',
                'confidence_score' => 9,
            ],
            [
                'crop_name' => 'Broad Beans',
                'crop_family' => 'Fabaceae',
                'variety_type' => 'autumn sown',
                'outdoor_seed_months' => 'Oct-Nov',
                'transplant_months' => null,
                'harvest_months' => 'May-Jun',
                'frost_hardy' => true,
                'uk_hardiness_zone' => 'H5',
                'typical_last_frost' => $lastFrost,
                'typical_first_frost' => $firstFrost,
                'uk_region' => 'general',
                'seasonal_notes' => 'Autumn sowing for earliest crop. Very hardy overwintering legume.',
                'uk_specific_advice' => 'Aquadulce Claudia best for autumn sowing. Pinch out tops when flowering to prevent blackfly.',
                'source' => 'UK autumn sowing guide',
                'confidence_score' => 10,
            ],
            
            // ===== ALLIUMS =====
            [
                'crop_name' => 'Garlic',
                'crop_family' => 'Allium',
                'variety_type' => 'hardneck',
                'outdoor_seed_months' => 'Oct-Dec',
                'transplant_months' => null,
                'harvest_months' => 'Jun-Jul',
                'frost_hardy' => true,
                'uk_hardiness_zone' => 'H5',
                'typical_last_frost' => $lastFrost,
                'typical_first_frost' => $firstFrost,
                'uk_region' => 'general',
                'seasonal_notes' => 'Plant cloves in autumn. Requires vernalization (cold period). Harvest when leaves yellow.',
                'uk_specific_advice' => 'Plant by shortest day (Dec 21) for best bulbs. Hardneck types more reliable in UK. Mulch in hard winters.',
                'source' => 'UK garlic growers',
                'confidence_score' => 10,
            ],
            [
                'crop_name' => 'Onions',
                'crop_family' => 'Allium',
                'variety_type' => 'sets - autumn',
                'outdoor_seed_months' => 'Sep-Oct',
                'transplant_months' => null,
                'harvest_months' => 'Jun-Jul',
                'frost_hardy' => true,
                'uk_hardiness_zone' => 'H4',
                'typical_last_frost' => $lastFrost,
                'typical_first_frost' => $firstFrost,
                'uk_region' => 'general',
                'seasonal_notes' => 'Autumn sets for early summer harvest. Plant by end October.',
                'uk_specific_advice' => 'Radar, Troy, Shakespeare good autumn varieties. Earlier harvest than spring-planted.',
                'source' => 'RHS onion growing',
                'confidence_score' => 9,
            ],
        ];
        
        foreach ($calendar as &$entry) {
            // Ensure all nullable fields have values
            $entry['variety_type'] = $entry['variety_type'] ?? null;
            $entry['indoor_seed_months'] = $entry['indoor_seed_months'] ?? null;
            $entry['outdoor_seed_months'] = $entry['outdoor_seed_months'] ?? null;
            $entry['transplant_months'] = $entry['transplant_months'] ?? null;
            $entry['harvest_months'] = $entry['harvest_months'] ?? null;
            $entry['uk_hardiness_zone'] = $entry['uk_hardiness_zone'] ?? null;
            $entry['seasonal_notes'] = $entry['seasonal_notes'] ?? null;
            $entry['uk_specific_advice'] = $entry['uk_specific_advice'] ?? null;
            $entry['source'] = $entry['source'] ?? null;
            $entry['needs_cloche'] = $entry['needs_cloche'] ?? false;
            $entry['needs_fleece'] = $entry['needs_fleece'] ?? false;
            $entry['needs_polytunnel'] = $entry['needs_polytunnel'] ?? false;
            
            $entry['created_at'] = $now;
            $entry['updated_at'] = $now;
        }
        
        DB::table('uk_planting_calendar')->insert($calendar);
        
        $this->command->info('✅ Inserted ' . count($calendar) . ' UK planting calendar entries');
    }
}
