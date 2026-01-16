<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FarmOSApi;
use App\Services\FarmOSQueryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Crop Plan Management Controller
 *
 * Provides a better interface for managing farmOS crop plans
 * with enhanced timeline visualization and management features
 */
class CropPlanController extends Controller
{
    protected $farmOSApi;
    protected $farmOSQuery;

    public function __construct(FarmOSApi $farmOSApi, FarmOSQueryService $farmOSQuery)
    {
        $this->farmOSApi = $farmOSApi;
        $this->farmOSQuery = $farmOSQuery;
    }

    /**
     * Display the crop plan timeline interface
     */
    public function index(): View
    {
        try {
            // Get available crop plans from farmOS
            $plans = $this->getCropPlans();

            return view('admin.farmos.crop-plan-timeline', [
                'plans' => $plans,
                'defaultPlan' => $plans->first() ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load crop plans: ' . $e->getMessage());
            return view('admin.farmos.crop-plan-timeline', [
                'plans' => collect(),
                'defaultPlan' => null,
                'error' => 'Unable to load crop plans from farmOS',
            ]);
        }
    }

    /**
     * Render farmOS-style Gantt chart from JSON data
     */
    public function renderGanttChart(Request $request)
    {
        $planId = $request->get('plan_id', 1);

        // Try to get real timeline data from farmOS
        $timelineData = $this->farmOSApi->getTimelineData($planId);

        // If no real data, use default JSON
        if (!$timelineData || !isset($timelineData['rows'])) {
            Log::info('Using default timeline data for plan', ['plan_id' => $planId]);
            $defaultJson = '{"rows":[{"id":"asset--location--4","label":"B1\/1","link":"\u003Ca href=\u0022\/asset\/4\u0022 hreflang=\u0022en\u0022\u003EB1\/1\u003C\/a\u003E","draggable":false,"resizable":false,"classes":[],"tasks":[],"expanded":true,"children":[{"id":"0fd3e275-43f5-4402-b6de-61a0f80ca97b","label":"2026 Season B1\/1 Carrot F1 Flyaway","link":"\u003Ca href=\u0022\/asset\/40\u0022 hreflang=\u0022en\u0022\u003E2026 Season B1\/1 Carrot F1 Flyaway\u003C\/a\u003E","draggable":false,"resizable":false,"classes":[],"tasks":[{"id":"3486ff42-4960-448a-bc4c-d5b032d2cd40","resource_id":"0fd3e275-43f5-4402-b6de-61a0f80ca97b","label":" ","edit_url":"","start":"2026-06-16T23:00:00+00:00","end":"2026-08-11T23:00:00+00:00","draggable":false,"resizable":false,"meta":{"stage":"location"},"classes":["stage","stage--location","last-location"]}],"expanded":false,"children":[]}]},{"id":"asset--location--5","label":"B1\/2","link":"\u003Ca href=\u0022\/asset\/5\u0022 hreflang=\u0022en\u0022\u003EB1\/2\u003C\/a\u003E","draggable":false,"resizable":false,"classes":[],"tasks":[],"expanded":true,"children":[{"id":"533d8484-af5e-4def-96f1-4b0a27dcd11c","label":"2026 Season B1\/2 Carrot F1 Flyaway","link":"\u003Ca href=\u0022\/asset\/41\u0022 hreflang=\u0022en\u0022\u003E2026 Season B1\/2 Carrot F1 Flyaway\u003C\/a\u003E","draggable":false,"resizable":false,"classes":[],"tasks":[{"id":"664fc45a-7db9-465b-8ac6-b353dc0399ab","resource_id":"533d8484-af5e-4def-96f1-4b0a27dcd11c","label":" ","edit_url":"","start":"2026-07-16T23:00:00+00:00","end":"2026-09-10T23:00:00+00:00","draggable":false,"resizable":false,"meta":{"stage":"location"},"classes":["stage","stage--location","last-location"]}],"expanded":false,"children":[]}]},{"id":"asset--location--6","label":"B1\/3","link":"\u003Ca href=\u0022\/asset\/6\u0022 hreflang=\u0022en\u0022\u003EB1\/3\u003C\/a\u003E","draggable":false,"resizable":false,"classes":[],"tasks":[],"expanded":true,"children":[{"id":"cb20ddbc-325f-4bc3-91e7-50a82ce23478","label":"2026 Season B1\/3 Carrot F1 Flyaway","link":"\u003Ca href=\u0022\/asset\/42\u0022 hreflang=\u0022en\u0022\u003E2026 Season B1\/3 Carrot F1 Flyaway\u003C\/a\u003E","draggable":false,"resizable":false,"classes":[],"tasks":[{"id":"a58827c2-b646-4f43-86b5-643bcbd3dde3","resource_id":"cb20ddbc-325f-4bc3-91e7-50a82ce23478","label":" ","edit_url":"","start":"2026-08-15T23:00:00+00:00","end":"2026-10-10T23:00:00+00:00","draggable":false,"resizable":false,"meta":{"stage":"location"},"classes":["stage","stage--location","last-location"]}],"expanded":false,"children":[]}]}]}';
            $timelineData = json_decode($defaultJson, true);
        }

        if (!$timelineData || !isset($timelineData['rows'])) {
            $html = '<div class="text-center p-4"><i class="fas fa-exclamation-triangle fa-2x text-warning mb-3"></i><br>Invalid timeline data</div>';
            return response($html)->header('Content-Type', 'text/html');
        }

        // Process the data for Chart.js
        $chartData = $this->processFarmOSGanttData($timelineData['rows']);

        $html = '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Crop Plan Timeline</title>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
            <script src="https://cdn.jsdelivr.net/npm/date-fns@2.29.3/index.min.js"></script>
            <style>
                body { margin: 0; padding: 20px; font-family: Arial, sans-serif; }
                .gantt-chart-container { width: 100%; height: 600px; position: relative; }
                canvas { width: 100% !important; height: 100% !important; }
            </style>
        </head>
        <body>
            <div class="gantt-chart-container">
                <canvas id="farmosGanttChart"></canvas>
            </div>

            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const chartData = ' . json_encode($chartData) . ';

                    const ctx = document.getElementById("farmosGanttChart").getContext("2d");

                    new Chart(ctx, {
                        type: "bar",
                        data: {
                            labels: chartData.labels,
                            datasets: chartData.datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: "top"
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const start = new Date(context.parsed.x[0]).toLocaleDateString();
                                            const end = new Date(context.parsed.x[1]).toLocaleDateString();
                                            return context.dataset.label + ": " + start + " - " + end;
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    type: "time",
                                    time: {
                                        unit: "month",
                                        displayFormats: {
                                            month: "MMM YYYY"
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: "Timeline"
                                    }
                                },
                                y: {
                                    title: {
                                        display: true,
                                        text: "Locations"
                                    },
                                    beginAtZero: true
                                }
                            },
                            elements: {
                                bar: {
                                    borderRadius: 2,
                                    borderSkipped: false
                                }
                            }
                        }
                    });
                });
            </script>
        </body>
        </html>';

