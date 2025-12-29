/**
 * Middle World Farms - Public Chatbot Widget
 * WordPress Integration JavaScript
 */

class MiddleWorldChatbot {
    constructor(options = {}) {
        this.apiEndpoint = options.apiEndpoint || 'http://localhost:8090/wordpress/chat';
        this.container = options.container || document.body;
        this.theme = options.theme || 'light';
        this.defaultModel = options.model || 'auto';
        this.sessionId = this.generateSessionId();
        
        this.init();
    }
    
    generateSessionId() {
        return 'mw-' + Math.random().toString(36).substr(2, 9);
    }
    
    init() {
        this.createChatWidget();
        this.attachEventListeners();
    }
    
    createChatWidget() {
        const chatWidget = document.createElement('div');
        chatWidget.className = `mw-chatbot ${this.theme}`;
        chatWidget.innerHTML = `
            <div class="mw-chatbot-container" id="mw-chatbot-container">
                <!-- Chat Button -->
                <div class="mw-chat-button" id="mw-chat-button">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12c0 1.54.36 3.04 1.04 4.36L2 22l5.64-1.04C9.96 21.64 11.46 22 13 22h7c1.1 0 2-.9 2-2V12c0-5.52-4.48-10-10-10z"/>
                    </svg>
                    <span>Ask about farming</span>
                </div>
                
                <!-- Chat Window -->
                <div class="mw-chat-window" id="mw-chat-window" style="display: none;">
                    <div class="mw-chat-header">
                        <h4>🌱 Middle World Farms Assistant</h4>
                        <div class="mw-model-selector">
                            <select id="mw-model-select">
                                <option value="auto">Auto-Select</option>
                                <option value="tinyllama">Quick Response</option>
                                <option value="gemma2">Detailed Advice</option>
                            </select>
                        </div>
                        <button class="mw-close-btn" id="mw-close-btn">&times;</button>
                    </div>
                    
                    <div class="mw-chat-messages" id="mw-chat-messages">
                        <div class="mw-message mw-bot-message">
                            <div class="mw-message-content">
                                👋 Hi! I'm your agricultural assistant. Ask me about biodynamic farming, growing tips, or any gardening questions!
                            </div>
                        </div>
                    </div>
                    
                    <div class="mw-chat-input-container">
                        <input type="text" id="mw-chat-input" placeholder="Ask about farming, gardening, or growing..." maxlength="1000">
                        <button id="mw-send-btn">Send</button>
                    </div>
                    
                    <div class="mw-chat-footer">
                        <small>Powered by Middle World Farms AI</small>
                    </div>
                </div>
            </div>
        `;
        
        this.container.appendChild(chatWidget);
        this.addStyles();
    }
    
    addStyles() {
        const styles = `
            .mw-chatbot {
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 9999;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            
            .mw-chat-button {
                background: #2d5a3d;
                color: white;
                padding: 12px 20px;
                border-radius: 25px;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                transition: all 0.3s ease;
            }
            
            .mw-chat-button:hover {
                background: #1e3a2a;
                transform: translateY(-2px);
                box-shadow: 0 6px 16px rgba(0,0,0,0.2);
            }
            
            .mw-chat-window {
                position: absolute;
                bottom: 60px;
                right: 0;
                width: 350px;
                height: 500px;
                background: white;
                border-radius: 12px;
                box-shadow: 0 8px 32px rgba(0,0,0,0.2);
                display: flex;
                flex-direction: column;
                overflow: hidden;
            }
            
            .mw-chat-header {
                background: #2d5a3d;
                color: white;
                padding: 15px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .mw-chat-header h4 {
                margin: 0;
                font-size: 16px;
            }
            
            .mw-model-selector select {
                background: rgba(255,255,255,0.2);
                color: white;
                border: 1px solid rgba(255,255,255,0.3);
                border-radius: 4px;
                padding: 4px 8px;
                font-size: 12px;
            }
            
            .mw-close-btn {
                background: none;
                border: none;
                color: white;
                font-size: 20px;
                cursor: pointer;
                padding: 0;
                width: 24px;
                height: 24px;
            }
            
            .mw-chat-messages {
                flex: 1;
                overflow-y: auto;
                padding: 15px;
                background: #f8f9fa;
            }
            
            .mw-message {
                margin-bottom: 15px;
            }
            
            .mw-bot-message .mw-message-content {
                background: white;
                padding: 12px 15px;
                border-radius: 18px 18px 18px 6px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                max-width: 85%;
            }
            
            .mw-user-message {
                text-align: right;
            }
            
            .mw-user-message .mw-message-content {
                background: #2d5a3d;
                color: white;
                padding: 12px 15px;
                border-radius: 18px 18px 6px 18px;
                display: inline-block;
                max-width: 85%;
            }
            
            .mw-chat-input-container {
                padding: 15px;
                background: white;
                display: flex;
                gap: 10px;
                border-top: 1px solid #eee;
            }
            
            .mw-chat-input-container input {
                flex: 1;
                padding: 10px 15px;
                border: 1px solid #ddd;
                border-radius: 20px;
                outline: none;
            }
            
            .mw-chat-input-container input:focus {
                border-color: #2d5a3d;
            }
            
            .mw-chat-input-container button {
                background: #2d5a3d;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 20px;
                cursor: pointer;
                font-weight: 500;
            }
            
            .mw-chat-input-container button:hover {
                background: #1e3a2a;
            }
            
            .mw-chat-input-container button:disabled {
                background: #ccc;
                cursor: not-allowed;
            }
            
            .mw-chat-footer {
                padding: 8px 15px;
                background: #f8f9fa;
                text-align: center;
                border-top: 1px solid #eee;
            }
            
            .mw-chat-footer small {
                color: #666;
                font-size: 11px;
            }
            
            .mw-typing-indicator {
                display: flex;
                align-items: center;
                gap: 5px;
                padding: 10px 15px;
                background: white;
                border-radius: 18px;
                margin-bottom: 10px;
                max-width: 85%;
            }
            
            .mw-typing-dots {
                display: flex;
                gap: 3px;
            }
            
            .mw-typing-dots span {
                width: 6px;
                height: 6px;
                background: #ccc;
                border-radius: 50%;
                animation: mw-typing 1.4s infinite ease-in-out;
            }
            
            .mw-typing-dots span:nth-child(1) { animation-delay: -0.32s; }
            .mw-typing-dots span:nth-child(2) { animation-delay: -0.16s; }
            
            @keyframes mw-typing {
                0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; }
                40% { transform: scale(1); opacity: 1; }
            }
            
            @media (max-width: 480px) {
                .mw-chatbot {
                    bottom: 10px;
                    right: 10px;
                    left: 10px;
                }
                
                .mw-chat-window {
                    width: 100%;
                    height: 400px;
                    right: 0;
                }
            }
        `;
        
        const styleSheet = document.createElement('style');
        styleSheet.textContent = styles;
        document.head.appendChild(styleSheet);
    }
    
