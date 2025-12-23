<!-- AI Chat Assistant Component -->
<div class="card h-100">
    <div class="card-header" style="background: linear-gradient(135deg, #6610f2 0%, #6f42c1 100%); color: white;">
        <h6 class="mb-0">
            <i class="fas fa-robot me-2"></i>AI Assistant - Symbiosis Chat
            <small class="float-end">Mistral 7B</small>
        </h6>
    </div>
    
    <div class="card-body p-0 d-flex flex-column">
        <!-- Chat Status Bar -->
        <div class="p-2 bg-light border-bottom">
            <div class="d-flex align-items-center justify-content-between">
                <small class="text-muted d-flex align-items-center">
                    <div id="chatStatusIndicator" class="status-dot me-2"></div>
                    <span id="chatStatusText">Connecting to AI...</span>
                </small>
                <button class="btn btn-sm btn-outline-secondary" id="clearChat" type="button">
                    <i class="fas fa-broom"></i> Clear
                </button>
            </div>
        </div>
        
        <!-- Chat Messages Container -->
        <div id="chatMessages" class="chat-container flex-grow-1 p-2" style="height: 300px; overflow-y: auto;">
            <div class="chat-message ai-message">
                <div class="message-content">
                    <strong>🌱 Symbiosis AI:</strong> Hello! I'm here to help with your succession planning. 
                    Select a crop above and I'll provide personalized recommendations.
                </div>
                <small class="message-time">Just now</small>
            </div>
        </div>
        
        <!-- Chat Input -->
        <div class="p-2 border-top">
            <div class="input-group">
                <input type="text" class="form-control" id="chatInput" 
                       placeholder="Ask about timing, spacing, companions..." 
                       maxlength="500">
                <button class="btn btn-primary" type="button" id="sendChat">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.chat-container {
    background: #f8f9fa;
    border-radius: 6px;
    max-height: 300px;
    overflow-y: auto;
}

.chat-message {
    margin-bottom: 12px;
    padding: 8px 12px;
    border-radius: 8px;
    max-width: 85%;
}

.user-message {
    background: #007bff;
    color: white;
    margin-left: auto;
    text-align: right;
}

.ai-message {
    background: white;
    border: 1px solid #e9ecef;
    margin-right: auto;
}

.system-message {
    background: #f0f0f0;
    color: #666;
    font-style: italic;
    text-align: center;
    margin: 0 auto;
}

.message-content {
    font-size: 0.9rem;
    line-height: 1.4;
}

.message-time {
    color: #6c757d;
    font-size: 0.75rem;
    opacity: 0.7;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #dc3545;
    display: inline-block;
}

.status-dot.connected {
    background: #28a745;
}

.status-dot.connecting {
    background: #ffc107;
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.chat-container::-webkit-scrollbar {
    width: 4px;
}

.chat-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 2px;
}

.chat-container::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 2px;
}

.chat-container::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>

<script>
// AI Chat functionality
function initializeAIChat() {
    const chatInput = document.getElementById('chatInput');
    const sendButton = document.getElementById('sendChat');
    const chatMessages = document.getElementById('chatMessages');
    const clearButton = document.getElementById('clearChat');
    const statusIndicator = document.getElementById('chatStatusIndicator');
    const statusText = document.getElementById('chatStatusText');
    
    // Initialize chat status
    statusIndicator.classList.add('connecting');
    statusText.textContent = 'Connecting to AI...';
    
    // Simulate connection after 2 seconds
    setTimeout(() => {
        statusIndicator.classList.remove('connecting');
        statusIndicator.classList.add('connected');
        statusText.textContent = 'AI Ready';
    }, 2000);
    
    // Send message function
    function sendMessage() {
        const message = chatInput.value.trim();
        if (!message) return;
        
        // Add user message
        addChatMessage('user', message);
        chatInput.value = '';
        
        // Show typing indicator
        addTypingIndicator();
        
        // Send to AI (simulate for now)
        setTimeout(() => {
            removeTypingIndicator();
            addChatMessage('ai', '🌱 Thank you for your question! This is a simulated response. In production, this would connect to the Mistral 7B API for intelligent crop planning advice.');
        }, 2000);
    }
    
    // Add message to chat
    function addChatMessage(type, content) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `chat-message ${type}-message`;
        
        const now = new Date();
        const timeString = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        
        messageDiv.innerHTML = `
            <div class="message-content">
                ${type === 'user' ? '<strong>You:</strong> ' : '<strong>🌱 Symbiosis AI:</strong> '}
                ${content}
            </div>
            <small class="message-time">${timeString}</small>
        `;
        
        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Typing indicator
    function addTypingIndicator() {
        const typingDiv = document.createElement('div');
        typingDiv.id = 'typingIndicator';
        typingDiv.className = 'chat-message ai-message';
        typingDiv.innerHTML = `
            <div class="message-content">
                <strong>🌱 Symbiosis AI:</strong> <span class="dots">...</span>
            </div>
        `;
        chatMessages.appendChild(typingDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        
        // Animate dots
        let dotCount = 0;
        const dotsElement = typingDiv.querySelector('.dots');
        const interval = setInterval(() => {
            dotCount = (dotCount + 1) % 4;
            dotsElement.textContent = '.'.repeat(dotCount);
        }, 500);
        typingDiv.dotAnimation = interval;
    }
    
    function removeTypingIndicator() {
        const typingDiv = document.getElementById('typingIndicator');
        if (typingDiv) {
            if (typingDiv.dotAnimation) {
                clearInterval(typingDiv.dotAnimation);
            }
            typingDiv.remove();
        }
    }
    
    // Event listeners
    sendButton.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
    
    clearButton.addEventListener('click', () => {
        chatMessages.innerHTML = `
            <div class="chat-message ai-message">
                <div class="message-content">
                    <strong>🌱 Symbiosis AI:</strong> Chat cleared. How can I help with your succession planning?
                </div>
                <small class="message-time">Just now</small>
            </div>
        `;
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', initializeAIChat);
</script>
