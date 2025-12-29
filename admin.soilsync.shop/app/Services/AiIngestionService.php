<?php
namespace App\Services;

use App\Models\{AiIngestionTask,AiDataset,AiDatasetSnapshot};
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AiIngestionService
{
    public function createTask(string $type, array $params = [], ?int $userId = null): AiIngestionTask
    {
        return AiIngestionTask::create([
            'type' => $type,
            'params' => $params,
            'created_by' => $userId,
            'status' => 'pending'
        ]);
    }

    public function runPending(int $max = 5): int
    {
        $count = 0;
        AiIngestionTask::where('status','pending')->orderBy('id')->limit($max)->get()->each(function($task) use (&$count) {
            $this->runTask($task);
            $count++;
        });
        return $count;
    }

    public function runTask(AiIngestionTask $task): void
    {
        $task->update(['status' => 'running','started_at' => now(), 'progress' => 0]);
        try {
            switch ($task->type) {
                case 'refresh_farmos_taxonomy':
                    $this->ingestFarmOsTaxonomy($task);
                    break;
                case 'import_crops_csv':
                    $this->importCropsCsv($task);
                    break;
                case 'analyze_yield_performance':
                    $this->analyzeYieldPerformance($task);
                    break;
                default:
                    throw new \RuntimeException('Unknown task type: '.$task->type);
            }
            $task->update(['status' => 'completed','progress' => 100,'finished_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('Ingestion task failed '.$task->id.': '.$e->getMessage());
            $task->update(['status' => 'failed','error' => $e->getMessage(),'finished_at' => now()]);
        }
    }

    private function ingestFarmOsTaxonomy(AiIngestionTask $task): void
    {
        $which = $task->params['which'] ?? 'plant_type';
        $svc = app(FarmOSApiService::class);
        $methodMap = [
            'plant_type' => 'getPlantTypes',
            'variety' => 'getVarieties',
            'crop_family' => 'getCropFamilies',
            'location' => 'getLocations',
        ];
        if (!isset($methodMap[$which])) { throw new \RuntimeException('Unsupported taxonomy: '.$which); }
        $data = $svc->{$methodMap[$which]}();
        $this->storeDatasetSnapshot('taxonomy_'.$which, $data);
    }

    private function importCropsCsv(AiIngestionTask $task): void
    {
        $path = $task->params['path'] ?? null;
        if (!$path || !Storage::disk('local')->exists($path)) {
            throw new \RuntimeException('CSV not found: '.$path);
        }
        $csv = Storage::disk('local')->get($path);
        $rows = array_map('str_getcsv', preg_split('/\r?\n/', trim($csv)));
        $header = array_shift($rows);
        $data = [];
        foreach ($rows as $r) {
            if (count($r) !== count($header)) continue;
            $data[] = array_combine($header, $r);
        }
        $this->storeDatasetSnapshot('crops_csv', $data, ['source_path' => $path]);
    }

    private function analyzeYieldPerformance(AiIngestionTask $task): void
    {
        // Placeholder: would aggregate harvest logs vs expectations
        $summary = [
            'status' => 'not_implemented',
            'note' => 'Implement yield analysis after expectation dataset exists.'
        ];
        $this->storeDatasetSnapshot('yield_performance', $summary);
    }

    private function storeDatasetSnapshot(string $dataset, $data, array $meta = []): void
    {
        DB::transaction(function() use ($dataset, $data, $meta) {
            $recordCount = is_array($data) ? (isset($data['data']) && is_array($data['data']) ? count($data['data']) : count($data)) : 1;
            $hash = substr(hash('sha256', json_encode($data)),0,40);
            $ds = AiDataset::firstOrCreate(['dataset' => $dataset]);
            $version = $ds->current_version + 1;
            $path = 'ai_datasets/'.$dataset.'_v'.$version.'_'.Str::random(6).'.json';
            Storage::disk('local')->put($path, json_encode($data));
            AiDatasetSnapshot::create([
                'dataset' => $dataset,
                'version' => $version,
                'row_count' => $recordCount,
                'source_hash' => $hash,
                'storage_path' => $path,
                'meta' => $meta,
            ]);
            $ds->update([
                'current_version' => $version,
                'last_refreshed_at' => now(),
                'record_count' => $recordCount
            ]);
        });
    }
}
