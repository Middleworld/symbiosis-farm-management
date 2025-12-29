<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PlantVariety;
use App\Services\FarmOSApi;
use Illuminate\Support\Facades\Log;

class SyncPlantVarieties extends Command
{
    /**
     * Map botanical genus names to crop families used by admin logic.
     */
    private const GENUS_FAMILY_MAP = [
        'allium' => 'Allium',
        'amaranthus' => 'Amaranthaceae',
        'apium' => 'Apiaceae',
        'beta' => 'Amaranthaceae',
        'brassica' => 'Brassica',
        'capsicum' => 'Solanaceae',
        'cichorium' => 'Asteraceae',
        'citrullus' => 'Cucurbit',
        'cucumis' => 'Cucurbit',
        'cucurbita' => 'Cucurbit',
        'daucus' => 'Apiaceae',
        'foeniculum' => 'Apiaceae',
        'glycine' => 'Legume',
        'hordeum' => 'Poaceae',
        'lactuca' => 'Asteraceae',
        'nicotiana' => 'Solanaceae',
        'oryza' => 'Poaceae',
        'pastinaca' => 'Apiaceae',
        'phaseolus' => 'Legume',
        'petroselinum' => 'Apiaceae',
        'pisum' => 'Legume',
        'secale' => 'Poaceae',
        'solanum' => 'Solanaceae',
        'spinacia' => 'Amaranthaceae',
        'triticum' => 'Poaceae',
        'vicia' => 'Legume',
        'zea' => 'Poaceae',
        'cicer' => 'Legume',
        'anethum' => 'Apiaceae',
        'coriandrum' => 'Apiaceae',
        'asparagus' => 'Asparagaceae',
        'petunia' => 'Solanaceae',
        'tagetes' => 'Asteraceae',
        'viola' => 'Violaceae',
        'begonia' => 'Begoniaceae',
        'dianthus' => 'Caryophyllaceae',
        'pelargonium' => 'Geraniaceae',
        'lobelia' => 'Campanulaceae',
        'antirrhinum' => 'Plantaginaceae',
        'primula' => 'Primulaceae',
        'delphinium' => 'Ranunculaceae',
        'zinnia' => 'Asteraceae',
        'helianthus' => 'Asteraceae',
        'salvia' => 'Lamiaceae',
        'gazania' => 'Asteraceae',
        'cosmos' => 'Asteraceae',
        'lupinus' => 'Legume',
        'bellis' => 'Asteraceae',
        'matthiola' => 'Brassica',
        'callistephus' => 'Asteraceae',
        'verbena' => 'Verbenaceae',
        'cheiranthus' => 'Brassica',
        'campanula' => 'Campanulaceae',
        'rudbeckia' => 'Asteraceae',
        'raphanus' => 'Brassica',
        'scabiosa' => 'Caprifoliaceae',
        'dahlia' => 'Asteraceae',
        'aquilegia' => 'Ranunculaceae',
        'papaver' => 'Papaveraceae',
        'impatiens' => 'Balsaminaceae',
        'myosotis' => 'Boraginaceae',
        'alcea' => 'Malvaceae',
        'mimulus' => 'Phrymaceae',
        'calendula' => 'Asteraceae',
        'tropaeolum' => 'Tropaeolaceae',
        'limonium' => 'Plumbaginaceae',
        'lavandula' => 'Lamiaceae',
        'celosia' => 'Amaranthaceae',
        'digitalis' => 'Plantaginaceae',
        'gaillardia' => 'Asteraceae',
        'lobularia' => 'Brassica',
        'sedum' => 'Crassulaceae',
        'gypsophila' => 'Caryophyllaceae',
        'coreopsis' => 'Asteraceae',
        'tanacetum' => 'Asteraceae',
        'aubrieta' => 'Brassica',
        'canna' => 'Cannaceae',
        'lychnis' => 'Caryophyllaceae',
        'cleome' => 'Cleomaceae',
        'nigella' => 'Ranunculaceae',
        'osteospermum' => 'Asteraceae',
        'cyclamen' => 'Primulaceae',
        'eucalyptus' => 'Myrtaceae',
        'ageratum' => 'Asteraceae',
        'fragaria' => 'Rosaceae',
        'anemone' => 'Ranunculaceae',
        'ipomoea' => 'Convolvulaceae',
        'phlox' => 'Polemoniaceae',
        'silene' => 'Caryophyllaceae',
        'nemesia' => 'Plantaginaceae',
        'heuchera' => 'Saxifragaceae',
        'chrysanthemum' => 'Asteraceae',
        'penstemon' => 'Plantaginaceae',
        'lathyrus' => 'Legume',
        'helichrysum' => 'Asteraceae',
        'saxifraga' => 'Saxifragaceae',
        'achillea' => 'Asteraceae',
        'cynara' => 'Asteraceae',
        'portulaca' => 'Portulacaceae',
        'origanum' => 'Lamiaceae',
        'leucanthemum' => 'Asteraceae',
        'senecio' => 'Asteraceae',
        'clarkia' => 'Onagraceae',
        'iberis' => 'Brassica',
        'helenium' => 'Asteraceae',
        'echium' => 'Boraginaceae',
        'armeria' => 'Plumbaginaceae',
        'erigeron' => 'Asteraceae',
        'lavatera' => 'Malvaceae',
        'monarda' => 'Lamiaceae',
        'eryngium' => 'Apiaceae',
        'agastache' => 'Lamiaceae',
        'potentilla' => 'Rosaceae',
        'sutera' => 'Plantaginaceae',
        'verbascum' => 'Plantaginaceae',
        'dichondra' => 'Convolvulaceae',
        'bergenia' => 'Saxifragaceae',
        'gaura' => 'Onagraceae',
        'linum' => 'Linaceae',
        'geum' => 'Rosaceae',
        'perilla' => 'Lamiaceae',
        'arabis' => 'Brassica',
        'gomphrena' => 'Amaranthaceae',
        'sanvitalia' => 'Asteraceae',
        'kochia' => 'Amaranthaceae',
        'cordyline' => 'Asparagaceae',
        'physostegia' => 'Lamiaceae',
        'saponaria' => 'Caryophyllaceae',
        'carthamus' => 'Asteraceae',
        'platycodon' => 'Campanulaceae',
        'matricaria' => 'Asteraceae',
        'asclepias' => 'Apocynaceae',
        'centaurea' => 'Asteraceae',
        'centranthus' => 'Caprifoliaceae',
        'lepidium' => 'Brassica',
        'hyssopus' => 'Lamiaceae',
        'agrostemma' => 'Caryophyllaceae',
        'euphorbia' => 'Euphorbiaceae',
        'liatris' => 'Asteraceae',
        'nemophila' => 'Boraginaceae',
        'hesperis' => 'Brassica',
        'alyssum' => 'Brassica',
        'pulsatilla' => 'Ranunculaceae',
        'linaria' => 'Plantaginaceae',
        'veronica' => 'Plantaginaceae',
        'nolana' => 'Solanaceae',
        'didiscus' => 'Apiaceae',
        'oenothera' => 'Onagraceae',
        'hypericum' => 'Hypericaceae',
        'gentiana' => 'Gentianaceae',
        'ricinus' => 'Euphorbiaceae',
        'thalictrum' => 'Ranunculaceae',
        'catharanthus' => 'Apocynaceae',
        'thunbergia' => 'Acanthaceae',
        'lythrum' => 'Lythraceae',
        'doronicum' => 'Asteraceae',
        'diplotaxis' => 'Brassica',
        'anthemis' => 'Asteraceae',
        'cobaea' => 'Polemoniaceae',
        'melissa' => 'Lamiaceae',
        'calceolaria' => 'Plantaginaceae',
        'pennisetum' => 'Poaceae',
        'borago' => 'Boraginaceae',
        'arctotis' => 'Asteraceae',
        'grevillea' => 'Proteaceae',
        'tradescantia' => 'Commelinaceae',
        'mimosa' => 'Legume',
        'carum' => 'Apiaceae',
        'abutilon' => 'Malvaceae',
        'echinops' => 'Asteraceae',
        'filipendula' => 'Rosaceae',
        'arenaria' => 'Caryophyllaceae',
        'trollius' => 'Ranunculaceae',
        'corydalis' => 'Papaveraceae',
        'mirabilis' => 'Nyctaginaceae',
        'chamaemelum' => 'Asteraceae',
        'acanthus' => 'Acanthaceae',
        'galium' => 'Rubiaceae',
        'catananche' => 'Asteraceae',
        'solenopsis' => 'Asteraceae',
        'tweedia' => 'Apocynaceae',
        'craspedia' => 'Asteraceae',
        'molucella' => 'Lamiaceae',
        'plectranthus' => 'Lamiaceae',
        'sanguisorba' => 'Rosaceae',
        'xeranthemum' => 'Asteraceae',
        'heliopsis' => 'Asteraceae',
        'sempervivum' => 'Crassulaceae',
        'cuphea' => 'Lythraceae',
        'anthriscus' => 'Apiaceae',
        'helipterum' => 'Asteraceae',
        'polygonum' => 'Polygonaceae',
        'yucca' => 'Asparagaceae',
        'aster' => 'Asteraceae',
        'helleborus' => 'Ranunculaceae',
        'sidalcea' => 'Malvaceae',
        'leontopodium' => 'Asteraceae',
        'ensete' => 'Musaceae',
        'erysimum' => 'Brassica',
        'astrantia' => 'Apiaceae',
        'hemerocallis' => 'Asphodelaceae',
        'cerastium' => 'Caryophyllaceae',
        'lewisia' => 'Montiaceae',
        'barbarea' => 'Brassica',
        'cynoglossum' => 'Boraginaceae',
        'levisticum' => 'Apiaceae',
        'pritzelago' => 'Brassica',
        'aubretia' => 'Brassica',
        'herniaria' => 'Caryophyllaceae',
        'solidago' => 'Asteraceae',
        'cerinthe' => 'Boraginaceae',
        'venidium' => 'Asteraceae',
        'datura' => 'Solanaceae',
        'alchemilla' => 'Rosaceae',
        'felicia' => 'Asteraceae',
        'ptilotus' => 'Amaranthaceae',
        'stachys' => 'Lamiaceae',
        'chaenorhinum' => 'Plantaginaceae',
        'ligularia' => 'Asteraceae',
        'nepeta' => 'Lamiaceae',
        'mentha' => 'Lamiaceae',
        'freesia' => 'Iridaceae',
        'dictamnus' => 'Rutaceae',
        'ruta' => 'Rutaceae',
        'ammobium' => 'Asteraceae',
        'punica' => 'Lythraceae',
        'berlandiera' => 'Asteraceae',
        'lunaria' => 'Brassica',
        'teloxys' => 'Amaranthaceae',
        'veronia' => 'Asteraceae',
        'vernonia' => 'Asteraceae',
        'satureja' => 'Lamiaceae',
        'ammi' => 'Apiaceae',
        'gaultheria' => 'Ericaceae',
        'rhodochiton' => 'Plantaginaceae',
        'bidens' => 'Asteraceae',
        'teucrium' => 'Lamiaceae',
        'pericallis' => 'Asteraceae',
        'lonas' => 'Asteraceae',
        'medicago' => 'Legume',
        'dierama' => 'Iridaceae',
        'orlaya' => 'Apiaceae',
        'artemesia' => 'Asteraceae',
        'artemisia' => 'Asteraceae',
        'anacyclus' => 'Asteraceae',
        'brachycome' => 'Asteraceae',
        'hypoestes' => 'Acanthaceae',
        'lysimachia' => 'Primulaceae',
        'phacelia' => 'Boraginaceae',
        'dodecatheon' => 'Primulaceae',
        'passiflora' => 'Passifloraceae',
        'gerbera' => 'Asteraceae',
        'rosa' => 'Rosaceae',
        'bupleurum' => 'Apiaceae',
        'inula' => 'Asteraceae',
        'cirsium' => 'Asteraceae',
        'angelica' => 'Apiaceae',
        'rumex' => 'Polygonaceae',
        'buphthalmum' => 'Asteraceae',
        'thymophylla' => 'Asteraceae',
        'meconopsis' => 'Papaveraceae',
        'pimpinella' => 'Apiaceae',
        'draba' => 'Brassica',
        'glebionis' => 'Asteraceae',
        'sinningia' => 'Gesneriaceae',
        'sagina' => 'Caryophyllaceae',
        'cactaceae' => 'Cactaceae',
        'jasione' => 'Campanulaceae',
        'thymus' => 'Lamiaceae',
        'melampodium' => 'Asteraceae',
        'dracocephalum' => 'Lamiaceae',
        'perovskia' => 'Lamiaceae',
        'eruca' => 'Brassica',
        'fatsia' => 'Araliaceae',
        'salpiglossis' => 'Solanaceae',
        'kniphofia' => 'Asphodelaceae',
        'tithonia' => 'Asteraceae',
        'convolvulus' => 'Convolvulaceae',
        'eccremocarpus' => 'Bignoniaceae',
        'dipsacus' => 'Caprifoliaceae',
    ];

