// resources\js\api\axios.js
import axios from 'axios';
import router from '@/router';

const api = axios.create({
    // baseURL: import.meta.env.VITE_API_URL || 'https://work-tracking.online/',
    baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
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

        // Pour FormData, supprimer Content-Type pour que le navigateur
        // le définisse automatiquement avec le boundary multipart correct.
        // Axios 1.x convertit sinon FormData → JSON si Content-Type: application/json est présent.
        if (config.data instanceof FormData) {
            delete config.headers['Content-Type'];
        } else {
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
        const isAuthRoute = originalRequest.url?.includes('/auth/login') || originalRequest.url?.includes('/auth/register') ||
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

        // 403 Forbidden - rediriger uniquement pour les requêtes de navigation (GET)
        // Les actions (POST/PUT/DELETE) gèrent l'erreur en local dans le composant appelant
        if (error.response?.status === 403 && originalRequest.method?.toLowerCase() === 'get') {
            router.push('/unauthorized');
        }

        return Promise.reject(error);
    }
);

export default api;
