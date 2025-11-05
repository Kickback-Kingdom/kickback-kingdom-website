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
            const response = await fetch(`api/v2/server/store/get-by-locator`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body:{
                    locator
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

    static async getStoreByAccount(accountId){
        if (!locator) {
            throw new Error('Account ID is required');
        }

        try {
            const response = await fetch(`api/v2/server/store/get-by-account`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body:{
                    accountId
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
                throw new Error(jsonData.message || `Failed to get store by account ID  ${accountId}`);
            }

            return jsonData;

        } catch (error) {
            console.error(`Store.getByAccount(${accountId}) failed:`, error);
            throw error;
        }
    }

    static async getCart(storeLocator){
        try {
            const bodyData = {
                "storeLocator": storeLocator
            };

            const response = await fetch(`api/v2/server/store/get-cart`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(bodyData)
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
                throw new Error(jsonData.message || `Failed to get cart for store locator ${storeLocator}`);
            }

            return jsonData;

        } catch (error) {
            console.error(`Store.getCart(${storeLocator}) failed:`, error);
            throw error;
        }
    }

    static async addProductToCartById(cart, productId){
        if (!cart) {
            throw new Error('Cart is required');
        }

        if (!productId) {
            throw new Error('ProductId is required');
        }

        const bodyData = {
            "cart": cart,
            "productId": productId
        };

        try {
            const response = await fetch(`api/v2/server/store/add-product-to-cart-by-id`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(bodyData)
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
                throw new Error(jsonData.message || `Failed to add product to cart by id`);
            }

            return jsonData;

        } catch (error) {
            console.error(`Store.addProductToCartById(${cart}, ${productId}) failed:`, error);
            throw error;
        }
    }

    static async addProductToCartByLocator(cart, productLocator){
        if (!cart) {
            throw new Error('Cart is required');
        }

        if (!productLocator) {
            throw new Error('productLocator is required');
        }

        const bodyData = {
            "cart": cart,
            "productLocator": productLocator
        };

        try {
            const response = await fetch(`api/v2/server/store/add-product-to-cart-by-locator`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(bodyData)
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
                throw new Error(jsonData.message || `Failed to add product to cart`);
            }

            return jsonData;

        } catch (error) {
            console.error(`Store.addProductToCartByLocator(${cart}, ${productLocator}) failed:`, error);
            throw error;
        }
    }

    static async removeProductFromCart(cartProduct){
        if (!cartProduct) {
            throw new Error('CartProduct is required');
        }

        const bodyData = {
            "cartProduct": cartProduct,
        };

        try {
            const response = await fetch(`api/v2/server/store/remove-product-from-cart`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(bodyData)
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
                throw new Error(jsonData.message || `Failed to remove product from cart`);
            }

            return jsonData;

        } catch (error) {
            console.error(`Store.removeProductFromCart(${cartProduct}) failed:`, error);
            throw error;
        }
    }

    static async checkoutCart(cart){
        if (!cart) {
            throw new Error('Cart is required');
        }

        const bodyData = {
            "cart": cart,
        };

        try {
            const response = await fetch(`api/v2/server/store/checkout-cart`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(bodyData)
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

    static async applyCoupon(cart, couponCode){
        if (!cart) {
            throw new Error('Cart is required');
        }

        if (!couponCode) {
            throw new Error('Coupon Code is required');
        }

        const bodyData = {
            "cart": cart,
            "couponCode": couponCode
        };

        try {
            const response = await fetch(`api/v2/server/store/apply-coupon`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(bodyData)
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
                throw new Error(jsonData.message || `Failed to apply coupon to cart`);
            }

            return jsonData;

        } catch (error) {
            console.error(`Store.applyCoupon(${cart}, ${couponCode}) failed:`, error);
            throw error;
        }
    }

    static async removeCouponFromProduct(cartProduct){
        if (!cartProduct) {
            throw new Error('CartProduct is required');
        }

        const bodyData = {
            "cartProduct": cartProduct,
        };

        try {
            const response = await fetch(`api/v2/server/store/remove-coupon-from-product`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(bodyData)
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
                throw new Error(jsonData.message || `Failed to remove coupon from product`);
            }

            return jsonData;

        } catch (error) {
            console.error(`Store.removeCouponFromProduct(${cartProduct}) failed:`, error);
            throw error;
        }
    }
}

console.log('Store component loaded');
