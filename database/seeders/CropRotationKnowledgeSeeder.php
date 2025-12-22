<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CropRotationKnowledgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        
        $rotations = [
            // ===== BRASSICAS (Heavy feeders) =====
            // What follows Brussels Sprouts
            [
                'previous_crop' => 'Brussels Sprouts',
                'previous_crop_family' => 'Brassica',
                'following_crop' => 'Legumes (Peas, Beans)',
                'following_crop_family' => 'Fabaceae',
                'relationship' => 'excellent',
                'benefits' => 'Legumes fix nitrogen, replenishing what Brussels sprouts depleted. Breaks brassica disease cycle (clubroot, cabbage root fly).',
                'minimum_gap_months' => 12,
                'breaks_disease_cycle' => true,
                'fixes_nitrogen' => true,
                'depletes_nitrogen' => false,
                'soil_consideration' => 'Heavy feeder (Brussels sprouts) followed by nitrogen-fixer restores soil fertility',
                'cover_crop_recommendation' => 'Winter field beans or crimson clover if gap period',
                'source' => 'Charles Dowding crop rotation principles',
                'confidence_score' => 10,
            ],
            [
                'previous_crop' => 'Brussels Sprouts',
                'previous_crop_family' => 'Brassica',
                'following_crop' => 'Potatoes',
                'following_crop_family' => 'Solanaceae',
                'relationship' => 'good',
                'benefits' => 'Potatoes are different family, break disease cycle. Deep cultivation helps with soil structure after brassicas.',
                'minimum_gap_months' => 12,
                'breaks_disease_cycle' => true,
                'improves_soil_structure' => true,
                'soil_consideration' => 'Potatoes tolerate lower nitrogen after heavy brassica feeding',
                'source' => 'Traditional four-year rotation',
                'confidence_score' => 8,
            ],
            [
                'previous_crop' => 'Brussels Sprouts',
                'previous_crop_family' => 'Brassica',
                'following_crop' => 'Onions/Garlic',
                'following_crop_family' => 'Allium',
                'relationship' => 'good',
                'benefits' => 'Alliums have different pest/disease profile. Light feeders after heavy feeders balances soil.',
                'minimum_gap_months' => 12,
                'breaks_disease_cycle' => true,
                'depletes_nitrogen' => false,
                'soil_consideration' => 'Light feeder following heavy feeder - good soil balance',
                'source' => 'Organic rotation planning',
                'confidence_score' => 8,
            ],
            [
                'previous_crop' => 'Brussels Sprouts',
                'previous_crop_family' => 'Brassica',
                'following_crop' => 'Brassicas',
                'following_crop_family' => 'Brassica',
                'relationship' => 'avoid',
                'risks' => 'Clubroot, cabbage root fly, and other brassica diseases persist in soil. Risk of pest/disease buildup. Depletes specific nutrients.',
                'minimum_gap_months' => 36,
                'breaks_disease_cycle' => false,
                'soil_consideration' => 'Same family crops deplete same nutrients and share pests/diseases',
                'cover_crop_recommendation' => 'Mustard green manure can reduce clubroot, but still avoid back-to-back brassicas',
                'source' => 'RHS crop rotation advice',
                'confidence_score' => 10,
            ],
            
            // ===== CAULIFLOWER =====
            [
                'previous_crop' => 'Cauliflower',
                'previous_crop_family' => 'Brassica',
                'following_crop' => 'Legumes (Peas, Beans)',
                'following_crop_family' => 'Fabaceae',
                'relationship' => 'excellent',
                'benefits' => 'Nitrogen fixation replenishes soil after heavy-feeding cauliflower. Different pest/disease profile.',
                'minimum_gap_months' => 12,
                'breaks_disease_cycle' => true,
                'fixes_nitrogen' => true,
                'soil_consideration' => 'Restores nitrogen balance',
                'source' => 'Market garden rotation best practice',
                'confidence_score' => 10,
            ],
            [
                'previous_crop' => 'Cauliflower',
                'previous_crop_family' => 'Brassica',
                'following_crop' => 'Squash/Courgettes',
                'following_crop_family' => 'Cucurbitaceae',
                'relationship' => 'good',
                'benefits' => 'Squash are heavy feeders but different family. Large leaves suppress weeds. Can add compost for squash.',
                'minimum_gap_months' => 12,
                'breaks_disease_cycle' => true,
                'depletes_nitrogen' => true,
                'soil_consideration' => 'Both heavy feeders - add compost before squash planting',
                'source' => 'Organic vegetable production guide',
                'confidence_score' => 7,
            ],
            [
                'previous_crop' => 'Cauliflower',
                'previous_crop_family' => 'Brassica',
                'following_crop' => 'Brassicas',
                'following_crop_family' => 'Brassica',
                'relationship' => 'avoid',
                'risks' => 'Clubroot spores persist 20+ years. Cabbage root fly, white blister, and other brassica-specific diseases accumulate.',
                'minimum_gap_months' => 36,
                'breaks_disease_cycle' => false,
                'source' => 'RHS disease management',
                'confidence_score' => 10,
            ],
            
            // ===== CABBAGE =====
            [
                'previous_crop' => 'Cabbage',
                'previous_crop_family' => 'Brassica',
                'following_crop' => 'Legumes (Peas, Beans)',
                'following_crop_family' => 'Fabaceae',
                'relationship' => 'excellent',
                'benefits' => 'Nitrogen fixation crucial after nutrient-hungry cabbage. Breaks disease cycle effectively.',
                'minimum_gap_months' => 12,
                'breaks_disease_cycle' => true,
                'fixes_nitrogen' => true,
                'source' => 'Crop rotation best practice',
                'confidence_score' => 10,
            ],
            [
                'previous_crop' => 'Cabbage',
                'previous_crop_family' => 'Brassica',
                'following_crop' => 'Carrots',
                'following_crop_family' => 'Apiaceae',
                'relationship' => 'good',
                'benefits' => 'Root crops follow leaf crops well. Different pest profile. Carrots are light feeders.',
                'minimum_gap_months' => 12,
                'breaks_disease_cycle' => true,
                'soil_consideration' => 'Light feeder after heavy feeder - good balance',
                'source' => 'Traditional rotation',
                'confidence_score' => 8,
            ],
            
            // ===== LEGUMES (Nitrogen fixers) =====
            [
                'previous_crop' => 'Peas',
                'previous_crop_family' => 'Fabaceae',
                'following_crop' => 'Brassicas',
                'following_crop_family' => 'Brassica',
                'relationship' => 'excellent',
                'benefits' => 'Peas fix nitrogen which brassicas need. Perfect pairing - nitrogen-rich soil for heavy feeders.',
                'minimum_gap_months' => 0,
                'breaks_disease_cycle' => true,
                'fixes_nitrogen' => false, // Previous crop already fixed it
                'depletes_nitrogen' => false,
                'soil_consideration' => 'Nitrogen-rich soil from legumes perfect for hungry brassicas',
                'source' => 'Classic crop rotation',
                'confidence_score' => 10,
            ],
            [
                'previous_crop' => 'Beans',
                'previous_crop_family' => 'Fabaceae',
                'following_crop' => 'Brassicas',
                'following_crop_family' => 'Brassica',
                'relationship' => 'excellent',
                'benefits' => 'Beans enrich soil with nitrogen. Brassicas are heavy nitrogen users - perfect match.',
                'minimum_gap_months' => 0,
                'breaks_disease_cycle' => true,
                'soil_consideration' => 'High nitrogen availability from beans suits brassica requirements',
                'source' => 'Nitrogen cycle management',
                'confidence_score' => 10,
            ],
            [
                'previous_crop' => 'Peas',
                'previous_crop_family' => 'Fabaceae',
                'following_crop' => 'Potatoes',
                'following_crop_family' => 'Solanaceae',
                'relationship' => 'good',
                'benefits' => 'Nitrogen-enriched soil benefits potato growth. Different disease families.',
                'minimum_gap_months' => 0,
                'breaks_disease_cycle' => true,
                'source' => 'Four-year rotation system',
                'confidence_score' => 8,
            ],
            [
                'previous_crop' => 'Beans',
                'previous_crop_family' => 'Fabaceae',
                'following_crop' => 'Legumes',
                'following_crop_family' => 'Fabaceae',
                'relationship' => 'poor',
                'risks' => 'Pea and bean weevil, root rot diseases (Fusarium, Aphanomyces) persist. Depletes same micronutrients.',
                'minimum_gap_months' => 24,
                'breaks_disease_cycle' => false,
                'source' => 'Legume disease management',
                'confidence_score' => 9,
            ],
            
            // ===== POTATOES =====
            [
                'previous_crop' => 'Potatoes',
                'previous_crop_family' => 'Solanaceae',
                'following_crop' => 'Brassicas',
                'following_crop_family' => 'Brassica',
                'relationship' => 'good',
                'benefits' => 'Potatoes cultivate and clean soil. Good tilth for brassica transplanting. Different disease profile.',
                'minimum_gap_months' => 0,
                'breaks_disease_cycle' => true,
                'improves_soil_structure' => true,
                'soil_consideration' => 'Well-cultivated soil from potato harvest ideal for brassica planting',
                'source' => 'Traditional UK rotation',
                'confidence_score' => 8,
            ],
            [
                'previous_crop' => 'Potatoes',
                'previous_crop_family' => 'Solanaceae',
                'following_crop' => 'Legumes',
                'following_crop_family' => 'Fabaceae',
                'relationship' => 'acceptable',
                'benefits' => 'Legumes will fix nitrogen depleted by potatoes. Different families.',
                'minimum_gap_months' => 0,
                'breaks_disease_cycle' => true,
                'fixes_nitrogen' => true,
                'source' => 'Crop rotation planning',
                'confidence_score' => 7,
            ],
            [
                'previous_crop' => 'Potatoes',
                'previous_crop_family' => 'Solanaceae',
                'following_crop' => 'Tomatoes',
                'following_crop_family' => 'Solanaceae',
                'relationship' => 'avoid',
                'risks' => 'Late blight (Phytophthora infestans) affects both. Verticillium wilt, potato cyst nematodes persist in soil.',
                'minimum_gap_months' => 36,
                'breaks_disease_cycle' => false,
                'source' => 'Solanaceae disease management',
                'confidence_score' => 10,
            ],
            
            // ===== ALLIUMS =====
            [
                'previous_crop' => 'Onions',
                'previous_crop_family' => 'Allium',
                'following_crop' => 'Brassicas',
                'following_crop_family' => 'Brassica',
                'relationship' => 'good',
                'benefits' => 'Onions are light feeders, leave soil in good condition. Different pest profile from brassicas.',
                'minimum_gap_months' => 0,
                'breaks_disease_cycle' => true,
                'soil_consideration' => 'Light feeder followed by heavy feeder - add compost for brassicas',
                'source' => 'Vegetable rotation',
                'confidence_score' => 8,
            ],
            [
                'previous_crop' => 'Garlic',
                'previous_crop_family' => 'Allium',
                'following_crop' => 'Brassicas',
                'following_crop_family' => 'Brassica',
                'relationship' => 'good',
                'benefits' => 'Garlic harvest (June/July) allows time for soil prep before brassica transplants. Allium residues may have some pest-deterrent effect.',
                'minimum_gap_months' => 0,
                'breaks_disease_cycle' => true,
                'soil_consideration' => 'Summer garlic harvest leaves good window for autumn/winter brassica planting',
                'source' => 'Market garden timing',
                'confidence_score' => 8,
            ],
            [
                'previous_crop' => 'Onions',
                'previous_crop_family' => 'Allium',
                'following_crop' => 'Alliums',
                'following_crop_family' => 'Allium',
                'relationship' => 'avoid',
                'risks' => 'White rot (Sclerotium cepivorum) persists 15+ years. Onion fly, stem and bulb eelworm accumulate.',
                'minimum_gap_months' => 48,
                'breaks_disease_cycle' => false,
                'source' => 'Allium disease persistence research',
                'confidence_score' => 10,
            ],
            
            // ===== COVER CROPS / GREEN MANURES =====
            [
                'previous_crop' => 'Winter Rye',
                'previous_crop_family' => 'Cover Crop',
                'following_crop' => 'Any vegetable',
                'following_crop_family' => null,
                'relationship' => 'excellent',
                'benefits' => 'Suppresses weeds, adds organic matter, improves soil structure. Deep roots break up compaction.',
                'minimum_gap_months' => 0,
                'improves_soil_structure' => true,
                'soil_consideration' => 'Excellent soil conditioner before any crop. Chop and drop or incorporate 2-3 weeks before planting.',
                'source' => 'Green manure best practice',
                'confidence_score' => 9,
            ],
            [
                'previous_crop' => 'Crimson Clover',
                'previous_crop_family' => 'Cover Crop',
                'following_crop' => 'Heavy feeders (Brassicas, Squash)',
                'following_crop_family' => null,
                'relationship' => 'excellent',
                'benefits' => 'Fixes nitrogen (up to 150kg/ha), adds organic matter. Beautiful flowers attract beneficials.',
                'minimum_gap_months' => 0,
                'fixes_nitrogen' => true,
                'improves_soil_structure' => true,
                'soil_consideration' => 'Nitrogen-rich biomass perfect for hungry crops. Incorporate 2-4 weeks before planting.',
                'source' => 'Legume cover crop research',
                'confidence_score' => 10,
            ],
            [
                'previous_crop' => 'Field Beans',
                'previous_crop_family' => 'Cover Crop',
                'following_crop' => 'Brassicas',
                'following_crop_family' => 'Brassica',
                'relationship' => 'excellent',
                'benefits' => 'Winter-hardy nitrogen fixer. Fixes 100-200kg N/ha. Deep roots improve drainage.',
                'minimum_gap_months' => 0,
                'fixes_nitrogen' => true,
                'improves_soil_structure' => true,
                'soil_consideration' => 'Overwinter cover, mow in spring, plant brassicas into nitrogen-rich residue.',
                'source' => 'Winter cover cropping',
                'confidence_score' => 10,
            ],
        ];
        
        foreach ($rotations as &$rotation) {
            // Ensure all fields have values
            $rotation['risks'] = $rotation['risks'] ?? null;
            $rotation['improves_soil_structure'] = $rotation['improves_soil_structure'] ?? false;
            $rotation['fixes_nitrogen'] = $rotation['fixes_nitrogen'] ?? false;
            $rotation['depletes_nitrogen'] = $rotation['depletes_nitrogen'] ?? false;
            $rotation['breaks_disease_cycle'] = $rotation['breaks_disease_cycle'] ?? false;
            $rotation['benefits'] = $rotation['benefits'] ?? null;
            $rotation['soil_consideration'] = $rotation['soil_consideration'] ?? null;
            $rotation['cover_crop_recommendation'] = $rotation['cover_crop_recommendation'] ?? null;
            $rotation['minimum_gap_months'] = $rotation['minimum_gap_months'] ?? null;
            
            $rotation['created_at'] = $now;
            $rotation['updated_at'] = $now;
        }
        
        DB::table('crop_rotation_knowledge')->insert($rotations);
        
        $this->command->info('✅ Inserted ' . count($rotations) . ' crop rotation knowledge entries');
    }
}
