/**
 * Middle World Farms AI Helper Widget
 * Contextual AI assistant for complex admin setup pages
 * Supports both floating and sidebar modes
 */

class AIHelperWidget {
    constructor(options = {}) {
        this.apiUrl = options.apiUrl || '/admin/ai-helper/contextual-help';
        this.pageContext = options.pageContext || this.detectPageContext();
        this.currentSection = options.currentSection || '';
        this.position = options.position || 'bottom-right';
        this.container = options.container || null; // Custom container selector
        this.isOpen = false;
        this.isSidebarMode = false;

        this.init();
    }

    detectPageContext() {
        // Detect current page from URL or page content
        const url = window.location.href;
        const path = window.location.pathname;

        // Check for specific admin pages
        if (path.includes('/admin/farmos/succession-planning')) {
            return 'succession-planning';
        }
        if (path.includes('/admin/users')) {
            return 'user-management';
        }
        if (path.includes('/admin/farmos')) {
            return 'farmos-integration';
        }
        if (path.includes('/admin/vegbox-subscriptions')) {
            return 'subscription-management';
        }
        if (path.includes('/admin/deliveries')) {
            return 'delivery-management';
        }
        if (path.includes('/admin/shipping-classes')) {
            return 'shipping-classes';
        }

        // Check for documentation pages
        if (url.includes('docs/') || document.querySelector('pre') || document.querySelector('code')) {
            return 'documentation';
        }

        return 'admin-general';
    }

    init() {
        // Check if we're in sidebar mode
        const sidebarWidget = document.getElementById('ai-helper-sidebar-widget');
        if (sidebarWidget) {
            this.isSidebarMode = true;
            this.container = sidebarWidget;
            this.cacheSidebarElements();
            this.attachSidebarEventListeners();
            this.showWelcomeMessage();
            return;
        }

        // Otherwise, use floating mode
        this.createWidget();
        this.attachEventListeners();
        this.showWelcomeMessage();
    }

    createWidget() {
        // Skip floating widget creation - using sidebar mode only
        return;
    }

    getPanelHTML() {
        // Not used in sidebar mode - HTML is in the blade template
        return '';
    }

    cacheElements() {
        this.button = this.container.querySelector('.ai-helper-button');
        this.panel = this.container.querySelector('.ai-helper-panel');
        this.input = this.container.querySelector('input');
        this.sendButton = this.container.querySelector('.ai-send-button');
        this.messages = this.container.querySelector('.ai-helper-messages');
        this.loading = this.container.querySelector('.ai-helper-loading');
    }

    cacheSidebarElements() {
        this.input = this.container.querySelector('input');
        this.sendButton = this.container.querySelector('.ai-send-button');
        this.messages = this.container.querySelector('.ai-helper-sidebar-messages');
        this.loading = this.container.querySelector('.ai-helper-sidebar-loading');
        this.contextElement = this.container.querySelector('.ai-helper-context');
    }

