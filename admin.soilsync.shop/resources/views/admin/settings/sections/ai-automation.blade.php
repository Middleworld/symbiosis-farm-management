@if(isset($showAiSettings) && $showAiSettings)
    {{-- Production Admin: Full AI configuration --}}
    <div class="alert alert-warning">
        <i class="fas fa-tools"></i> <strong>Production Admin Settings</strong>
        <p class="mb-0 mt-2">Configure shared AI service for all customer instances.</p>
    </div>
    
    <div class="mb-3">
        <label class="form-label">AI Service URL</label>
        <input type="text" class="form-control" value="{{ env('AI_SERVICE_URL', 'http://localhost:8005') }}" readonly>
        <small class="text-muted">Configured in .env file</small>
    </div>
    
    <div class="mb-3">
        <label class="form-label">Service Timeout</label>
        <input type="text" class="form-control" value="{{ env('AI_SERVICE_TIMEOUT', 90) }}s" readonly>
        <small class="text-muted">CPU-only processing timeout</small>
    </div>
@else
    {{-- Customer View: Enable/Disable toggle and simple test --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">
                    <i class="fas fa-robot text-primary"></i> AI Chatbot Assistant
                </h5>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="ai-enabled-toggle" 
                           {{ ($settings['ai_enabled'] ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="ai-enabled-toggle">
                        <strong>Enable AI Features</strong>
                    </label>
                </div>
            </div>
            
            <div id="ai-content" style="{{ ($settings['ai_enabled'] ?? true) ? '' : 'display:none' }}">
                <p class="text-muted mb-4">
                    Test your AI farm assistant. The chatbot helps with crop planning, 
                    succession schedules, and farm management questions.
                </p>
                
                <div class="row g-3 mb-4">
                    <!-- AI Status -->
                    <div class="col-md-6">
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Service Status</h6>
                                <div id="ai-status-display">
                                    <span class="spinner-border spinner-border-sm me-2"></span>
                                    Checking...
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Response Time -->
                    <div class="col-md-6">
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <h6 class="text-muted mb-2">Response Time</h6>
                                <div id="ai-response-time">
                                    <span class="text-muted">--</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Test AI Button -->
                <button type="button" class="btn btn-primary" id="test-ai-btn">
                    <i class="fas fa-vial"></i> Test AI Chatbot
                </button>
                
                <!-- Test Result -->
                <div id="ai-test-result" class="mt-3" style="display: none;">
                    <div class="alert mb-0" id="test-result-content"></div>
                </div>
            </div>
            
            <div id="ai-disabled-message" style="{{ ($settings['ai_enabled'] ?? true) ? 'display:none' : '' }}">
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle"></i> 
                    AI features are currently disabled. Enable the toggle above to use the AI chatbot assistant.
                </div>
            </div>
        </div>
    </div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    @if(!isset($showAiSettings) || !$showAiSettings)
    const aiToggle = document.getElementById('ai-enabled-toggle');
    const aiContent = document.getElementById('ai-content');
    const aiDisabledMessage = document.getElementById('ai-disabled-message');
    
    // Handle toggle change
    aiToggle?.addEventListener('change', function() {
        const enabled = this.checked;
        
        // Show/hide content
        aiContent.style.display = enabled ? '' : 'none';
        aiDisabledMessage.style.display = enabled ? 'none' : '';
        
        // Save setting
        fetch('/admin/settings/ai-toggle', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ enabled: enabled })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show toast or notification
                console.log('AI settings updated');
            }
        });
    });
    
    // Only check status if AI is enabled
    if (aiToggle?.checked) {
        checkAIStatus();
    }
    
    // Test AI button
    document.getElementById('test-ai-btn')?.addEventListener('click', testAIChatbot);
    
    function checkAIStatus() {
        fetch('/admin/ai/status')
            .then(response => response.json())
            .then(data => {
                const statusDiv = document.getElementById('ai-status-display');
                if (data.available) {
                    statusDiv.innerHTML = '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Online</span>';
                } else {
                    statusDiv.innerHTML = '<span class="badge bg-danger"><i class="fas fa-times-circle"></i> Offline</span>';
                }
                
                if (data.response_time) {
                    document.getElementById('ai-response-time').innerHTML = 
                        `<strong>${data.response_time}ms</strong>`;
                }
            })
            .catch(error => {
                document.getElementById('ai-status-display').innerHTML = 
                    '<span class="badge bg-warning"><i class="fas fa-exclamation-triangle"></i> Unknown</span>';
            });
    }
    
    function testAIChatbot() {
        const btn = document.getElementById('test-ai-btn');
        const resultDiv = document.getElementById('ai-test-result');
        const resultContent = document.getElementById('test-result-content');
        
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Testing...';
        resultDiv.style.display = 'none';
        
        fetch('/admin/ai/test', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            resultDiv.style.display = 'block';
            
            if (data.success) {
                resultContent.className = 'alert alert-success mb-0';
                resultContent.innerHTML = `
                    <strong><i class="fas fa-check-circle"></i> AI Chatbot is Working!</strong>
                    <p class="mb-2 mt-2">${data.message}</p>
                    <small class="text-muted">Response time: ${data.response_time}ms</small>
                `;
            } else {
                resultContent.className = 'alert alert-danger mb-0';
                resultContent.innerHTML = `
                    <strong><i class="fas fa-exclamation-triangle"></i> AI Chatbot Not Available</strong>
                    <p class="mb-0 mt-2">${data.message}</p>
                `;
            }
        })
        .catch(error => {
            resultDiv.style.display = 'block';
            resultContent.className = 'alert alert-danger mb-0';
            resultContent.innerHTML = `
                <strong><i class="fas fa-times-circle"></i> Connection Error</strong>
                <p class="mb-0 mt-2">Unable to connect to AI service.</p>
            `;
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-vial"></i> Test AI Chatbot';
        });
    }
    @endif
});
</script>