    /**
     * Keyword mapping to detect crop families when genus is unavailable.
     */
    private const FAMILY_KEYWORD_MAP = [
        'Allium' => ['allium', 'onion', 'garlic', 'leek', 'shallot', 'chive'],
        'Amaranthaceae' => ['amaranth', 'spinach', 'chard', 'beet', 'beta', 'chenopod'],
        'Apiaceae' => ['apiaceae', 'umbell', 'carrot', 'parsley', 'celery', 'celeriac', 'fennel', 'coriander', 'cilantro', 'dill', 'parsnip'],
        'Asteraceae' => ['asteraceae', 'compositae', 'lettuce', 'endive', 'chicory', 'radicchio'],
        'Brassica' => ['brassica', 'crucifer', 'cole', 'cabbage', 'broccoli', 'cauliflower', 'sprout', 'kalette', 'kale', 'mustard', 'rapa'],
        'Cucurbit' => ['cucurbit', 'cucurbitaceae', 'squash', 'pumpkin', 'courgette', 'zucchini', 'melon', 'gourd', 'cucumber'],
        'Legume' => ['legume', 'fabaceae', 'bean', 'pea', 'lentil', 'vetch', 'clover', 'vicia', 'phaseolus', 'pisum', 'cicer'],
        'Poaceae' => ['poaceae', 'graminea', 'grass', 'corn', 'maize', 'sweetcorn', 'wheat', 'barley', 'oat', 'rye'],
        'Solanaceae' => ['solanaceae', 'nightshade', 'tomato', 'potato', 'pepper', 'aubergine', 'eggplant', 'chilli', 'capsicum', 'physalis'],
        'Polygonaceae' => ['polygonaceae', 'rhubarb', 'sorrel', 'buckwheat'],
        'Lamiaceae' => ['lamiaceae', 'mint', 'basil', 'oregano', 'sage', 'thyme', 'rosemary', 'hyssop', 'monarda', 'perilla', 'stachys', 'teucrium', 'thymus', 'dracocephalum', 'perovskia', 'melissa', 'nepeta', 'physostegia'],
        'Asparagaceae' => ['asparagaceae', 'asparagus', 'liliaceae'],
        'Violaceae' => ['violaceae', 'viola', 'violet'],
        'Begoniaceae' => ['begoniaceae', 'begonia'],
        'Caryophyllaceae' => ['caryophyllaceae', 'dianthus', 'pinks', 'carnation', 'gypsophila', 'lychnis'],
        'Geraniaceae' => ['geraniaceae', 'geranium', 'pelargonium'],
        'Campanulaceae' => ['campanulaceae', 'campanula', 'lobelia', 'bellflower'],
        'Plantaginaceae' => ['plantaginaceae', 'antirrhinum', 'snapdragon', 'digitalis', 'foxglove'],
        'Primulaceae' => ['primulaceae', 'primula', 'primrose'],
        'Ranunculaceae' => ['ranunculaceae', 'delphinium', 'aconitum', 'aquilegia', 'nigella', 'ranunculus'],
        'Asteraceae' => ['asteraceae', 'compositae', 'lettuce', 'endive', 'chicory', 'radicchio', 'zinnia', 'helianthus', 'rudbeckia', 'cosmos', 'gaillardia', 'tanacetum', 'callistephus', 'calendula', 'coreopsis', 'osteospermum', 'bellis', 'tagetes', 'gazania', 'achillea', 'chrysanthemum', 'senecio', 'leucanthemum', 'erigeron', 'helenium', 'liatris', 'sanvitalia', 'carthamus', 'matricaria', 'anthemis', 'doronicum', 'arctotis', 'echinops', 'catananche', 'solenopsis', 'craspedia', 'heliopsis', 'venidium', 'felicia', 'bidens', 'pericallis', 'lonas', 'gerbera', 'inula', 'cirsium', 'buphthalmum', 'thymophylla', 'glebionis', 'melampodium', 'tithonia', 'vernonia', 'veronia'],
        'Verbenaceae' => ['verbenaceae', 'verbena'],
        'Caprifoliaceae' => ['caprifoliaceae', 'dipsacaceae', 'scabiosa'],
        'Papaveraceae' => ['papaveraceae', 'papaver', 'poppy'],
        'Balsaminaceae' => ['balsaminaceae', 'impatiens'],
        'Boraginaceae' => ['boraginaceae', 'myosotis', 'forget-me-not'],
        'Phrymaceae' => ['phrymaceae', 'mimulus'],
        'Tropaeolaceae' => ['tropaeolaceae', 'tropaeolum', 'nasturtium'],
        'Plumbaginaceae' => ['plumbaginaceae', 'limonium', 'statice'],
        'Crassulaceae' => ['crassulaceae', 'sedum'],
        'Cleomaceae' => ['cleomaceae', 'cleome'],
        'Cannaceae' => ['cannaceae', 'canna'],
        'Myrtaceae' => ['myrtaceae', 'eucalyptus', 'myrtle'],
        'Convolvulaceae' => ['convolvulaceae', 'ipomoea', 'convolvulus', 'bindweed', 'dichondra'],
        'Polemoniaceae' => ['polemoniaceae', 'phlox'],
        'Saxifragaceae' => ['saxifragaceae', 'saxifraga', 'bergenia', 'heuchera'],
        'Rosaceae' => ['rosaceae', 'fragaria', 'potentilla', 'geum', 'alchemilla', 'rosa'],
        'Onagraceae' => ['onagraceae', 'gaura', 'oenothera', 'clarkia'],
        'Linaceae' => ['linaceae', 'linum'],
        'Portulacaceae' => ['portulacaceae', 'portulaca'],
        'Euphorbiaceae' => ['euphorbiaceae', 'euphorbia', 'ricinus'],
        'Apocynaceae' => ['apocynaceae', 'asclepias', 'catharanthus', 'tweedia'],
        'Lythraceae' => ['lythraceae', 'lythrum', 'cuphea', 'punica'],
        'Nyctaginaceae' => ['nyctaginaceae', 'mirabilis'],
        'Hypericaceae' => ['hypericaceae', 'hypericum'],
        'Gentianaceae' => ['gentianaceae', 'gentiana'],
        'Rutaceae' => ['rutaceae', 'dictamnus', 'ruta'],
        'Proteaceae' => ['proteaceae', 'grevillea'],
        'Commelinaceae' => ['commelinaceae', 'tradescantia'],
        'Musaceae' => ['musaceae', 'ensete'],
        'Iridaceae' => ['iridaceae', 'dierama', 'freesia'],
        'Asphodelaceae' => ['asphodelaceae', 'hemerocallis', 'kniphofia'],
        'Montiaceae' => ['montiaceae', 'lewisia'],
        'Araliaceae' => ['araliaceae', 'fatsia'],
        'Bignoniaceae' => ['bignoniaceae', 'eccremocarpus'],
        'Ericaceae' => ['ericaceae', 'gaultheria'],
        'Gesneriaceae' => ['gesneriaceae', 'sinningia'],
        'Passifloraceae' => ['passifloraceae', 'passiflora'],
        'Iridaceae' => ['iridaceae', 'iris', 'dierama', 'freesia'],
        'Gentianaceae' => ['gentianaceae', 'gentiana'],
    ];

