<?php
declare(strict_types=1);

use Kickback\Common\Version;
?>
<script>
/**
 * Item API Component
 * Handles all item-related API calls
 */
class ItemAPI {
    /**
     * Get item by ID
     * @param {number|string} id - Item ID
     * @returns {Promise<Object>} API response with item data
     */
    static async getById(id) {
        if (!id) {
            throw new Error('Item ID is required');
        }

        try {
            const response = await fetch(`<?= Version::formatUrl('/api/v1/item/get.php'); ?>?id=${id}`, {
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
                throw new Error(jsonData.message || `Failed to get item with ID ${id}`);
            }

            return jsonData;

        } catch (error) {
            console.error(`ItemAPI.getById(${id}) failed:`, error);
            throw error;
        }
    }

    /**
     * Get multiple items by IDs (future expansion)
     * @param {Array<number>} ids - Array of item IDs
     * @returns {Promise<Array>} Array of item responses
     */
    static async getByIds(ids) {
        if (!Array.isArray(ids) || ids.length === 0) {
            throw new Error('Valid array of item IDs is required');
        }

        try {
            const promises = ids.map(id => this.getById(id));
            const results = await Promise.allSettled(promises);
            
            return results.map((result, index) => ({
                id: ids[index],
                success: result.status === 'fulfilled',
                data: result.status === 'fulfilled' ? result.value.data : null,
                error: result.status === 'rejected' ? result.reason.message : null
            }));

        } catch (error) {
            console.error('ItemAPI.getByIds failed:', error);
            throw error;
        }
    }
}

/**
 * Enhanced version of existing GetItemInformationById function
 * Checks cache first, then calls API if not found
 */
function GetItemInformationById(id) {
    // Check existing cache first (backward compatibility)
    if (window.itemInformation && Array.isArray(window.itemInformation)) {
        for (let index = 0; index < window.itemInformation.length; index++) {
            var item = window.itemInformation[index];
            if (item.crand == id) {
                console.log(`ItemAPI: Found item ${id} in cache`);
                return item;
            }
        }
    }
    
    // Not in cache - return null for now, could be enhanced to call API
    console.log(`ItemAPI: Item ${id} not found in cache`);
    return null;
}

/**
 * Enhanced version that can fetch from API if not in cache
 * @param {number|string} id - Item ID
 * @param {boolean} useAPI - Whether to call API if not in cache
 * @returns {Promise<Object>|Object|null} Item data
 */
async function GetItemInformationByIdWithAPI(id, useAPI = false) {
    // Check cache first
    const cachedItem = GetItemInformationById(id);
    if (cachedItem) {
        return cachedItem;
    }
    
    // If not in cache and API is allowed, fetch from API
    if (useAPI) {
        try {
            console.log(`ItemAPI: Fetching item ${id} from API`);
            const response = await ItemAPI.getById(id);
            
            // Add to cache for future use
            if (response.success && response.data) {
                if (!window.itemInformation) {
                    window.itemInformation = [];
                }
                window.itemInformation.push(response.data);
                console.log(`ItemAPI: Added item ${id} to cache`);
            }
            
            return response.data;
        } catch (error) {
            console.error(`ItemAPI: Failed to fetch item ${id}:`, error);
            return null;
        }
    }
    
    return null;
}

console.log('ItemAPI component loaded');
</script> 