        return response($html)->header('Content-Type', 'text/html');
    }

    /**
     * Process farmOS timeline JSON data for Chart.js
     */
    private function processFarmOSGanttData($rows)
    {
        $labels = [];
        $datasets = [];

        foreach ($rows as $row) {
            $locationLabel = $row['label'];
            $labels[] = $locationLabel;

            // Process children (plantings)
            if (isset($row['children']) && is_array($row['children'])) {
                foreach ($row['children'] as $child) {
                    $plantingLabel = $child['label'];

                    // Process tasks
                    if (isset($child['tasks']) && is_array($child['tasks'])) {
                        foreach ($child['tasks'] as $task) {
                            if (isset($task['start']) && isset($task['end'])) {
                                $startDate = date('Y-m-d', strtotime($task['start']));
                                $endDate = date('Y-m-d', strtotime($task['end']));

                                // Create dataset for this planting
                                $datasets[] = [
                                    'label' => $plantingLabel,
                                    'data' => [
                                        [
                                            'x' => [$startDate, $endDate],
                                            'y' => $locationLabel
                                        ]
                                    ],
                                    'backgroundColor' => 'rgba(40, 167, 69, 0.7)',
                                    'borderColor' => 'rgba(40, 167, 69, 1)',
                                    'borderWidth' => 1,
                                ];
                            }
                        }
                    }
                }
            }
        }

        return [
            'labels' => array_unique($labels),
            'datasets' => $datasets
        ];
    }

    /**
     * Render timeline iframe content
     */
    public function renderTimeline(Request $request, string $type)
    {
        $planId = $request->get('plan_id', 1);

        // Get real planting data from farmOS
        $plantings = $this->getPlanPlantings($planId);

        if ($plantings->isEmpty()) {
            // No data - show simple message
            return $this->showNoDataMessage($type);
        }

        // Have data - show timeline
        return $this->showTimelineWithData($plantings, $type);
    }

    /**
     * Show message when no planting data exists
     */
    private function showNoDataMessage($type)
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <title>Crop Plan Timeline - ' . ucfirst(str_replace('_', ' ', $type)) . '</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; padding: 50px; }
        .message { max-width: 600px; margin: 0 auto; text-align: center; }
    </style>
