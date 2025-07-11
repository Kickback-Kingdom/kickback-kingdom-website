/**
 * Kickback Kingdom API Wrapper
 * Main interface that delegates to modular components
 */
class KickbackAPI {
    constructor() {
        this.version = '1.0.0';
        this.sessionToken = null;
        this.serviceKey = null;
        
        // Lazy-initialized component classes
        this._item = null;
        
        console.log('KickbackAPI wrapper initialized v' + this.version);
    }
    
    // Lazy getters for component classes
    get item() {
        if (!this._item) {
            this._item = ItemAPI; // Static class, no instantiation needed
        }
        return this._item;
    }
    

    /**
     * Set session token for authenticated requests
     * @param {string} token - Session token
     */
    setSessionToken(token) {
        this.sessionToken = token;
        console.log('KickbackAPI: Session token set');
    }

    /**
     * Set service key for API requests
     * @param {string} key - Service key
     */
    setServiceKey(key) {
        this.serviceKey = key;
        console.log('KickbackAPI: Service key set');
    }

    /**
     * Get current session token
     * @returns {string|null} Current session token
     */
    getSessionToken() {
        return this.sessionToken || window.kickbackSessionToken || null;
    }

    /**
     * Get current service key
     * @returns {string|null} Current service key
     */
    getServiceKey() {
        return this.serviceKey || window.kickbackServiceKey || null;
    }

    /**
     * Check if wrapper is ready for authenticated requests
     * @returns {boolean} True if session token is available
     */
    isAuthenticated() {
        return !!this.getSessionToken();
    }

    /**
     * Get wrapper status and loaded components
     * @returns {Object} Status information
     */
    getStatus() {
        return {
            version: this.version,
            authenticated: this.isAuthenticated(),
            sessionToken: !!this.getSessionToken(),
            serviceKey: !!this.getServiceKey(),
            loadedComponents: {
                item: typeof ItemAPI !== 'undefined',
                account: typeof AccountAPI !== 'undefined',
                media: typeof MediaAPI !== 'undefined',
                session: typeof SessionAPI !== 'undefined',
                lich: typeof LichAPI !== 'undefined',
                match: typeof MatchAPI !== 'undefined'
            }
        };
    }

    /**
     * Initialize wrapper with session data from PHP
     * @param {Object} config - Configuration object
     */
    initialize(config = {}) {
        if (config.sessionToken) {
            this.setSessionToken(config.sessionToken);
        }
        if (config.serviceKey) {
            this.setServiceKey(config.serviceKey);
        }
        
        console.log('KickbackAPI initialized with config:', this.getStatus());
    }
}

// Create global instance
window.KickbackAPI = KickbackAPI;
window.kickbackApi = new KickbackAPI();

// Auto-initialize with any existing session data
if (typeof window.kickbackSessionToken !== 'undefined') {
    window.kickbackApi.setSessionToken(window.kickbackSessionToken);
}
if (typeof window.kickbackServiceKey !== 'undefined') {
    window.kickbackApi.setServiceKey(window.kickbackServiceKey);
}

console.log('KickbackAPI wrapper ready. Access via window.kickbackApi');
console.log('Example: await kickbackApi.item.getById(123)');
console.log('Status: kickbackApi.getStatus()'); 