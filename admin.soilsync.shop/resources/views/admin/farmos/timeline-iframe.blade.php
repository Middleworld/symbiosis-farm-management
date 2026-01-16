<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crop Plan Timeline - {{ ucfirst($type) }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        .timeline-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .timeline-header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        .timeline-header h3 {
            margin: 0;
            color: #495057;
            font-weight: 600;
        }
        .timeline-chart {
            min-height: 1200px;
            height: 1400px;
            width: 100%;
        }
        /* Make timeline elements much larger and more visible */
        .farm-timeline, .sg-timeline, .sg-gantt {
            min-height: 1400px !important;
            height: 1400px !important;
        }
        /* Increase row heights significantly for better visibility */
        .sg-row {
            min-height: 120px !important;
            padding: 30px 0 !important;
            margin-bottom: 20px !important;
        }
        /* Make timeline tree wider for better label visibility */
        .sg-table, .sg-timeline-body {
            width: 400px !important;
            min-width: 400px !important;
            max-width: 400px !important;
        }
        /* Adjust gantt chart to fill remaining space */
        .sg-gantt .content {
            width: calc(100% - 400px) !important;
            margin-left: 400px !important;
        }
        /* Larger fonts for better readability */
        .sg-row .sg-row-label {
            font-size: 16px !important;
            font-weight: 600 !important;
        }
        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .loading {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="timeline-container">
        <div class="timeline-header">
            <h3>Timeline by {{ $type === 'plant_type' ? 'Plant Type' : 'Location' }}</h3>
        </div>

        @if(isset($error))
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i> {{ $error }}
            </div>
        @else
            <div id="timeline-chart-{{ $type }}" class="timeline-chart">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading timeline...
                </div>
            </div>
        @endif
    </div>

    <!-- Chart.js and date-fns for timeline rendering -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/date-fns@2.29.3/index.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if($timelineData && isset($timelineData['rows']) && count($timelineData['rows']) > 0)
                const timelineData = @json($timelineData);

                // Simple timeline rendering
                renderTimeline(timelineData.rows, '{{ $type }}');
            @else
                // Show sample timeline data to demonstrate the interface
                const sampleData = @json($this->getSampleTimelineData($type));
                renderTimeline(sampleData.rows, '{{ $type }}');

                // Add a note that this is sample data
                setTimeout(() => {
                    const container = document.querySelector('.timeline-container');
                    if (container) {
                        const note = document.createElement('div');
                        note.className = 'alert alert-info mt-3';
                        note.innerHTML = '<i class="fas fa-info-circle"></i> <strong>Sample Data:</strong> This timeline shows example data to demonstrate the interface. Connect to farmOS to see your actual crop planning data.';
                        container.appendChild(note);
                    }
                }, 1000);
            @endif
        });

        function renderTimeline(rows, type) {
            const container = document.getElementById('timeline-chart-' + type);

            if (!rows || rows.length === 0) {
                container.innerHTML = '<div class="text-center text-muted py-5"><i class="fas fa-info-circle fa-2x mb-3"></i><br>No timeline data available for this plan.</div>';
                return;
            }

            // Create a simple timeline visualization
            let html = '<div class="timeline-visualization">';

            rows.forEach(row => {
                html += `
                    <div class="timeline-row mb-3">
                        <div class="row-header fw-bold mb-2">
                            <i class="fas fa-seedling text-success me-2"></i>
                            ${row.label || 'Unknown'}
                        </div>
                        <div class="row-content">
                `;

                if (row.children && row.children.length > 0) {
                    row.children.forEach(child => {
                        const taskCount = child.tasks ? child.tasks.length : 0;
                        html += `
                            <div class="child-item mb-2 p-2 border rounded">
                                <div class="fw-semibold">
                                    <i class="fas fa-leaf text-primary me-2"></i>
                                    ${child.label || 'Unknown Plant'}
                                </div>
                                <div class="text-muted small">Tasks: ${taskCount}</div>
                                ${taskCount > 0 ? `
                                    <div class="mt-2">
                                        <small class="text-muted">Recent tasks:</small>
                                        <ul class="list-unstyled ms-3 mt-1">
                                            ${child.tasks.slice(0, 3).map(task =>
                                                `<li class="small">
                                                    <i class="fas fa-circle text-warning me-1" style="font-size: 6px;"></i>
                                                    ${task.meta && task.meta.label ? task.meta.label : 'Task'}
                                                </li>`
                                            ).join('')}
                                        </ul>
                                    </div>
                                ` : ''}
                            </div>
                        `;
                    });
                } else {
                    html += '<div class="text-muted fst-italic">No planting records found</div>';
                }

                html += '</div></div>';
            });

            html += '</div>';
            container.innerHTML = html;
        }
    </script>
</body>
</html>