    private const FARMOS_CROP_FAMILY_NAME_MAP = [
        'Allium' => 'Allium',
        'Amaranthaceae' => 'Amaranthaceae',
        'Apiaceae' => 'Apiaceae',
        'Asteraceae' => 'Asteraceae',
        'Brassica' => 'Brassicaceae',
        'Cucurbit' => 'Cucurbitaceae',
        'Legume' => 'Leguminosae',
        'Poaceae' => 'Gramineae',
        'Solanaceae' => 'Solanaceae',
        'Lamiaceae' => 'Lamiaceae',
        'Malvaceae' => 'Malvaceae',
        'Rosaceae' => 'Rosaceae',
        'Portulacaceae' => 'Portulacaceae',
        'Convolvulaceae' => 'Convolvulaceae'
    ];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'farmos:sync-varieties {--force : Force sync all varieties regardless of last sync time} {--push-to-farmos : Write inferred metadata back to FarmOS taxonomy terms}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync plant variety data from FarmOS to local database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🌱 Starting FarmOS variety sync...');

        $force = $this->option('force');
        $pushToFarmOS = $this->option('push-to-farmos');

        try {
            $farmOSApi = app(FarmOSApi::class);

            $cropFamilyTermMap = [];

            if ($pushToFarmOS) {
                $this->info('🔄 Fetching crop family taxonomy terms from FarmOS...');
                $cropFamilyTerms = $farmOSApi->getCropFamilies();
                foreach ($cropFamilyTerms as $term) {
                    $termName = $term['attributes']['name'] ?? null;
                    if ($termName) {
                        $cropFamilyTermMap[mb_strtolower($termName)] = $term['id'];
                    }
                }

                if (empty($cropFamilyTermMap)) {
                    $this->warn('⚠️ Unable to load crop family taxonomy terms; skipping FarmOS updates.');
                    $pushToFarmOS = false;
                }
            }

            // Get all plant types first
            $this->info('📋 Fetching plant types from FarmOS...');
            $plantTypes = $farmOSApi->getPlantTypes();

            if (empty($plantTypes)) {
                $this->error('❌ No plant types found in FarmOS');
                return 1;
            }

            $this->info("📊 Found " . count($plantTypes) . " plant types");

            // Get all varieties
            $this->info('🌿 Fetching plant varieties from FarmOS...');
            $varieties = $farmOSApi->getVarieties();

            if (empty($varieties)) {
                $this->error('❌ No varieties found in FarmOS');
                return 1;
            }

            $this->info("🌱 Found " . count($varieties) . " varieties to process");

            // Create a lookup map for plant types
            $plantTypeMap = [];
            foreach ($plantTypes as $type) {
                $plantTypeMap[$type['id']] = $type;
            }

            $processed = 0;
            $updated = 0;
            $created = 0;
            $skipped = 0;
            $errors = 0;
            $pushed = 0;
            $pushFailures = 0;

            $progressBar = $this->output->createProgressBar(count($varieties));
            $progressBar->start();

            foreach ($varieties as $variety) {
                try {
                    $result = $this->syncVariety(
                        $variety,
                        $plantTypeMap,
                        $force,
                        $pushToFarmOS,
                        $cropFamilyTermMap,
                        $farmOSApi
                    );

                    $processed++;

                    switch ($result['status'] ?? 'skipped') {
                        case 'created':
                            $created++;
                            break;
                        case 'updated':
                            $updated++;
                            break;
                        case 'skipped':
                            $skipped++;
                            break;
                    }

                    if ($pushToFarmOS) {
                        $pushStatus = $result['push'] ?? null;
                        if ($pushStatus === 'pushed') {
                            $pushed++;
                        } elseif ($pushStatus === 'failed') {
                            $pushFailures++;
                            if (!empty($result['push_error'])) {
                                $this->warn("⚠️ Push failed for {$variety['id']}: {$result['push_error']}");
                            }
                        }
                    }

                    $progressBar->advance();

                } catch (\Exception $e) {
                    $this->error("❌ Error processing variety {$variety['id']}: " . $e->getMessage());
                    $errors++;
                    $progressBar->advance();
                }
            }

            $progressBar->finish();
            $this->newLine(2);

            $this->info("🎉 Sync complete!");
            $this->info("📊 Summary:");
            $this->info("   ✅ Created: {$created}");
            $this->info("   🔄 Updated: {$updated}");
            $this->info("   ⏭️  Skipped: {$skipped}");
            if ($pushToFarmOS) {
                $this->info("   📤 Pushed to FarmOS: {$pushed}");
                if ($pushFailures > 0) {
                    $this->info("   ⚠️ FarmOS push failures: {$pushFailures}");
                }
            }
            $this->info("   ❌ Errors: {$errors}");
            $this->info("   📊 Total processed: {$processed}");

            Log::info('Plant variety sync completed', [
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => $errors,
                'total_processed' => $processed,
                'pushed' => $pushToFarmOS ? $pushed : null,
                'push_failures' => $pushToFarmOS ? $pushFailures : null
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error('💥 Fatal error during sync: ' . $e->getMessage());
            Log::error('PlantVariety sync command failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Sync a single variety
     */
    private function syncVariety(
        array $variety,
        array $plantTypeMap,
        bool $force,
        bool $pushToFarmOS = false,
        array $cropFamilyTermMap = [],
        ?FarmOSApi $farmOSApi = null
    ): array
    {
        $attributes = $variety['attributes'] ?? [];
        $relationships = $variety['relationships'] ?? [];

        // Check if we need to sync this variety
        if (!$force && !$pushToFarmOS) {
            $existing = PlantVariety::where('farmos_id', $variety['id'])->first();
            if ($existing && $existing->last_synced_at && $existing->last_synced_at->diffInDays(now()) < 7) {
                return ['status' => 'skipped'];
            }
        }

        // Get parent plant type
        $parentId = null;
        if (isset($relationships['parent']['data']) && is_array($relationships['parent']['data'])) {
            foreach ($relationships['parent']['data'] as $parent) {
                if (isset($parent['id'])) {
                    $parentId = $parent['id'];
                    break;
                }
            }
        }

        $plantTypeData = $parentId && isset($plantTypeMap[$parentId]) ? $plantTypeMap[$parentId] : null;

        // Extract description
        $description = '';
        if (isset($attributes['description']['value'])) {
            $description = $attributes['description']['value'];
        }

        // Parse description for additional data
        $parsedData = $this->parseDescription($description, $plantTypeData, $attributes['name'] ?? '');

        // Extract image URL from relationships
        $imageUrl = null;
        if (isset($relationships['image']['data']) && !empty($relationships['image']['data'])) {
            $imageFileId = $relationships['image']['data'][0]['id'] ?? null;
            if ($imageFileId) {
                // Construct farmOS file URL
                $imageUrl = rtrim($this->baseUrl ?? config('services.farmos.url', 'https://farmos.middleworldfarms.org'), '/') . 
                           '/api/file/file/' . $imageFileId;
            }
        }

        // Ensure crop family aligns with farmOS taxonomy and botanical context
        $parsedData['family'] = $this->inferCropFamily(
            $parsedData['family'] ?? null,
            $parsedData['scientific_name'] ?? null,
            $plantTypeData,
            $attributes['name'] ?? '',
            $description
        );

        // Prepare data for database
        $data = [
            'farmos_id' => $variety['id'] ?? '',
            'farmos_tid' => $attributes['drupal_internal__tid'] ?? null,
            'name' => $attributes['name'] ?? 'Unknown',
            'description' => $description,
            'image_url' => $imageUrl,
            'scientific_name' => $parsedData['scientific_name'] ?? null,
            'crop_family' => $parsedData['family'] ?? null,
            'plant_type' => $plantTypeData ? ($plantTypeData['attributes']['name'] ?? null) : null,
            'plant_type_id' => $parentId,
            'maturity_days' => $parsedData['maturity_days'] ?? $attributes['maturity_days'] ?? null,
            'transplant_days' => $parsedData['transplant_days'] ?? $attributes['transplant_days'] ?? null,
            'harvest_days' => $parsedData['harvest_days'] ?? $attributes['harvest_days'] ?? null,
            'min_temperature' => $parsedData['min_temp'] ?? null,
            'max_temperature' => $parsedData['max_temp'] ?? null,
            'optimal_temperature' => $parsedData['optimal_temp'] ?? null,
            'season' => $parsedData['season'] ?? null,
            'frost_tolerance' => $parsedData['frost_tolerance'] ?? null,
            'companions' => isset($relationships['companions']['data']) ? $relationships['companions']['data'] : null,
            'external_uris' => $attributes['external_uri'] ?? null,
            'farmos_data' => $variety, // Store complete FarmOS response
            'is_active' => true,
            'last_synced_at' => now(),
            'sync_status' => 'synced'
        ];

        $optionalFields = [
            'germination_days_min',
            'germination_days_max',
            'germination_temp_min',
            'germination_temp_max',
            'germination_temp_optimal',
            'planting_depth_inches',
            'seed_spacing_inches',
            'row_spacing_inches',
            'seeds_per_hole',
            'requires_light_for_germination',
            'seed_starting_notes',
            'seed_type',
            'transplant_notes',
            'hardening_off_days',
            'hardening_off_notes'
        ];

        foreach ($optionalFields as $field) {
            if (array_key_exists($field, $parsedData)) {
                $data[$field] = $parsedData[$field];
            }
        }

        // Use updateOrCreate to handle duplicates
        $existing = PlantVariety::where('farmos_id', $data['farmos_id'])->first();
        $status = 'created';

        if ($existing) {
            $existing->update($data);
            $status = 'updated';
        } else {
            PlantVariety::create($data);
        }

        $result = ['status' => $status];

        if ($pushToFarmOS && $farmOSApi) {
            try {
                $pushOutcome = $this->pushVarietyMetadata(
                    $variety,
                    $data,
                    $farmOSApi,
                    $cropFamilyTermMap
                );

                if (!empty($pushOutcome['status'])) {
                    $result['push'] = $pushOutcome['status'];
                }

                if (!empty($pushOutcome['error'])) {
                    $result['push_error'] = $pushOutcome['error'];
                }

            } catch (\Exception $e) {
                $result['push'] = 'failed';
                $result['push_error'] = $e->getMessage();
                Log::warning('FarmOS push failed', [
                    'variety_id' => $variety['id'] ?? null,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $result;
    }

    private function pushVarietyMetadata(
        array $variety,
        array $data,
        FarmOSApi $farmOSApi,
        array $cropFamilyTermMap
    ): array {
        $varietyId = $variety['id'] ?? null;
        if (!$varietyId) {
            return [
                'status' => 'failed',
                'error' => 'Missing variety ID'
            ];
        }

        $attributes = array_filter([
            'maturity_days' => $data['maturity_days'] ?? null,
            'transplant_days' => $data['transplant_days'] ?? null,
            'harvest_days' => $data['harvest_days'] ?? null,
        ], static fn($value) => $value !== null);

        $cropFamilyUuid = null;
        $cropFamilyName = $data['crop_family'] ?? null;
        if (!empty($cropFamilyName)) {
            $canonicalName = self::FARMOS_CROP_FAMILY_NAME_MAP[$cropFamilyName] ?? $cropFamilyName;
            $lookupKey = mb_strtolower($canonicalName);
            if (isset($cropFamilyTermMap[$lookupKey])) {
                $cropFamilyUuid = $cropFamilyTermMap[$lookupKey];
            } else {
                Log::debug('No matching FarmOS crop family term found', [
                    'variety_id' => $varietyId,
                    'family' => $cropFamilyName,
                    'canonical' => $canonicalName
                ]);
            }
        }

        if (empty($attributes) && !$cropFamilyUuid) {
            return ['status' => 'skipped'];
        }

        $response = $farmOSApi->updatePlantTypeTerm($varietyId, $attributes, $cropFamilyUuid);

        if (($response['status'] ?? 500) >= 200 && ($response['status'] ?? 500) < 300) {
            return ['status' => 'pushed'];
        }

        $errorDetails = $response['body']['errors'] ?? $response['body'] ?? null;
        $errorMessage = 'HTTP ' . ($response['status'] ?? 'unknown');
        if ($errorDetails) {
            $encoded = json_encode($errorDetails, JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
            if ($encoded) {
                $errorMessage .= ' ' . mb_substr($encoded, 0, 500);
            }
        }

        Log::warning('FarmOS variety metadata push failed', [
            'variety_id' => $varietyId,
            'status' => $response['status'] ?? null,
            'body' => $response['body'] ?? null
        ]);

        return [
            'status' => 'failed',
            'error' => $errorMessage
        ];
    }

    /**
     * Parse description text for structured data
     */
    private function parseDescription(string $description, ?array $plantTypeData = null, string $varietyName = ''): array
    {
        $data = [];

        if (empty($description)) {
            return $data;
        }

        $text = strip_tags($description);
        $text = preg_replace('/\s+/', ' ', $text);

        $labels = $this->extractLabeledSegments($text);

        if (isset($labels['scientific name'])) {
            $data['scientific_name'] = trim($labels['scientific name']);
        } elseif (isset($labels['botanical name'])) {
            $data['scientific_name'] = trim($labels['botanical name']);
        }

        if (isset($labels['family'])) {
            $data['family'] = $this->normalizeFamilyName($labels['family']);
        } elseif (isset($labels['crop family'])) {
            $data['family'] = $this->normalizeFamilyName($labels['crop family']);
        }

        if (isset($labels['season'])) {
            $data['season'] = trim($labels['season']);
        }

        foreach (['growing days', 'days to maturity', 'maturity', 'days to harvest', 'harvest days'] as $label) {
            if (isset($labels[$label])) {
                $duration = $this->parseDurationToDays($labels[$label]);
                if ($duration) {
                    $data['maturity_days'] = $duration['max'];
                    break;
                }
            }
        }

        if (isset($labels['transplant days'])) {
            $duration = $this->parseDurationToDays($labels['transplant days']);
            if ($duration) {
                $data['transplant_days'] = $duration['max'];
            }
        }

        if (isset($labels['temperature range'])) {
            $temps = $this->parseTemperatureRange($labels['temperature range']);
            if ($temps) {
                $data['min_temp'] = $temps['min'];
                $data['max_temp'] = $temps['max'];
                $data['optimal_temp'] = $temps['optimal'];
            }
        }

        $sentences = preg_split('/(?<=[.!?])\s+/u', $text);
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if ($sentence === '') {
                continue;
            }

            $lower = mb_strtolower($sentence);

            if (!isset($data['scientific_name']) && preg_match('/botanical name\s*[:\-]\s*([A-Za-z0-9\s\-]+)/i', $sentence, $match)) {
                $data['scientific_name'] = trim($match[1]);
            }

            if (!isset($data['germination_days_min']) && (str_contains($lower, 'germin') || str_contains($lower, 'emerge'))) {
                $duration = $this->parseDurationToDays($sentence);
                if ($duration) {
                    $data['germination_days_min'] = $duration['min'];
                    $data['germination_days_max'] = $duration['max'];
                }
            }

            if (!isset($data['maturity_days']) && $this->sentenceRefersToMaturity($lower)) {
                $duration = $this->parseDurationToDays($sentence);
                if ($duration) {
                    $data['maturity_days'] = $duration['max'];
                }
            }

            if (!isset($data['transplant_days']) && str_contains($lower, 'transplant')) {
                $duration = $this->parseDurationToDays($sentence);
                if ($duration) {
                    $data['transplant_days'] = $duration['max'];
                }
            }

            if (!isset($data['family']) && preg_match('/family\s*[:\-]\s*([A-Za-z\s]+)/i', $sentence, $match)) {
                $data['family'] = $this->normalizeFamilyName($match[1]);
            }

            if (!isset($data['frost_tolerance']) && str_contains($lower, 'frost')) {
                if (str_contains($lower, 'hardy') || str_contains($lower, 'tolerant')) {
                    $data['frost_tolerance'] = 'hardy';
                } elseif (str_contains($lower, 'tender') || str_contains($lower, 'sensitive')) {
                    $data['frost_tolerance'] = 'tender';
                }
            }

            if (!array_key_exists('requires_light_for_germination', $data)) {
                if (str_contains($lower, 'requires light for germination') || str_contains($lower, 'needs light for germination')) {
                    $data['requires_light_for_germination'] = true;
                } elseif (str_contains($lower, 'no light for germination') || str_contains($lower, 'does not require light for germination')) {
                    $data['requires_light_for_germination'] = false;
                }
            }
        }

        return $data;
    }

    private function extractLabeledSegments(string $text): array
    {
        $pairs = [];

        if (preg_match_all('/([A-Za-z0-9][A-Za-z0-9\s\/\-&\(\)]+):\s*([^\.]+)(?:\.|$)/u', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $label = mb_strtolower(trim($match[1]));
                $value = trim($match[2]);
                if ($value !== '') {
                    $pairs[$label] = $value;
                }
            }
        }

        return $pairs;
    }

    private function parseDurationToDays(string $value): ?array
    {
        if (preg_match('/(\d+)\s*-\s*(\d+)\s*(?:day|days)/i', $value, $matches)) {
            return ['min' => (int) $matches[1], 'max' => (int) $matches[2]];
        }

        if (preg_match('/(\d+)\s*(?:day|days)/i', $value, $matches)) {
            $days = (int) $matches[1];
            return ['min' => $days, 'max' => $days];
        }

        if (preg_match('/(\d+)\s*-\s*(\d+)\s*(?:week|weeks)/i', $value, $matches)) {
            return ['min' => (int) $matches[1] * 7, 'max' => (int) $matches[2] * 7];
        }

        if (preg_match('/(\d+)\s*(?:week|weeks)/i', $value, $matches)) {
            $days = (int) $matches[1] * 7;
            return ['min' => $days, 'max' => $days];
        }

        return null;
    }

    private function parseTemperatureRange(string $value): ?array
    {
        if (preg_match('/(-?\d+(?:\.\d+)?)°?[cf]?\s*(?:-|to)\s*(-?\d+(?:\.\d+)?)°?[cf]?/i', $value, $matches)) {
            $min = (float) $matches[1];
            $max = (float) $matches[2];

            return [
                'min' => $min,
                'max' => $max,
                'optimal' => ($min + $max) / 2,
            ];
        }

        return null;
    }

    private function sentenceRefersToMaturity(string $sentence): bool
    {
        foreach (['matur', 'harvest', 'ready', 'pick in', 'crop in', 'after sowing'] as $keyword) {
            if (str_contains($sentence, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeFamilyName(string $family): string
    {
        $normalized = mb_strtolower(trim($family));

        $map = [
            'fabaceae' => 'Legume',
            'legume' => 'Legume',
            'legumes' => 'Legume',
            'brassicaceae' => 'Brassica',
            'crucifer' => 'Brassica',
            'allium' => 'Allium',
            'alliaceae' => 'Allium',
            'amaryllidaceae' => 'Allium',
            'solanaceae' => 'Solanaceae',
            'nightshade' => 'Solanaceae',
            'cucurbitaceae' => 'Cucurbit',
            'gourd' => 'Cucurbit',
            'apiaceae' => 'Apiaceae',
            'umbelliferae' => 'Apiaceae',
            'poaceae' => 'Poaceae',
            'gramineae' => 'Poaceae',
            'amaranthaceae' => 'Amaranthaceae',
            'chenopodiaceae' => 'Amaranthaceae',
            'asteraceae' => 'Asteraceae',
            'compositae' => 'Asteraceae',
            'polygonaceae' => 'Polygonaceae',
            'lamiaceae' => 'Lamiaceae',
            'asparagaceae' => 'Asparagaceae',
            'liliaceae' => 'Asparagaceae',
        ];

        return $map[$normalized] ?? ucwords($normalized);
    }

    private function inferCropFamily(?string $family, ?string $scientificName, ?array $plantTypeData, string $varietyName, string $description): ?string
    {
        if ($family) {
            return $this->normalizeFamilyName($family);
        }

        if ($scientificName) {
            $genus = mb_strtolower(strtok($scientificName, ' '));
            if ($genus && isset(self::GENUS_FAMILY_MAP[$genus])) {
                return self::GENUS_FAMILY_MAP[$genus];
            }
        }

        $candidates = [];

        if ($plantTypeData && isset($plantTypeData['attributes']['name'])) {
            $candidates[] = mb_strtolower($plantTypeData['attributes']['name']);
        }

        if ($varietyName) {
            $candidates[] = mb_strtolower($varietyName);
        }

        if ($description) {
            $candidates[] = mb_strtolower($description);
        }

        return $this->matchFamilyByKeywords($candidates);
    }

    private function matchFamilyByKeywords(array $sources): ?string
    {
        foreach ($sources as $text) {
            if (!$text) {
                continue;
            }

            foreach (self::FAMILY_KEYWORD_MAP as $family => $keywords) {
                foreach ($keywords as $keyword) {
                    if (mb_strpos($text, $keyword) !== false) {
                        return $family;
                    }
                }
            }
        }

        return null;
    }
}