    attachEventListeners() {
        const chatButton = document.getElementById('mw-chat-button');
        const chatWindow = document.getElementById('mw-chat-window');
        const closeBtn = document.getElementById('mw-close-btn');
        const sendBtn = document.getElementById('mw-send-btn');
        const chatInput = document.getElementById('mw-chat-input');
        
        chatButton.addEventListener('click', () => {
            chatWindow.style.display = chatWindow.style.display === 'none' ? 'flex' : 'none';
        });
        
        closeBtn.addEventListener('click', () => {
            chatWindow.style.display = 'none';
        });
        
        sendBtn.addEventListener('click', () => this.sendMessage());
        
        chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.sendMessage();
            }
        });
    }
    
    async sendMessage() {
        const input = document.getElementById('mw-chat-input');
        const messagesContainer = document.getElementById('mw-chat-messages');
        const sendBtn = document.getElementById('mw-send-btn');
        const modelSelect = document.getElementById('mw-model-select');
        
        const message = input.value.trim();
        if (!message) return;
        
        // Add user message
        this.addMessage(message, 'user');
        input.value = '';
        sendBtn.disabled = true;
        
        // Add typing indicator
        this.showTypingIndicator();
        
        try {
            const response = await fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Session-ID': this.sessionId
                },
                body: JSON.stringify({
                    message: message,
                    model: modelSelect.value,
                    session_id: this.sessionId
                })
            });
            
            const data = await response.json();
            
            this.hideTypingIndicator();
            
            if (data.success) {
                this.addMessage(data.data.message, 'bot');
            } else {
                this.addMessage('Sorry, I encountered an error. Please try again.', 'bot');
            }
            
        } catch (error) {
            console.error('Chatbot error:', error);
            this.hideTypingIndicator();
            this.addMessage('Sorry, I\'m having trouble connecting. Please try again later.', 'bot');
        }
        
        sendBtn.disabled = false;
    }
    
    addMessage(content, type) {
        const messagesContainer = document.getElementById('mw-chat-messages');
        const messageDiv = document.createElement('div');
        messageDiv.className = `mw-message mw-${type}-message`;
        messageDiv.innerHTML = `<div class="mw-message-content">${content}</div>`;
        messagesContainer.appendChild(messageDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    showTypingIndicator() {
        const messagesContainer = document.getElementById('mw-chat-messages');
        const indicator = document.createElement('div');
        indicator.id = 'mw-typing-indicator';
        indicator.className = 'mw-typing-indicator';
        indicator.innerHTML = `
            <span>AI is thinking</span>
            <div class="mw-typing-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        `;
        messagesContainer.appendChild(indicator);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    hideTypingIndicator() {
        const indicator = document.getElementById('mw-typing-indicator');
        if (indicator) {
            indicator.remove();
        }
    }
}

// WordPress integration
(function() {
    'use strict';
    
    // Auto-initialize if WordPress
    if (typeof window.wp !== 'undefined' || document.body.classList.contains('wordpress')) {
        document.addEventListener('DOMContentLoaded', function() {
            // Get configuration from WordPress
            const config = window.middleworldChatbotConfig || {};
            
            // Initialize chatbot
            window.middleworldChatbot = new MiddleWorldChatbot({
                apiEndpoint: config.apiEndpoint || 'http://localhost:8090/wordpress/chat',
                theme: config.theme || 'light',
                model: config.model || 'auto'
            });
        });
    }
})();

// Expose for manual initialization
window.MiddleWorldChatbot = MiddleWorldChatbot;
