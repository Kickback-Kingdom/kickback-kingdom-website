/**
 * Item API Component
 * Handles all item-related API calls
 */
class StoreClient {
    /**
     * Get store by locator
     * @param {string} locator - Store Locator
     * @returns {Promise<Object>} API response with store data
     */
    static async getStoreByLocator(locator) {
        if (!locator) {
            throw new Error('Store Locator is required');
        }

        try {
            const response = await fetch(`api/v2/server/store/get-by-locator?locator=${locator}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.text();
            let jsonData;
            
            try {
                jsonData = JSON.parse(data);
            } catch (parseError) {
                throw new Error('Invalid JSON response from server');
            }

            if (!jsonData.success) {
                throw new Error(jsonData.message || `Failed to get store with locator ${locator}`);
            }

            return jsonData;

        } catch (error) {
            console.error(`Store.getByLocator(${locator}) failed:`, error);
            throw error;
        }
    }

    static async checkoutCart(cart){
        if (!cart) {
            throw new Error('Cart is required');
        }

        try {
            const response = await fetch(`api/v2/server/store/checkout-cart`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body:{
                    cart
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.text();
            let jsonData;
            
            try {
                jsonData = JSON.parse(data);
            } catch (parseError) {
                throw new Error('Invalid JSON response from server');
            }

            if (!jsonData.success) {
                throw new Error(jsonData.message || `Failed to checkout cart`);
            }

            return jsonData;

        } catch (error) {
            console.error(`Store.checkoutCart failed:`, error);
            throw error;
        }
    }
}

console.log('Store component loaded');
