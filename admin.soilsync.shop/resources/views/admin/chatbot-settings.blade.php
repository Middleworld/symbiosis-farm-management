@extends('layouts.app')

@section('title', 'Chatbot Settings - Middle World Farms Admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">
                        <i class="fas fa-robot text-primary me-2"></i>
                        Chatbot Settings
                    </h1>
                    <p class="text-muted mb-0">Manage AI chatbot configuration and knowledge base</p>
                </div>
                <div class="badge bg-info fs-6">Production: admin.middleworldfarms.org</div>
            </div>

            <!-- System Status Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <i class="fas fa-microchip fa-2x mb-2 
                                {{ $aiStatus['status'] === 'online' ? 'text-success' : 'text-danger' }}"></i>
                            <h6 class="card-title">AI Service</h6>
                            <span class="badge {{ $aiStatus['status'] === 'online' ? 'bg-success' : 'bg-danger' }}">
                                {{ ucfirst($aiStatus['status']) }}
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <i class="fas fa-brain fa-2x mb-2 
                                {{ $knowledgeStatus['status'] === 'available' ? 'text-success' : 'text-warning' }}"></i>
                            <h6 class="card-title">Knowledge Base</h6>
                            <span class="badge {{ $knowledgeStatus['status'] === 'available' ? 'bg-success' : 'bg-warning' }}">
                                {{ ucfirst($knowledgeStatus['status']) }}
                            </span>
                            @if(isset($knowledgeStatus['count']))
                                <small class="d-block text-muted mt-1">{{ $knowledgeStatus['count'] }} entries</small>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <i class="fas fa-server fa-2x mb-2 text-info"></i>
                            <h6 class="card-title">Ollama (RunPod)</h6>
                            <span class="badge bg-info">SSH Tunnel</span>
                            <small class="d-block text-muted mt-1">Port 11434</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <i class="fas fa-cogs fa-2x mb-2 text-primary"></i>
                            <h6 class="card-title">FastAPI Service</h6>
                            <span class="badge bg-primary">Port 8005</span>
                            <small class="d-block text-muted mt-1">RAG + Ollama</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Connection Testing -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-plug me-2"></i>Connection Testing
                            </h5>
                        </div>
                        <div class="card-body">
                            <button id="testConnection" class="btn btn-primary">
                                <i class="fas fa-heartbeat me-2"></i>Test AI Service
                            </button>
                            <button id="refreshStatus" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-sync-alt me-2"></i>Refresh Status
                            </button>
                            
                            <div id="connectionResult" class="mt-3" style="display: none;">
                                <!-- Test results will appear here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Live Chatbot Testing -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header p-0" style="background: linear-gradient(135deg, #27ae60 0%, #213b2e 100%); border-bottom: 4px solid #213b2e;">
                            <div class="d-flex align-items-center p-3">
                                <img src="/Middle%20World%20Logo%20Image%20Green-300x300.png" alt="Middle World Farms Logo" style="height: 40px; width: 40px; object-fit: contain; background: #fff; border-radius: 50%; border: 2px solid #213b2e; margin-right: 15px;">
                                <h5 class="mb-0 text-white"><i class="fas fa-comments me-2"></i>Live Chatbot Testing</h5>
                            </div>
                        </div>
                        <div class="card-body" style="background: #f8f9fa;">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="chat-container" style="height: 400px; border: 2px solid #213b2e; border-radius: 12px; overflow-y: auto; padding: 15px; background: #fff;">
                                        <div id="chatMessages">
                                            <div class="message system-message mb-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-2">
                                                        <img src="/Middle%20World%20Logo%20Image%20Green-300x300.png" alt="Logo" style="height: 32px; width: 32px; object-fit: contain; background: #fff; border-radius: 50%; border: 2px solid #213b2e;">
                                                    </div>
                                                    <div>
                                                        <strong style="color: #213b2e;">Sybiosis (AI Assistant)</strong><br>
                                                        <small class="text-muted">Ready to help with farm management questions!</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="input-group mt-3">
                                        <input type="text" id="chatInput" class="form-control" placeholder="Ask about farm planning, planting schedules, biodynamic practices..." autocomplete="off">
                                        <button class="btn btn-success" type="button" id="sendMessage" style="background: #213b2e; border-color: #213b2e;">
                                            <i class="fas fa-paper-plane me-1"></i>Send
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title">💡 Try asking about:</h6>
                                            <div class="mb-2">
                                                <button class="btn btn-sm btn-outline-primary me-1 mb-1 quick-question" data-question="What should I plant this week?">
                                                    Planting Schedule
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary me-1 mb-1 quick-question" data-question="How do I prepare biodynamic preparations?">
                                                    Biodynamic Prep
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary me-1 mb-1 quick-question" data-question="When is the best time to harvest Brussels sprouts?">
                                                    Harvest Timing
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary me-1 mb-1 quick-question" data-question="How do I manage pests naturally?">
                                                    Pest Control
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary me-1 mb-1 quick-question" data-question="What are moon phases for farming?">
                                                    Moon Phases
                                                </button>
                                            </div>
                                            <small class="text-muted">Click any topic or type your own question</small>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <small class="text-muted">Response Time</small>
                                            <span id="responseTime" class="badge bg-info">-</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">Knowledge Used</small>
                                            <span id="knowledgeUsed" class="badge bg-success">1176 entries</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI Configuration -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-sliders-h me-2"></i>AI Configuration
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">AI Service URL</label>
                                        <input type="text" class="form-control" value="http://localhost:8005" readonly>
                                        <small class="text-muted">FastAPI service with RAG integration</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">AI Model</label>
                                        <input type="text" class="form-control" value="phi3:latest (Phi-3 Full)" readonly>
                                        <small class="text-muted">Fast & efficient - Port 8005 | Phi-3 3B Model</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info" role="alert">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Production Environment:</strong> All AI services are configured for admin.middleworldfarms.org
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Test Connection Button
    document.getElementById('testConnection').addEventListener('click', function() {
        const button = this;
        const originalText = button.innerHTML;
        const resultDiv = document.getElementById('connectionResult');
        
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Testing...';
        button.disabled = true;
        
        // Test the admin test route
        fetch('/admin/chatbot-api', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = 
                '<div class="alert alert-success">' +
                    '<i class="fas fa-check-circle me-2"></i>' +
                    '<strong>Connection Successful!</strong><br>' +
                    '<small>Response: ' + data.message + ' (' + data.timestamp + ')</small>' +
                '</div>';
        })
        .catch(error => {
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = 
                '<div class="alert alert-danger">' +
                    '<i class="fas fa-exclamation-circle me-2"></i>' +
                    '<strong>Connection Failed!</strong><br>' +
                    '<small>Error: ' + error.message + '</small>' +
                '</div>';
        })
        .finally(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        });
    });
    
    // Refresh Status Button
    document.getElementById('refreshStatus').addEventListener('click', function() {
        location.reload();
    });

    // Live Chatbot Functionality
    let conversationId = 'admin-test-' + Date.now();
    
    // Send message function
    function sendMessage(message) {
        if (!message.trim()) return;
        
        console.log('Sending message:', message); // Debug log
        
        const chatMessages = document.getElementById('chatMessages');
        const sendButton = document.getElementById('sendMessage');
        const chatInput = document.getElementById('chatInput');
        
        if (!chatMessages || !sendButton || !chatInput) {
            console.error('Chat elements not found');
            return;
        }
        
        // Add user message
        addMessage(message, 'user');
        
        // Clear input and disable button
        chatInput.value = '';
        sendButton.disabled = true;
        sendButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Thinking...';
        
        const startTime = Date.now();
        
        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        const token = csrfToken ? csrfToken.getAttribute('content') : '';
        
        console.log('CSRF Token:', token ? 'Found' : 'Missing'); // Debug log
        
        // Send to AI service
        fetch('/admin/chatbot-api', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                message: message,
                conversation_id: conversationId,
                test_mode: true
            })
        })
        .then(response => {
            console.log('Response status:', response.status); // Debug log
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data); // Debug log
            const responseTime = Date.now() - startTime;
            const responseTimeEl = document.getElementById('responseTime');
            if (responseTimeEl) {
                responseTimeEl.textContent = responseTime + 'ms';
            }
            
            if (data.success) {
                addMessage(data.response || data.message || 'Response received successfully!', 'ai');
            } else {
                addMessage('Sorry, I encountered an error: ' + (data.error || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Chat error:', error); // Debug log
            addMessage('Connection error: ' + error.message, 'error');
        })
        .finally(() => {
            sendButton.disabled = false;
            sendButton.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Send';
            chatInput.focus();
        });
    }
    
    // Add message to chat
    function addMessage(message, type) {
        const chatMessages = document.getElementById('chatMessages');
        const messageDiv = document.createElement('div');
        messageDiv.className = 'message mb-3';
        
        let icon, bgClass, textClass, logo = '';
        if (type === 'user') {
            icon = 'fas fa-user';
            bgClass = 'bg-success text-white';
            textClass = 'text-end';
        } else if (type === 'ai') {
            icon = 'fas fa-robot';
            bgClass = 'bg-light border border-success';
            textClass = 'text-start';
            logo = `<img src="/Middle%20World%20Logo%20Image%20Green-300x300.png" alt="Logo" style="height: 24px; width: 24px; object-fit: contain; background: #fff; border-radius: 50%; border: 2px solid #213b2e;">`;
        } else {
            icon = 'fas fa-exclamation-triangle';
            bgClass = 'bg-warning';
            textClass = 'text-start';
        }
        messageDiv.innerHTML = 
            '<div class="d-flex ' + (textClass === 'text-end' ? 'justify-content-end' : '') + '">' +
                (textClass !== 'text-end' ? '<div class="me-2">' + (logo || '<i class="' + icon + ' text-success"></i>') + '</div>' : '') +
                '<div class="message-content p-2 rounded ' + bgClass + '" style="max-width: 80%; border-radius: 16px; border: 1.5px solid #213b2e;">' +
                    message +
                    '<br><small class="opacity-75">' + new Date().toLocaleTimeString() + '</small>' +
                '</div>' +
                (textClass === 'text-end' ? '<div class="ms-2"><i class="' + icon + ' text-success"></i></div>' : '') +
            '</div>';
        
        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Event listeners
    document.getElementById('sendMessage').addEventListener('click', function() {
        const message = document.getElementById('chatInput').value;
        sendMessage(message);
    });
    
    document.getElementById('chatInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const message = this.value;
            sendMessage(message);
        }
    });
    
    // Quick question buttons
    document.querySelectorAll('.quick-question').forEach(button => {
        button.addEventListener('click', function() {
            const question = this.getAttribute('data-question');
            document.getElementById('chatInput').value = question;
            sendMessage(question);
        });
    });
});
</script>
@endsection
