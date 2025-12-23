<?php
namespace App\Services;

use App\Models\AiAccessLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class AiGatewayService
{
    public function __construct(
        protected AiDataAccessService $aiData,
    ) {}

    public function call(string $serviceKey, string $method, array $params = [], array $context = [])
    {
        $catalog = config('ai_data_catalog.services');
        if (!isset($catalog[$serviceKey])) {
            return ['error' => 'unknown_service'];
        }
        $serviceMeta = $catalog[$serviceKey];
        if (!isset($serviceMeta['methods'][$method])) {
            return ['error' => 'unknown_method'];
        }
        $methodMeta = $serviceMeta['methods'][$method];
        $tier = is_array($methodMeta) && isset($methodMeta['tier']) ? $methodMeta['tier'] : 'public';

        // Resolve underlying service instance
        $serviceClass = $serviceMeta['class'];
        $serviceInstance = app($serviceClass);

        if (!method_exists($serviceInstance, $method)) {
            return ['error' => 'not_implemented'];
        }

        $start = microtime(true);
        try {
            $result = $serviceInstance->$method(...$this->buildArgs($params));
        } catch (\Throwable $e) {
            Log::error('AI gateway call failed: '.$e->getMessage());
            $result = ['error' => 'exception', 'message' => $e->getMessage()];
        }
        $duration = (int) ((microtime(true) - $start) * 1000);

        // Redact if restricted
        if ($tier === 'restricted') {
            $result = $this->redact($result);
        }

        $resultCount = $this->countItems($result);

        AiAccessLog::create([
            'user_id' => Auth::id(),
            'service' => $serviceKey,
            'method' => $method,
            'tier' => $tier,
            'duration_ms' => $duration,
            'result_count' => $resultCount,
            'params' => $params,
            'client_ip' => request()->ip() ?? 'cli'
        ]);

        return [
            'tier' => $tier,
            'duration_ms' => $duration,
            'result_count' => $resultCount,
            'data' => $result
        ];
    }

    private function buildArgs(array $params): array
    {
        // Simple positional support: if key 'args' present use that; else treat params as one associative arg if non-numeric keys
        if (isset($params['args']) && is_array($params['args'])) return $params['args'];
        $numeric = array_filter(array_keys($params), 'is_int');
        if (count($numeric) === count($params)) return $params; // purely numeric
        return [$params];
    }

    private function countItems($data): int
    {
        if (is_array($data)) {
            if (isset($data['data']) && is_array($data['data'])) return count($data['data']);
            return count($data);
        }
        return 1;
    }

    private function redact($data)
    {
        if (is_array($data)) {
            return array_map(function($item) {
                if (is_array($item)) {
                    foreach (['email','phone','address'] as $field) {
                        if (isset($item[$field])) $item[$field] = '[redacted]';
                    }
                }
                return $item;
            }, $data);
        }
        return $data;
    }
}