    attachSidebarEventListeners() {
        // Update context when page changes
        this.updateSidebarContext();

        // Input handling
        this.input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.sendMessage();
            }
        });

        this.sendButton.addEventListener('click', () => {
            this.sendMessage();
        });

        // Auto-update context on navigation
        const observer = new MutationObserver(() => {
            this.updateSidebarContext();
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    updateSidebarContext() {
        const newContext = this.detectPageContext();
        if (newContext !== this.pageContext) {
            this.pageContext = newContext;
            if (this.contextElement) {
                this.contextElement.textContent = this.formatContext(this.pageContext);
            }
        }
    }

    addStyles() {
        // Styles are now handled in the main CSS file for sidebar mode
    }

    attachEventListeners() {
        // Not used in sidebar mode - events are handled by attachSidebarEventListeners
    }

    togglePanel() {
        this.isOpen = !this.isOpen;
        this.panel.style.display = this.isOpen ? 'flex' : 'none';
    }

    closePanel() {
        this.isOpen = false;
        this.panel.style.display = 'none';
    }

    showWelcomeMessage() {
        // Welcome message is already in the HTML
    }

    async sendMessage() {
        const message = this.input.value.trim();
        if (!message) return;

        // Add user message
        this.addMessage('user', message);
        this.input.value = '';

        // Show loading
        this.loading.style.display = 'flex';

        try {
            const response = await this.getHelp(message);
            this.addMessage('ai', response.response);

            // Show sources if available
            if (response.sources && response.sources.length > 0) {
                this.addMessage('ai', `📚 Sources: ${response.sources.join(', ')}`, 'sources');
            }
        } catch (error) {
            this.addMessage('ai', 'Sorry, I encountered an error. Please try again.', 'error');
        }

        // Hide loading
        this.loading.style.display = 'none';
    }

    addMessage(type, content, subtype = '') {
        const messageDiv = document.createElement('div');
        messageDiv.className = `ai-message ${type} ${subtype}`;

        if (type === 'user') {
            messageDiv.innerHTML = `
                <div class="ai-content">
                    <p>${this.escapeHtml(content)}</p>
                </div>
                <div class="ai-avatar">👤</div>
            `;
        } else {
            messageDiv.innerHTML = `
                <div class="ai-avatar">🌱</div>
                <div class="ai-content">
                    <p>${this.formatResponse(content)}</p>
                </div>
            `;
        }

        this.messages.appendChild(messageDiv);
        this.messages.scrollTop = this.messages.scrollHeight;
    }

    async getHelp(question) {
        // Handle shipping-classes context locally to avoid API issues
        if (this.pageContext === 'shipping-classes') {
            return this.getShippingClassesHelp(question);
        }

        const response = await fetch(`${this.apiUrl}/contextual-help`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                page_context: this.pageContext,
                question: question,
                current_section: this.currentSection
            })
        });

        if (!response.ok) {
            throw new Error('Failed to get help');
        }

        return await response.json();
    }

    getShippingClassesHelp(question) {
        if (strContains(question.toLowerCase(), 'explain') || strContains(question.toLowerCase(), 'what') || strContains(question.toLowerCase(), 'how')) {
            return {
                response: "📦 **Shipping Classes Create Page**\n\nThis page allows you to create new shipping classes for your WooCommerce store. Shipping classes help you organize products by delivery requirements and costs.\n\n**Key Fields:**\n- **Name**: Display name for the shipping class (e.g., \"Fragile Items\", \"Heavy Equipment\")\n- **Description**: Optional details about this shipping class\n- **Cost**: Base shipping cost for this class\n- **Is Farm Collection**: Check if this class allows farm pickup instead of delivery\n\n**Usage:**\n- Assign shipping classes to products in WooCommerce\n- Different classes can have different shipping rates\n- Farm collection bypasses delivery costs\n- Helps organize your delivery schedule and logistics\n\n**Tips:**\n- Use descriptive names that help customers understand shipping options\n- Consider weight, size, and fragility when creating classes\n- Farm collection is great for local customers who prefer pickup",
                sources: ['admin_help'],
                context_found: true
            };
        } else {
            return {
                response: "For shipping classes, you asked: '{$question}'. This page manages WooCommerce shipping classifications for organizing delivery costs and methods.",
                sources: ['admin_help'],
                context_found: true
            };
        }
    }

    async sendMessage() {
        const message = this.input.value.trim();
        if (!message) return;

        // Add user message
        this.addMessage(message, 'user');

        // Clear input
        this.input.value = '';

        // Show loading
        this.loading.style.display = 'flex';

        try {
            const response = await this.getHelp(message);
            this.addMessage(response.response, 'ai');
        } catch (error) {
            console.error('AI Helper Error:', error);
            this.addMessage('Sorry, I encountered an error. Please try again or contact support if the issue persists.', 'ai');
        } finally {
            // Hide loading
            this.loading.style.display = 'none';
        }
    }

    addMessage(content, type) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `ai-message ${type}`;

        if (type === 'user') {
            messageDiv.innerHTML = `
                <div class="ai-content">
                    <p>${this.escapeHtml(content)}</p>
                </div>
                <div class="ai-avatar">👤</div>
            `;
        } else {
            messageDiv.innerHTML = `
                <div class="ai-avatar">🌱</div>
                <div class="ai-content">
                    <p>${this.formatResponse(content)}</p>
                </div>
            `;
        }

        this.messages.appendChild(messageDiv);
        this.messages.scrollTop = this.messages.scrollHeight;
    }

    formatContext(context) {
        const contextMap = {
            'succession-planning': 'Succession Planning',
            'user-management': 'User Management',
            'farmos-integration': 'farmOS Integration',
            'subscription-management': 'Subscription Management',
            'delivery-management': 'Delivery Management',
            'shipping-classes': 'Shipping Classes',
            'documentation': 'Documentation',
            'admin-general': 'Admin System'
        };

        return contextMap[context] || context.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }

    formatResponse(text) {
        // Basic markdown-like formatting
        return text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/`([^`]+)`/g, '<code>$1</code>')
            .replace(/\n/g, '<br>');
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    strContains(str, substr) {
        return str.indexOf(substr) !== -1;
    }

    updateSection(section) {
        this.currentSection = section;
        const contextElement = this.container.querySelector('.ai-helper-context');
        if (contextElement) {
            contextElement.textContent = section || this.formatContext(this.pageContext);
        }
    }
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize AI Helper Widget
    window.aiHelper = new AIHelperWidget({
        apiUrl: window.location.hostname === 'admin.soilsync.shop' ?
            'https://admin.soilsync.shop:8007/api/v1' :
            'http://localhost:8007/api/v1'
    });
});

// Export for manual initialization
window.AIHelperWidget = AIHelperWidget;