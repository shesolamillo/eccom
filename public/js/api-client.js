/**
 * ECCOM API Client Library
 * Provides convenient methods for interacting with the ECCOM API
 */

class EccomApiClient {
    constructor(baseUrl = 'http://localhost:8000/api') {
        this.baseUrl = baseUrl;
        this.defaultHeaders = {
            'Content-Type': 'application/json'
        };
    }

    /**
     * Make HTTP request to the API
     * @param {string} endpoint - API endpoint path
     * @param {string} method - HTTP method (GET, POST, PUT, etc.)
     * @param {object} data - Request body data
     * @returns {Promise}
     */
    async request(endpoint, method = 'GET', data = null) {
        const options = {
            method,
            headers: this.defaultHeaders
        };

        if (data && (method === 'POST' || method === 'PUT' || method === 'PATCH')) {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(`${this.baseUrl}${endpoint}`, options);
            
            if (!response.ok) {
                throw new Error(`API Error: ${response.status} ${response.statusText}`);
            }

            return await response.json();
        } catch (error) {
            console.error('API Request Error:', error);
            throw error;
        }
    }

    /**
     * Build query string from object
     * @param {object} params - Query parameters
     * @returns {string}
     */
    buildQueryString(params) {
        return Object.keys(params)
            .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(params[key])}`)
            .join('&');
    }

    // ============================================
    // PRODUCTS
    // ============================================

    /**
     * Get all products
     * @param {object} options - Query options (page, limit, category, type, search)
     * @returns {Promise}
     */
    async getProducts(options = {}) {
        const queryString = this.buildQueryString({
            page: options.page || 1,
            limit: options.limit || 20,
            ...(options.category && { category: options.category }),
            ...(options.type && { type: options.type }),
            ...(options.search && { search: options.search })
        });

        return this.request(`/products?${queryString}`);
    }

    /**
     * Get single product
     * @param {number} productId - Product ID
     * @returns {Promise}
     */
    async getProduct(productId) {
        return this.request(`/products/${productId}`);
    }

    /**
     * Get product stock
     * @param {number} productId - Product ID
     * @returns {Promise}
     */
    async getProductStock(productId) {
        return this.request(`/products/${productId}/stock`);
    }

    // ============================================
    // ORDERS
    // ============================================

    /**
     * Get all orders
     * @param {object} options - Query options (page, limit, status, userId)
     * @returns {Promise}
     */
    async getOrders(options = {}) {
        const queryString = this.buildQueryString({
            page: options.page || 1,
            limit: options.limit || 20,
            ...(options.status && { status: options.status }),
            ...(options.userId && { userId: options.userId })
        });

        return this.request(`/orders?${queryString}`);
    }

    /**
     * Get single order
     * @param {number} orderId - Order ID
     * @returns {Promise}
     */
    async getOrder(orderId) {
        return this.request(`/orders/${orderId}`);
    }

    /**
     * Create new order
     * @param {object} orderData - Order data with userId and items
     * @returns {Promise}
     */
    async createOrder(orderData) {
        return this.request('/orders', 'POST', orderData);
    }

    /**
     * Update order
     * @param {number} orderId - Order ID
     * @param {object} updateData - Data to update (status, etc.)
     * @returns {Promise}
     */
    async updateOrder(orderId, updateData) {
        return this.request(`/orders/${orderId}`, 'PUT', updateData);
    }

    // ============================================
    // STOCK
    // ============================================

    /**
     * Get all stock items
     * @param {object} options - Query options (page, limit, productId)
     * @returns {Promise}
     */
    async getStock(options = {}) {
        const queryString = this.buildQueryString({
            page: options.page || 1,
            limit: options.limit || 20,
            ...(options.productId && { productId: options.productId })
        });

        return this.request(`/stock?${queryString}`);
    }

    /**
     * Get single stock item
     * @param {number} stockId - Stock ID
     * @returns {Promise}
     */
    async getStockItem(stockId) {
        return this.request(`/stock/${stockId}`);
    }

    /**
     * Update stock
     * @param {number} stockId - Stock ID
     * @param {object} updateData - Data to update (quantity, reorderLevel)
     * @returns {Promise}
     */
    async updateStock(stockId, updateData) {
        return this.request(`/stock/${stockId}`, 'PUT', updateData);
    }

    /**
     * Get low stock items
     * @param {object} options - Query options (page, limit)
     * @returns {Promise}
     */
    async getLowStock(options = {}) {
        const queryString = this.buildQueryString({
            page: options.page || 1,
            limit: options.limit || 20
        });

        return this.request(`/stock/low-stock?${queryString}`);
    }

    // ============================================
    // USERS
    // ============================================

    /**
     * Get all users
     * @param {object} options - Query options (page, limit, role)
     * @returns {Promise}
     */
    async getUsers(options = {}) {
        const queryString = this.buildQueryString({
            page: options.page || 1,
            limit: options.limit || 20,
            ...(options.role && { role: options.role })
        });

        return this.request(`/users?${queryString}`);
    }

    /**
     * Get single user
     * @param {number} userId - User ID
     * @returns {Promise}
     */
    async getUser(userId) {
        return this.request(`/users/${userId}`);
    }
}

// Export for use in browser or Node.js
if (typeof module !== 'undefined' && module.exports) {
    module.exports = EccomApiClient;
}
