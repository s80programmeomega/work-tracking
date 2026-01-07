// resources\js\api\axios.js
import axios from 'axios';
import router from '@/router';

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL || 'https://work-tracking.online/',
    // baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
    withCredentials: true,
    timeout: 30000, // 30 seconds timeout
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// Request interceptor - Add auth token
api.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('auth_token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }

        // ✅ IMPORTANT: Ne pas forcer Content-Type si c'est FormData
        // Axios le détecte automatiquement et ajoute le boundary
        if (!(config.data instanceof FormData)) {
            config.headers['Content-Type'] = 'application/json';
        }
        return config;
    },
    (error) => Promise.reject(error)
);

// Response interceptor - Handle errors
api.interceptors.response.use(
    (response) => response,
    async (error) => {
        const originalRequest = error.config;

        // Skip token refresh for auth routes (login, register, etc.)
        const isAuthRoute = originalRequest.url?.includes('/auth/login') ||
                           originalRequest.url?.includes('/auth/register') ||
                           originalRequest.url?.includes('/auth/refresh');

        // 401 Unauthorized - Token expired (but not for auth routes)
        if (error.response?.status === 401 && !originalRequest._retry && !isAuthRoute) {
            originalRequest._retry = true;

            try {
                const { data } = await api.post('/auth/refresh');
                localStorage.setItem('auth_token', data.data.token);
                originalRequest.headers.Authorization = `Bearer ${data.data.token}`;
                return api(originalRequest);
            } catch (refreshError) {
                localStorage.removeItem('auth_token');
                localStorage.removeItem('user');
                router.push('/signin');
                return Promise.reject(refreshError);
            }
        }

        // 403 Forbidden - Insufficient permissions
        if (error.response?.status === 403) {
            router.push('/unauthorized');
        }

        return Promise.reject(error);
    }
);

export default api;