</head>
<body>
    <div class="message">
        <h2><i class="fas fa-seedling text-muted"></i></h2>
        <h3>No planting records found</h3>
        <p class="text-muted">This crop plan doesn\'t have any planting data yet.</p>
        <a href="/admin/farmos/plans" class="btn btn-primary">Manage Crop Plans</a>
    </div>
</body>
</html>';

        return response($html)->header('Content-Type', 'text/html');
    }

    /**
     * Show timeline with actual planting data
     */
    private function showTimelineWithData($plantings, $type)
    {
        // Prepare timeline data for Chart.js Gantt chart
        $timelineData = $this->prepareTimelineData($plantings);

        $html = '<!DOCTYPE html>
<html>
<head>
    <title>Crop Plan Timeline - ' . ucfirst(str_replace('_', ' ', $type)) . '</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
    <script src="https://cdn.jsdelivr.net/npm/date-fns@2.29.3/index.min.js"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        .timeline-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            max-width: 100%;
        }
        .timeline-header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        .timeline-chart {
            height: 600px;
            width: 100%;
            position: relative;
        }
        .timeline-legend {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .legend-item {
            display: inline-block;
            margin-right: 20px;
            font-size: 14px;
        }
        .legend-color {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 2px;
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="timeline-container">
        <div class="timeline-header">
            <h3><i class="fas fa-chart-gantt text-primary me-2"></i>Crop Plan Timeline</h3>
            <p class="text-muted mb-0">Planting schedule for ' . $plantings->count() . ' crops</p>
        </div>

        <div class="timeline-chart">
            <canvas id="cropTimelineChart"></canvas>
        </div>

        <div class="timeline-legend">
            <div class="legend-item">
                <span class="legend-color" style="background-color: #28a745;"></span>
                Seeding Period
            </div>
            <div class="legend-item">
                <span class="legend-color" style="background-color: #007bff;"></span>
                Transplant Period
            </div>
            <div class="legend-item">
                <span class="legend-color" style="background-color: #ffc107;"></span>
                Growth Period
            </div>
            <div class="legend-item">
                <span class="legend-color" style="background-color: #dc3545;"></span>
                Harvest Period
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const timelineData = ' . json_encode($timelineData) . ';

            const ctx = document.getElementById("cropTimelineChart").getContext("2d");

            new Chart(ctx, {
                type: "bar",
                data: {
                    labels: timelineData.labels,
                    datasets: timelineData.datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: "top"
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ": " + context.parsed.x;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            type: "time",
                            time: {
                                unit: "month",
                                displayFormats: {
                                    month: "MMM YYYY"
                                }
                            },
                            title: {
                                display: true,
                                text: "Timeline"
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: "Crops"
                            },
                            beginAtZero: true
                        }
                    },
                    elements: {
                        bar: {
                            borderRadius: 4,
                            borderSkipped: false
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>';

        return response($html)->header('Content-Type', 'text/html');
    }

    /**
     * Render just the timeline chart component for iframe embedding
     */
    public function renderTimelineChart(Request $request, $planId = null)
    {
        if (!$planId) {
            $planId = $request->get('plan_id', 1);
        }

        // Get real planting data from farmOS
        $plantings = $this->getPlanPlantings($planId);

        if ($plantings->isEmpty()) {
            // No data - show simple message
            $html = '<div class="text-center p-4"><i class="fas fa-info-circle fa-2x text-muted mb-3"></i><br>No planting records found for this plan.</div>';
            return response($html)->header('Content-Type', 'text/html');
        }

        // Prepare timeline data for Chart.js
        $timelineData = $this->prepareTimelineData($plantings);

        $html = '
        <div class="timeline-chart-container" style="width: 100%; height: 600px; position: relative;">
            <canvas id="cropTimelineChart" style="width: 100%; height: 100%;"></canvas>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
        <script src="https://cdn.jsdelivr.net/npm/date-fns@2.29.3/index.min.js"></script>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const timelineData = ' . json_encode($timelineData) . ';

                const ctx = document.getElementById("cropTimelineChart").getContext("2d");

                new Chart(ctx, {
                    type: "bar",
                    data: {
                        labels: timelineData.labels,
                        datasets: timelineData.datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: "top"
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ": " + context.parsed.x;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                type: "time",
                                time: {
                                    unit: "month",
                                    displayFormats: {
                                        month: "MMM YYYY"
                                    }
                                },
                                title: {
                                    display: true,
                                    text: "Timeline"
                                }
                            },
                            y: {
                                title: {
                                    display: true,
                                    text: "Crops"
                                },
                                beginAtZero: true
                            }
                        },
                        elements: {
                            bar: {
                                borderRadius: 4,
                                borderSkipped: false
                            }
                        }
                    }
                });
            });
        </script>';

        return response($html)->header('Content-Type', 'text/html');
    }





    /**
     * Prepare timeline data for Chart.js visualization
     */
    private function prepareTimelineData($plantings)
    {
        $labels = [];
        $datasets = [
            [
                'label' => 'Seeding',
                'data' => [],
                'backgroundColor' => 'rgba(40, 167, 69, 0.7)',
                'borderColor' => 'rgba(40, 167, 69, 1)',
                'borderWidth' => 1,
            ],
            [
                'label' => 'Transplant',
                'data' => [],
                'backgroundColor' => 'rgba(0, 123, 255, 0.7)',
                'borderColor' => 'rgba(0, 123, 255, 1)',
                'borderWidth' => 1,
            ],
            [
                'label' => 'Growth',
                'data' => [],
                'backgroundColor' => 'rgba(255, 193, 7, 0.7)',
                'borderColor' => 'rgba(255, 193, 7, 1)',
                'borderWidth' => 1,
            ],
            [
                'label' => 'Harvest',
                'data' => [],
                'backgroundColor' => 'rgba(220, 53, 69, 0.7)',
                'borderColor' => 'rgba(220, 53, 69, 1)',
                'borderWidth' => 1,
            ],
        ];

        foreach ($plantings as $planting) {
            $cropName = $planting->asset_name ?? 'Unknown Crop';
            $labels[] = $cropName;

            // Seeding period (1-2 weeks)
            if ($planting->seeding_date) {
                $seedingStart = Carbon::parse($planting->seeding_date);
                $seedingEnd = $seedingStart->copy()->addDays(14); // Assume 2 weeks seeding period

                $datasets[0]['data'][] = [
                    'x' => [$seedingStart->format('Y-m-d'), $seedingEnd->format('Y-m-d')],
                    'y' => $cropName,
                ];
            } else {
                $datasets[0]['data'][] = null;
            }

            // Transplant period (if applicable)
            if ($planting->transplant_date) {
                $transplantStart = Carbon::parse($planting->transplant_date);
                $transplantEnd = $transplantStart->copy()->addDays(7); // 1 week transplant period

                $datasets[1]['data'][] = [
                    'x' => [$transplantStart->format('Y-m-d'), $transplantEnd->format('Y-m-d')],
                    'y' => $cropName,
                ];
            } else {
                $datasets[1]['data'][] = null;
            }

            // Growth period (from transplant/seeding to harvest start)
            $growthStart = null;
            $growthEnd = null;

            if ($planting->transplant_date) {
                $growthStart = Carbon::parse($planting->transplant_date)->addDays(7);
            } elseif ($planting->seeding_date) {
                $growthStart = Carbon::parse($planting->seeding_date)->addDays(14);
            }

            if ($planting->harvest_start_date) {
                $growthEnd = Carbon::parse($planting->harvest_start_date);
            }

            if ($growthStart && $growthEnd) {
                $datasets[2]['data'][] = [
                    'x' => [$growthStart->format('Y-m-d'), $growthEnd->format('Y-m-d')],
                    'y' => $cropName,
                ];
            } else {
                $datasets[2]['data'][] = null;
            }

            // Harvest period
            if ($planting->harvest_start_date) {
                $harvestStart = Carbon::parse($planting->harvest_start_date);
                $harvestEnd = $planting->harvest_end_date
                    ? Carbon::parse($planting->harvest_end_date)
                    : $harvestStart->copy()->addDays(30); // Default 30 days harvest period

                $datasets[3]['data'][] = [
                    'x' => [$harvestStart->format('Y-m-d'), $harvestEnd->format('Y-m-d')],
                    'y' => $cropName,
                ];
            } else {
                $datasets[3]['data'][] = null;
            }
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

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
            // Try to get real plans from farmOS database (plan_field_data is the correct table)
            $plans = DB::connection('farmos')
                ->table('plan_field_data')
                ->where('status', 'active')
                ->select('id', 'name', 'type', 'created', 'changed')
                ->orderBy('changed', 'desc')
                ->get()
                ->map(function ($plan) {
                    return (object) [
                        'id' => $plan->id,
                        'name' => $plan->name ?? 'Plan ' . $plan->id,
                        'description' => 'Crop plan from farmOS (' . $plan->type . ')',
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
