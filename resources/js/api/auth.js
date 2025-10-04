import api from './axios';

export const authAPI = {
    // Get CSRF cookie first (for session-based auth)
    async getCsrfCookie() {
        await api.get('/sanctum/csrf-cookie');
    },

    async register(data) {
        await this.getCsrfCookie();
        return api.post('/auth/register', data);
    },

    async login(credentials) {
        await this.getCsrfCookie();
        return api.post('/auth/login', credentials);
    },

    async logout() {
        return api.post('/auth/logout');
    },

    async getUser() {
        return api.get('/auth/me');
    },

    async refreshToken() {
        return api.post('/auth/refresh');
    },

    async verifyEmail() {
        return api.post('/auth/verify-email');
    },
};
