/**
 * MWF Integration API Client
 * Handles communication with WooCommerce via the MWF WordPress plugin
 */

class MWFApiClient {
    constructor() {
        this.baseUrl = '/admin/products/mwf-integration';
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }

    /**
     * Make an HTTP request with proper headers
     */
    async request(method, endpoint, data = null) {
        const config = {
            method: method.toUpperCase(),
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }
        };

        if (this.csrfToken) {
            config.headers['X-CSRF-TOKEN'] = this.csrfToken;
        }

        if (data && (method.toUpperCase() === 'POST' || method.toUpperCase() === 'PUT')) {
            config.body = JSON.stringify(data);
        }

        const url = `${this.baseUrl}${endpoint}`;
        const response = await fetch(url, config);

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({ message: 'Network error' }));
            throw new Error(errorData.message || `HTTP ${response.status}: ${response.statusText}`);
        }

        return await response.json();
    }

    /**
     * Get WooCommerce capabilities
     */
    async getCapabilities() {
        return await this.request('GET', '/capabilities');
    }

    /**
     * Get product data for editing
     */
    async getProduct(productId) {
        return await this.request('GET', `/products/${productId}/edit`);
    }

    /**
     * Update product data
     */
    async updateProduct(productId, productData) {
        return await this.request('PUT', `/products/${productId}`, productData);
    }

    /**
     * Get product variations
     */
    async getProductVariations(productId) {
        return await this.request('GET', `/products/${productId}/variations`);
    }

    /**
     * Create a new product variation
     */
    async createVariation(productId, variationData) {
        return await this.request('POST', `/products/${productId}/variations`, variationData);
    }

    /**
     * Update an existing variation
     */
    async updateVariation(variationId, variationData) {
        return await this.request('PUT', `/products/variations/${variationId}`, variationData);
    }

    /**
     * Delete a variation
     */
    async deleteVariation(variationId) {
        return await this.request('DELETE', `/products/variations/${variationId}`);
    }

    /**
     * Get product attributes
     */
    async getProductAttributes(productId) {
        return await this.request('GET', `/products/${productId}/attributes`);
    }

    /**
     * Update product attributes
     */
    async updateProductAttributes(productId, attributesData) {
        return await this.request('POST', `/products/${productId}/attributes`, attributesData);
    }

    /**
     * Bulk update variations
     */
    async bulkUpdateVariations(updates) {
        return await this.request('POST', '/products/variations/bulk-update', updates);
    }

    /**
     * Execute bulk action on products
     */
    async executeAction(action, productIds, params = {}) {
        const data = {
            action,
            product_ids: productIds,
            ...params
        };
        return await this.request('POST', '/actions', data);
    }

    /**
     * Bulk update products
     */
    async bulkUpdate(updates) {
        return await this.request('POST', '/products/bulk-update', updates);
    }
}

// Create global instance
window.mwfApi = new MWFApiClient();

// Dispatch custom event to notify that MWF API is ready
window.dispatchEvent(new CustomEvent('mwfApiReady', { detail: window.mwfApi }));

export default MWFApiClient;