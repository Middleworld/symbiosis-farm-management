<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RosTelemetryController extends Controller
{
    public function store(Request $request)
    {
        $payload = $request->validate([
            'timestamp_ms' => ['nullable', 'integer'],
            'source' => ['nullable', 'string'],
            'robots' => ['required', 'array'],
            'robots.*.name' => ['required', 'string'],
            'robots.*.position' => ['required', 'array'],
            'robots.*.position.x' => ['required', 'numeric'],
            'robots.*.position.y' => ['required', 'numeric'],
            'robots.*.position.z' => ['required', 'numeric'],
            'robots.*.orientation' => ['required', 'array'],
            'robots.*.orientation.x' => ['required', 'numeric'],
            'robots.*.orientation.y' => ['required', 'numeric'],
            'robots.*.orientation.z' => ['required', 'numeric'],
            'robots.*.orientation.w' => ['required', 'numeric'],
            'robots.*.linear' => ['required', 'array'],
            'robots.*.linear.x' => ['required', 'numeric'],
            'robots.*.linear.y' => ['required', 'numeric'],
            'robots.*.linear.z' => ['required', 'numeric'],
            'robots.*.angular' => ['required', 'array'],
            'robots.*.angular.x' => ['required', 'numeric'],
            'robots.*.angular.y' => ['required', 'numeric'],
            'robots.*.angular.z' => ['required', 'numeric'],
        ]);

        Cache::put('ros.telemetry.latest', $payload, now()->addMinutes(5));
        Log::info('ROS telemetry received', ['robots' => count($payload['robots'])]);

        return response()->json(['ok' => true]);
    }

    public function latest()
    {
        $telemetry = Cache::get('ros.telemetry.latest');

        if (!$telemetry) {
            return response()->json(['error' => 'No telemetry data available'], 404);
        }

        return response()->json($telemetry);
    }
}
