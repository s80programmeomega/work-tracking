// resources\js\stores\authStore.js
import { defineStore } from 'pinia';
import { authAPI } from '@/api/auth';
import router from '@/router';

/**
 * STORE D'AUTHENTIFICATION - Gère tout ce qui concerne l'utilisateur connecté
 * 
 * Ce store centralise :
 * - La connexion/déconnexion
 * - Les informations utilisateur
 * - Les rôles et permissions
 * - La langue de l'utilisateur
 */
export const useAuthStore = defineStore('auth', {
    state: () => ({
        // Données utilisateur récupérées depuis le localStorage ou null si non connecté
        user: JSON.parse(localStorage.getItem('user')) || null,

        // Token JWT pour les requêtes API
        token: localStorage.getItem('auth_token') || null,

        // État de connexion (booléen)
        isAuthenticated: !!localStorage.getItem('auth_token'),

        // État de chargement pour les requêtes
        loading: false,

        // Stockage des erreurs
        error: null,

        // ✅ NOUVEAU : Langue de l'utilisateur (par défaut: français)
        language: localStorage.getItem('user_language') || 'fr'
    }),

    getters: {
        /**
         * Retourne l'utilisateur courant
         */
        currentUser: (state) => state.user,

        /**
         * Retourne tous les rôles de l'utilisateur
         * Gère à la fois le champ 'role' (string) et 'roles' (collection Spatie)
         */
        userRoles: (state) => {
            if (state.user?.role) {
                return [state.user.role]; // Format string
            }
            return state.user?.roles?.map(r => r.name) || []; // Format collection
        },

        /**
         * Retourne toutes les permissions de l'utilisateur
         * Combine permissions des rôles + permissions directes
         */
        userPermissions: (state) => {
            const rolePerms = state.user?.roles?.flatMap(r => r.permissions) || [];
            const directPerms = state.user?.permissions || [];
            return [...rolePerms, ...directPerms].map(p => p.name);
        },

        /**
         * Vérifie si l'utilisateur a un rôle spécifique
         */
        hasRole: (state) => (role) => {
            // Vérifie le champ 'role' (string)
            if (state.user?.role === role) return true;
            // Vérifie la collection 'roles' (Spatie)
            return state.user?.roles?.some(r => r.name === role) || false;
        },

        /**
         * Vérifie si l'utilisateur a au moins un des rôles demandés
         */
        hasAnyRole: (state) => (roles) => {
            // Vérifie d'abord le champ 'role'
            if (state.user?.role && roles.includes(state.user.role)) return true;
            // Puis vérifie la collection 'roles'
            return roles.some(role => state.user?.roles?.some(r => r.name === role));
        },

        /**
         * Vérifie si l'utilisateur a une permission spécifique
         */
        hasPermission: (state) => (permission) => {
            const allPerms = [
                ...state.user?.roles?.flatMap(r => r.permissions) || [],
                ...state.user?.permissions || []
            ];
            return allPerms.some(p => p.name === permission);
        },

        /**
         * Vérifie si l'utilisateur a au moins une des permissions demandées
         */
        hasAnyPermission: (state) => (permissions) => {
            const allPerms = [
                ...state.user?.roles?.flatMap(r => r.permissions) || [],
                ...state.user?.permissions || []
            ];
            return permissions.some(perm =>
                allPerms.some(p => p.name === perm)
            );
        },

        /**
         * Vérifie si l'utilisateur est super administrateur
         */
        isSuperAdmin: (state) => {
            return state.user?.roles?.some(r => r.name === 'super_admin') || false;
        },

        /**
         * ✅ NOUVEAU : Retourne la langue de l'utilisateur
         */
        currentLanguage: (state) => state.language,

        /**
         * ✅ NOUVEAU : Vérifie si la langue est française
         */
        isFrench: (state) => state.language === 'fr',

        /**
         * ✅ NOUVEAU : Vérifie si la langue est anglaise
         */
        isEnglish: (state) => state.language === 'en'
    },

    actions: {
        /**
         * INSCRIPTION - Crée un nouveau compte utilisateur
         */
        async register(data) {
            this.loading = true;
            this.error = null;

            try {
                const response = await authAPI.register(data);
                // Redirige vers la page de connexion après inscription
                router.push('/signin');
                return response.data;
            } catch (error) {
                // Gestion des erreurs avec message personnalisé
                this.error = error.response?.data?.message || 'Registration failed';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        /**
         * CONNEXION - Authentifie l'utilisateur
         */
        async login(credentials) {
            this.loading = true;
            this.error = null;

            try {
                const response = await authAPI.login(credentials);
                console.log('Réponse API:', response.data);

                const { user, token } = response.data.data;

                // Met à jour l'état du store
                this.user = user;
                this.token = token;
                this.isAuthenticated = true;

                // Sauvegarde dans le localStorage pour persister la connexion
                localStorage.setItem('user', JSON.stringify(user));
                localStorage.setItem('auth_token', token);

                // ✅ Met à jour la langue depuis les données utilisateur
                if (user.language) {
                    this.setLanguage(user.language);
                }

                // Redirige vers la page d'accueil
                router.push('/');
                return response.data;
            } catch (error) {
                // Messages d'erreur spécifiques selon le type d'erreur
                const errorMessage = error.response?.status === 401
                    ? 'Invalid email or password' // Identifiants incorrects
                    : error.response?.data?.message || error.response?.data?.error || 'Login failed';

                this.error = errorMessage;

                // Efface l'erreur après 5 secondes
                setTimeout(() => {
                    this.error = null;
                }, 5000);

                throw error;
            } finally {
                this.loading = false;
            }
        },

        /**
         * DÉCONNEXION - Déconnecte l'utilisateur
         */
        async logout() {
            this.loading = true;

            try {
                // Appel API pour invalider le token côté serveur
                await authAPI.logout();
            } catch (error) {
                console.error('Logout error:', error);
            } finally {
                // Nettoie l'état local quoi qu'il arrive
                this.user = null;
                this.token = null;
                this.isAuthenticated = false;

                // Nettoie le localStorage
                localStorage.removeItem('user');
                localStorage.removeItem('auth_token');

                // Redirige vers la page de connexion
                router.push('/signin');
                this.loading = false;
            }
        },

        /**
         * RÉCUPÈRE LES INFOS UTILISATEUR - Appel API pour mettre à jour les données
         */
        async fetchUser() {
            if (!this.token) return;

            this.loading = true;

            try {
                const response = await authAPI.getUser();
                this.user = response.data.data;

                // Sauvegarde les nouvelles données
                localStorage.setItem('user', JSON.stringify(this.user));

                // ✅ Met à jour la langue si elle a changé
                if (this.user.language && this.user.language !== this.language) {
                    this.setLanguage(this.user.language);
                }
            } catch (error) {
                // Si erreur, déconnecte l'utilisateur (token probablement expiré)
                this.logout();
                throw error;
            } finally {
                this.loading = false;
            }
        },

        /**
         * RAFRAÎCHIT LE TOKEN - Obtient un nouveau token
         */
        async refreshToken() {
            try {
                const response = await authAPI.refreshToken();
                this.token = response.data.data.token;
                localStorage.setItem('auth_token', this.token);
            } catch (error) {
                this.logout();
                throw error;
            }
        },

        /**
         * ✅ NOUVEAU : CHANGE LA LANGUE
         * @param {string} language - 'fr' ou 'en'
         */
        async setLanguage(language) {
            if (!['fr', 'en'].includes(language)) {
                console.warn('Langue non supportée:', language);
                return;
            }

            try {
                // Met à jour i18n
                const { i18n } = await import('@/locales');
                i18n.global.locale.value = language;

                // Met à jour le store
                this.language = language;
                localStorage.setItem('user_language', language);

                // Met à jour le document HTML pour l'accessibilité
                document.documentElement.lang = language;

                // Si l'utilisateur est connecté, met à jour son profil en base
                if (this.isAuthenticated) {
                    await authAPI.updateLanguage({ language });

                    // Met à jour l'utilisateur dans le store
                    if (this.user) {
                        this.user.language = language;
                        localStorage.setItem('user', JSON.stringify(this.user));
                    }
                }

                // Déclenche un événement pour notifier les autres composants
                window.dispatchEvent(new Event('languageChanged'));

            } catch (error) {
                console.error('Erreur lors du changement de langue:', error);
            }
        },

        /**
         * INITIALISATION - Doit être appelé au démarrage de l'app
         */
        initialize() {
            // Récupère la langue sauvegardée ou utilise le français par défaut
            const savedLanguage = localStorage.getItem('user_language') || 'fr';
            this.setLanguage(savedLanguage);

            // Configure axios pour les requêtes authentifiées
            if (this.token) {
                // Ta configuration axios existante...
            }

            // Récupère les données utilisateur si connecté
            if (this.isAuthenticated && !this.user) {
                this.fetchUser();
            }
        }

    },
});