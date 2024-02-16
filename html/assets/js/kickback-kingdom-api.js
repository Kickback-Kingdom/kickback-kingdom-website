class KickbackKingdomAPI {
    constructor(sessionToken) {
        this.baseURL = "https://kickback-kingdom.com/api/v1/";
        this.sessionToken = sessionToken;
    }

    async makeRequest(endpoint, method, data = {}) {
        const url = `${this.baseURL}/${endpoint}`;
        
        const params = new URLSearchParams();

        for (const [key,value] of Object.entries(data)) {
            params.append(key, value);
        }

        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
                //'Content-Type': 'application/json',
                // Add any other headers like authentication tokens here
            },
        };

        if (method !== 'GET') {
            options.body = params;
        }

        try {
            const response = await fetch(url, options);
            return await response.json();
        } catch (error) {
            console.error('Error making API request:', error);
            throw error;
        }
    }

    // Account API Endpoints
    async login(email, password) {
        return await this.makeRequest('account/login', 'POST', { email, password });
    }

    async register(email, password, otherDetails) {
        return await this.makeRequest('account/register', 'POST', { email, password, ...otherDetails });
    }

    async updateAccount(details) {
        return await this.makeRequest('account/update', 'POST', details);
    }

    async searchAccount(query) {
        return await this.makeRequest('account/search', 'GET', { query });
    }

    // Chest API Endpoints
    async closeChest() {
        return await this.makeRequest('chest/close', 'POST');
    }

    // Item API Endpoints
    async getItem(itemId) {
        return await this.makeRequest(`item/get/${itemId}`, 'GET');
    }

    // Media API Endpoints
    async searchMedia(query) {
        return await this.makeRequest('media/search', 'GET', { query });
    }

    async uploadMedia(mediaData) {
        return await this.makeRequest('media/upload', 'POST', mediaData);
    }

    // Service API Endpoints
    async createService(serviceDetails) {
        return await this.makeRequest('service/create', 'POST', serviceDetails);
    }

    async deleteService(serviceId) {
        return await this.makeRequest(`service/delete/${serviceId}`, 'DELETE');
    }

    async listServices() {
        return await this.makeRequest('service/list', 'GET');
    }

    // Session API Endpoints
    async verifySession(sessionToken) {
        return await this.makeRequest('session/verifySession', 'POST', { sessionToken });
    }

    async requireSession(sessionToken) {
        return await this.makeRequest('session/requireSession', 'POST', { sessionToken });
    }

    // Add more functions for other API endpoints as needed
}

// Usage example
//const api = new KickbackKingdomAPI('https://api.kickbackkingdom.com');
/*api.login('user@example.com', 'password123').then(response => {
    console.log('Login response:', response);
});
*/