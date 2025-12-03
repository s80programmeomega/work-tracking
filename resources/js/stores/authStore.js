// resources\js\stores\authStore.js - VERSION HIÉRARCHIQUE
import { defineStore } from 'pinia';
import { authAPI } from '@/api/auth';
import router from '@/router';
import axios from 'axios';
import { i18n } from '@/locales';

/**
 * STORE D'AUTHENTIFICATION - Système Hiérarchique
 * 
 * Structure des permissions :
 * - Workspace (Niveau 1) : owner, admin, member
 * - Projet (Niveau 2) : responsable, membre (+ pivot permissions)
 * - Activité (Niveau 3) : responsable, membre (+ pivot permissions)
 * - Tâche (Niveau 4) : assigné (+ pivot permissions)
 * 
 * Super Admin : Accès total à tout
 */
export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('user')) || null,
        token: localStorage.getItem('auth_token') || null,
        isAuthenticated: !!localStorage.getItem('auth_token'),
        loading: false,
        error: null,
        language: localStorage.getItem('user_language') || 'fr',
        tokenExpiry: null,
        refreshInterval: null,

        // ⭐ NOUVEAU: Gestion du timeout d'inactivité
        inactivityTimeout: 60 * 60 * 1000, // 60 minutes par défaut
        inactivityTimer: null,
        lastActivity: Date.now(),
        warningTime: 5 * 60 * 1000, // 5 minutes avant déconnexion
        warningShown: false,

    }),

    getters: {
        /**
         * Retourne l'utilisateur courant
         */
        currentUser: (state) => state.user,

        /**
         * ✅ Vérifie si super admin (accès total)
         */
        isSuperAdmin: (state) => {
            if (!state.user) return false;
            return state.user.is_super_admin === true;
        },

        /**
         * ✅ Vérifie si admin (accès étendu)
         */
        isAdmin: (state) => {
            if (!state.user) return false;

            // Super admin est aussi admin
            if (state.user.is_super_admin === true) return true;

            // Vérifie le champ role
            if (state.user.role) {
                const role = state.user.role.toLowerCase();
                return role === 'admin' || role === 'super_admin';
            }

            return false;
        },

        /**
         * ✅ Hiérarchie des rôles système
         */
        roleLevel: (state) => {
            if (!state.user) return 0;

            const hierarchy = {
                'super_admin': 7,
                'admin': 6,
                'manager': 5,
                'responsable_n1': 4,
                'responsable_n2': 3,
                'cadre': 2,
                'stagiaire': 1,
            };

            const userRole = state.user.role?.toLowerCase() || '';
            return hierarchy[userRole] || 0;
        },

        /**
         * ✅ Workspace actuel
         */
        currentWorkspace: (state) => {
            if (!state.user) return null;
            return state.user.current_workspace;
        },

        currentWorkspaceId: (state) => {
            if (!state.user) return null;
            return state.user.current_workspace_id;
        },

        /**
         * ✅ Liste des workspaces accessibles
         */
        userWorkspaces: (state) => {
            if (!state.user) return [];
            if (!Array.isArray(state.user.workspaces)) return [];
            return state.user.workspaces;
        },

        /**
         * ✅ Informations utilisateur
         */
        userName: (state) => {
            if (!state.user) return 'Invité';
            return state.user.nom_complet || state.user.nom || state.user.prenom || state.user.email || 'Utilisateur';
        },

        userEmail: (state) => {
            if (!state.user) return '';
            return state.user.email || '';
        },

        userAvatar: (state) => {
            if (!state.user) return null;
            return state.user.avatar_url || state.user.avatar || null;
        },

        userInitials: (state) => {
            if (!state.user) return '?';
            return state.user.initials || '?';
        },

        userRole: (state) => {
            if (!state.user) return null;
            return state.user.role || null;
        },

        /**
         * ✅ Langue et préférences
         */
        currentLanguage: (state) => state.language,
        isFrench: (state) => state.language === 'fr',
        isEnglish: (state) => state.language === 'en',

        /**
         * ✅ Statut utilisateur
         */
        isActive: (state) => {
            if (!state.user) return false;
            return state.user.is_active === true;
        },

        lastLoginAt: (state) => {
            if (!state.user) return null;
            return state.user.last_login_at;
        },
    },

    actions: {

        // ==========================================
        // GESTION DE L'INACTIVITÉ
        // ==========================================

        /**
         * ⭐ Initialiser le timer d'inactivité
         */
        initializeInactivityTimer() {
            this.resetInactivityTimer();

            // Événements utilisateur qui réinitialisent le timer
            const events = ['mousedown', 'keydown', 'scroll', 'touchstart', 'mousemove'];

            events.forEach(event => {
                document.addEventListener(event, () => {
                    this.resetInactivityTimer();
                });
            });

            // Vérifier périodiquement l'inactivité
            setInterval(() => {
                this.checkInactivity();
            }, 60 * 1000); // Vérifie toutes les minutes
        },

        /**
         * ⭐ Réinitialiser le timer d'inactivité
         */
        resetInactivityTimer() {
            if (!this.isAuthenticated) return;

            this.lastActivity = Date.now();

            if (this.inactivityTimer) {
                clearTimeout(this.inactivityTimer);
            }

            // Cacher l'avertissement si affiché
            if (this.warningShown) {
                this.hideTimeoutWarning();
            }

            // Définir le nouveau timer
            this.inactivityTimer = setTimeout(() => {
                this.showTimeoutWarning();
            }, this.inactivityTimeout - this.warningTime);
        },

        /**
         * ⭐ Vérifier l'inactivité
         */
        checkInactivity() {
            if (!this.isAuthenticated) return;

            const now = Date.now();
            const inactiveTime = now - this.lastActivity;

            // Si l'utilisateur est inactif plus longtemps que le timeout
            if (inactiveTime > this.inactivityTimeout) {
                this.autoLogout();
            }
            // Si proche de la déconnexion et avertissement non affiché
            else if (inactiveTime > (this.inactivityTimeout - this.warningTime) && !this.warningShown) {
                this.showTimeoutWarning();
            }
        },

        /**
         * ⭐ Afficher l'avertissement de timeout
         */
        showTimeoutWarning() {
            if (this.warningShown || !this.isAuthenticated) return;

            this.warningShown = true;

            // Créer ou mettre à jour l'overlay d'avertissement
            let warningEl = document.getElementById('inactivity-warning');

            if (!warningEl) {
                warningEl = document.createElement('div');
                warningEl.id = 'inactivity-warning';
                warningEl.className = 'inactivity-warning';

                warningEl.innerHTML = `
                    <div class="warning-content">
                        <h3>Session sur le point d'expirer</h3>
                        <p>Vous serez déconnecté automatiquement dans ${this.warningTime / 60000} minutes.</p>
                        <div class="warning-actions">
                            <button id="stay-logged-in" class="btn-primary">
                                Rester connecté
                            </button>
                            <button id="logout-now" class="btn-secondary">
                                Déconnexion
                            </button>
                        </div>
                    </div>
                `;

                document.body.appendChild(warningEl);

                // Gestionnaires d'événements
                document.getElementById('stay-logged-in').addEventListener('click', () => {
                    this.resetInactivityTimer();
                    this.hideTimeoutWarning();
                });

                document.getElementById('logout-now').addEventListener('click', () => {
                    this.autoLogout();
                });
            }

            // Définir le timer pour déconnexion automatique
            setTimeout(() => {
                if (this.warningShown) {
                    this.autoLogout();
                }
            }, this.warningTime);
        },

        /**
         * ⭐ Cacher l'avertissement
         */
        hideTimeoutWarning() {
            this.warningShown = false;
            const warningEl = document.getElementById('inactivity-warning');
            if (warningEl) {
                warningEl.remove();
            }
        },

        /**
         * ⭐ Déconnexion automatique
         */
        async autoLogout() {
            console.log('🔒 Déconnexion automatique pour inactivité');

            this.hideTimeoutWarning();

            // Afficher notification
            this.showLogoutNotification();

            // Attendre 2 secondes pour que l'utilisateur voie la notification
            await new Promise(resolve => setTimeout(resolve, 2000));

            // Déconnexion
            await this.logout();
        },

        /**
         * ⭐ Afficher notification de déconnexion
         */
        showLogoutNotification() {
            // Vous pouvez utiliser votre système de notifications existant
            // ou créer une notification simple
            const notification = document.createElement('div');
            notification.className = 'logout-notification';
            notification.innerHTML = `
                <div class="notification-content">
                    <span>🔄 Déconnexion automatique pour inactivité</span>
                </div>
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 3000);
        },

        /**
         * ⭐ Définir le timeout d'inactivité
         */
        setTimeoutDuration(minutes) {
            this.inactivityTimeout = minutes * 1 * 1000;
            localStorage.setItem('inactivity_timeout', minutes);
            this.resetInactivityTimer();
        },

        /**
         * ⭐ Récupérer le timeout configuré
         */
        getTimeoutDuration() {
            return this.inactivityTimeout / (1 * 1000); // Retourne en minutes
        },

        // ==========================================
        // GESTION HIÉRARCHIQUE DES PERMISSIONS
        // ==========================================

        /**
         * ✅ Vérifie si l'utilisateur a un niveau de rôle minimum
         */
        // hasRoleLevel(requiredRole) {
        //     if (!this.user) return false;

        //     // Super admin a tous les accès
        //     if (this.isSuperAdmin) return true;

        //     const hierarchy = {
        //         'super_admin': 7,
        //         'admin': 6,
        //         'manager': 5,
        //         'responsable_n1': 4,
        //         'responsable_n2': 3,
        //         'cadre': 2,
        //         'stagiaire': 1,
        //     };

        //     const userLevel = hierarchy[this.user.role?.toLowerCase()] || 0;
        //     const requiredLevel = hierarchy[requiredRole.toLowerCase()] || 0;

        //     return userLevel >= requiredLevel;
        // },

        /**
         * ✅ WORKSPACE : Vérifie l'accès à un workspace
         */
        canAccessWorkspace(workspaceId) {
            if (!this.user || !workspaceId) return false;

            // Super admin a accès à tout
            if (this.isSuperAdmin) return true;

            // Vérifie si membre du workspace
            const workspaces = this.userWorkspaces;
            return workspaces.some(w => w.id === workspaceId);
        },

        /**
         * ✅ WORKSPACE : Récupère le rôle dans un workspace
         */
        getWorkspaceRole(workspaceId) {
            if (!this.user || !workspaceId) return null;

            // Super admin = owner
            if (this.isSuperAdmin) return 'owner';

            const workspaces = this.userWorkspaces;
            const workspace = workspaces.find(w => w.id === workspaceId);

            return workspace?.pivot?.role || null;
        },

        /**
         * ✅ WORKSPACE : Vérifie si owner du workspace
         */
        isWorkspaceOwner(workspaceId) {
            if (!this.user || !workspaceId) return false;
            if (this.isSuperAdmin) return true;

            return this.getWorkspaceRole(workspaceId) === 'owner';
        },

        /**
         * ✅ WORKSPACE : Vérifie si admin du workspace
         */
        isWorkspaceAdmin(workspaceId) {
            if (!this.user || !workspaceId) return false;
            if (this.isSuperAdmin) return true;

            const role = this.getWorkspaceRole(workspaceId);
            return role === 'owner' || role === 'admin';
        },

        /**
         * ✅ WORKSPACE : Vérifie une permission dans un workspace
         */
        hasWorkspacePermission(workspaceId, permission) {
            if (!this.user || !workspaceId) return false;

            // Super admin a toutes les permissions
            if (this.isSuperAdmin) return true;

            const workspaces = this.userWorkspaces;
            const workspace = workspaces.find(w => w.id === workspaceId);

            if (!workspace) return false;

            // Owner et admin ont toutes les permissions
            const role = workspace.pivot?.role;
            if (role === 'owner' || role === 'admin') return true;

            // Vérifie les permissions spécifiques
            const permissions = workspace.pivot?.permissions;
            if (!permissions) return false;

            if (typeof permissions === 'string') {
                try {
                    const parsed = JSON.parse(permissions);
                    return Array.isArray(parsed) && parsed.includes(permission);
                } catch {
                    return false;
                }
            }

            if (Array.isArray(permissions)) {
                return permissions.includes(permission);
            }

            return false;
        },

        /**
         * ✅ PROJET : Vérifie si responsable d'un projet
         */
        isProjetResponsable(projetId) {
            if (!this.user || !projetId) return false;
            if (this.isSuperAdmin) return true;

            const projets = this.user.projets || [];
            const projet = projets.find(p => p.id === projetId);

            if (!projet) return false;

            // Vérifie si responsable via pivot
            return projet.pivot?.role === 'responsable' ||
                projet.responsable_id === this.user.id;
        },

        /**
         * ✅ PROJET : Vérifie une permission sur un projet
         */
        hasProjetPermission(projetId, permission) {
            if (!this.user || !projetId) return false;
            if (this.isSuperAdmin) return true;

            const projets = this.user.projets || [];
            const projet = projets.find(p => p.id === projetId);

            if (!projet) return false;

            // Responsable a toutes les permissions
            if (this.isProjetResponsable(projetId)) return true;

            // Vérifie permission spécifique
            return projet.pivot?.[permission] === true;
        },

        /**
         * ✅ ACTIVITÉ : Vérifie si responsable d'une activité
         */
        isActiviteResponsable(activiteId) {
            if (!this.user || !activiteId) return false;
            if (this.isSuperAdmin) return true;

            const activites = this.user.activites || [];
            const activite = activites.find(a => a.id === activiteId);

            if (!activite) return false;

            return activite.pivot?.role === 'responsable' ||
                activite.responsable_id === this.user.id;
        },

        /**
         * ✅ ACTIVITÉ : Vérifie une permission sur une activité
         */
        hasActivitePermission(activiteId, permission) {
            if (!this.user || !activiteId) return false;
            if (this.isSuperAdmin) return true;

            const activites = this.user.activites || [];
            const activite = activites.find(a => a.id === activiteId);

            if (!activite) return false;

            // Responsable a toutes les permissions
            if (this.isActiviteResponsable(activiteId)) return true;

            // Vérifie permission spécifique (ex: can_create_tasks, can_validate_results)
            return activite.pivot?.[permission] === true;
        },

        /**
         * ✅ TÂCHE : Vérifie si assigné à une tâche
         */
        isAssignedToTache(tacheId) {
            if (!this.user || !tacheId) return false;
            if (this.isSuperAdmin) return true;

            const taches = this.user.taches || [];
            return taches.some(t => t.id === tacheId);
        },

        /**
         * ✅ TÂCHE : Vérifie une permission sur une tâche
         */
        hasTachePermission(tacheId, permission) {
            if (!this.user || !tacheId) return false;
            if (this.isSuperAdmin) return true;

            const taches = this.user.taches || [];
            const tache = taches.find(t => t.id === tacheId);

            if (!tache) return false;

            // Vérifie permission spécifique (ex: can_edit, can_complete, can_validate)
            return tache.pivot?.[permission] === true;
        },

        /**
         * ✅ VALIDATION : Vérifie si peut valider N1
         */
        canValidateN1(activiteId) {
            if (!this.user || !activiteId) return false;
            if (this.isSuperAdmin) return true;

            // Responsable activité peut valider N1
            if (this.isActiviteResponsable(activiteId)) return true;

            // Ou membre avec permission can_validate_results
            return this.hasActivitePermission(activiteId, 'can_validate_results');
        },

        /**
         * ✅ VALIDATION : Vérifie si peut valider N2
         */
        canValidateN2(projetId) {
            if (!this.user || !projetId) return false;
            if (this.isSuperAdmin) return true;

            // Responsable projet peut valider N2
            return this.isProjetResponsable(projetId);
        },

        /**
         * ✅ Vérifie si peut gérer un utilisateur
         */
        canManageUser(targetUser) {
            if (!this.user || !targetUser) return false;

            // Super admin peut tout gérer
            if (this.isSuperAdmin) return true;

            // Admin peut gérer tous sauf super admin
            if (this.isAdmin) {
                return targetUser.is_super_admin !== true;
            }

            return false;
        },

        // ==========================================
        // CONFIGURATION AXIOS
        // ==========================================
        setAxiosToken(token) {
            if (token) {
                axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
            } else {
                delete axios.defaults.headers.common['Authorization'];
            }
        },

        // ==========================================
        // INSCRIPTION
        // ==========================================
        async register(data) {
            this.loading = true;
            this.error = null;
            try {
                const response = await authAPI.register(data);
                router.push('/signin');
                return response.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Registration failed';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        // ==========================================
        // CONNEXION
        // ==========================================
        async login(credentials) {
            this.loading = true;
            this.error = null;
            try {
                const response = await authAPI.login(credentials);
                const { user, token, expires_at } = response.data.data;

                this.user = user;
                this.token = token;
                this.isAuthenticated = true;
                this.tokenExpiry = expires_at ? new Date(expires_at).getTime() : null;

                localStorage.setItem('user', JSON.stringify(user));
                localStorage.setItem('auth_token', token);
                localStorage.setItem('user_language', user.language || 'fr');

                this.setAxiosToken(token);

                // Mise à jour de la langue
                if (user.language) this.setLanguage(user.language);

                // Auto refresh token
                if (expires_at) {
                    this.startTokenAutoRefresh();
                }

                console.log('✅ Connexion réussie:', {
                    user: user.nom || user.email,
                    role: user.role,
                    roleLevel: this.roleLevel,
                    isSuperAdmin: this.isSuperAdmin,
                    workspaces: user.workspaces?.length || 0
                });

                // Redirection après login
                const invitationToken = router.currentRoute.value.query.invitation;
                if (invitationToken) {
                    router.push(`/accept-invitation/${invitationToken}`);
                } else {
                    router.push('/');
                }

                return response.data;
            } catch (error) {
                console.error('❌ Erreur login:', error);
                this.error = error.response?.data?.message || 'Login failed';
                setTimeout(() => (this.error = null), 5000);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        // ==========================================
        // DÉCONNEXION
        // ==========================================
         async logout() {
            this.loading = true;
            try {
                // ⭐ Nettoyer les timers d'inactivité
                if (this.inactivityTimer) {
                    clearTimeout(this.inactivityTimer);
                    this.inactivityTimer = null;
                }
                
                this.hideTimeoutWarning();
                
                await authAPI.logout();
            } catch (error) {
                console.error('Logout error:', error);
            } finally {
                this.clearAuth();
                router.push('/signin');
                this.loading = false;
            }
        },

       clearAuth() {
            // ⭐ Nettoyer les timers
            if (this.inactivityTimer) {
                clearTimeout(this.inactivityTimer);
                this.inactivityTimer = null;
            }
            
            this.hideTimeoutWarning();
            this.warningShown = false;
            
            this.user = null;
            this.token = null;
            this.isAuthenticated = false;
            this.tokenExpiry = null;
            this.stopTokenAutoRefresh();
            localStorage.removeItem('user');
            localStorage.removeItem('auth_token');
            this.setAxiosToken(null);
        },

        // ==========================================
        // RÉCUPÉRATION USER
        // ==========================================
        async fetchUser() {
            if (!this.token) {
                console.warn('⚠️ Pas de token, impossible de charger l\'utilisateur');
                return;
            }

            this.loading = true;
            try {
                const response = await authAPI.getUser();
                this.user = response.data.data || response.data;
                localStorage.setItem('user', JSON.stringify(this.user));

                if (this.user.language) {
                    this.setLanguage(this.user.language);
                }

                console.log('✅ Utilisateur chargé:', {
                    user: this.user.nom || this.user.email,
                    role: this.user.role,
                    roleLevel: this.roleLevel,
                    isSuperAdmin: this.isSuperAdmin,
                    workspaces: this.user.workspaces?.length || 0
                });
            } catch (error) {
                console.error('❌ Erreur fetchUser:', error);

                // Si 401, déconnecter
                if (error.response?.status === 401) {
                    await this.logout();
                }

                throw error;
            } finally {
                this.loading = false;
            }
        },

        // ==========================================
        // RAFRAÎCHISSEMENT TOKEN
        // ==========================================
        async refreshToken() {
            try {
                const response = await authAPI.refreshToken();
                const { token, expires_at } = response.data.data;
                this.token = token;
                this.tokenExpiry = expires_at ? new Date(expires_at).getTime() : null;
                localStorage.setItem('auth_token', token);
                this.setAxiosToken(token);
                console.log('✅ Token rafraîchi');
            } catch (error) {
                console.error('❌ Erreur refresh token:', error);
                await this.logout();
                throw error;
            }
        },

        startTokenAutoRefresh() {
            this.stopTokenAutoRefresh();

            if (!this.tokenExpiry) {
                console.warn('⚠️ Pas de tokenExpiry, auto-refresh désactivé');
                return;
            }

            const refreshBefore = 60 * 1000; // 1 min avant expiration

            this.refreshInterval = setInterval(() => {
                if (!this.tokenExpiry) return;

                const now = Date.now();
                const timeUntilExpiry = this.tokenExpiry - now;

                if (timeUntilExpiry <= refreshBefore) {
                    console.log('🔄 Rafraîchissement automatique du token...');
                    this.refreshToken();
                }
            }, 30 * 1000); // vérifie toutes les 30 sec

            console.log('✅ Auto-refresh token activé');
        },

        stopTokenAutoRefresh() {
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
                this.refreshInterval = null;
            }
        },

        // ==========================================
        // LANGUE
        // ==========================================
        async setLanguage(language) {
            if (!['fr', 'en'].includes(language)) {
                console.warn(`⚠️ Langue non supportée: ${language}`);
                return;
            }

            i18n.global.locale.value = language;
            this.language = language;
            localStorage.setItem('user_language', language);
            document.documentElement.lang = language;

            if (this.isAuthenticated) {
                try {
                    await authAPI.updateLanguage({ language });

                    if (this.user) {
                        this.user.language = language;
                        localStorage.setItem('user', JSON.stringify(this.user));
                    }
                } catch (error) {
                    console.error('Erreur mise à jour langue:', error);
                }
            }

            window.dispatchEvent(new Event('languageChanged'));
        },

        // ==========================================
        // INITIALISATION
        // ==========================================
        initialize() {
            console.log('🔧 Initialisation authStore...');

            // Récupérer le timeout sauvegardé
            const savedTimeout = localStorage.getItem('inactivity_timeout');
            if (savedTimeout) {
                this.inactivityTimeout = savedTimeout * 60 * 1000;
            }

            this.setAxiosToken(this.token);

            if (this.isAuthenticated) {
                this.startTokenAutoRefresh();

                // ⭐ Initialiser le timer d'inactivité
                this.initializeInactivityTimer();

                if (!this.user) {
                    this.fetchUser().catch(err => {
                        console.error('Erreur chargement utilisateur:', err);
                    });
                }
            }

            const savedLang = localStorage.getItem('user_language') || 'fr';
            this.setLanguage(savedLang);

            console.log('✅ authStore initialisé - Timeout:', this.getTimeoutDuration(), 'minutes');
        },

        // ==========================================
        // WORKSPACE
        // ==========================================
        setCurrentWorkspace(workspaceId) {
            if (!this.user) {
                console.warn('⚠️ Impossible de définir workspace, pas d\'utilisateur');
                return;
            }

            this.user.current_workspace_id = workspaceId;
            localStorage.setItem('user', JSON.stringify(this.user));
            console.log(`✅ Workspace actuel: ${workspaceId}`);
        },

        setUser(userData) {
            if (!userData) {
                console.warn('⚠️ setUser appelé avec données vides');
                return;
            }

            this.user = userData;
            localStorage.setItem('user', JSON.stringify(userData));
            console.log('✅ Utilisateur mis à jour');
        },

        // ==========================================
        // UTILITAIRES
        // ==========================================
        clearError() {
            this.error = null;
        },

        $reset() {
            this.user = null;
            this.token = null;
            this.isAuthenticated = false;
            this.loading = false;
            this.error = null;
            this.tokenExpiry = null;
            this.stopTokenAutoRefresh();

            localStorage.removeItem('user');
            localStorage.removeItem('auth_token');
            this.setAxiosToken(null);

            console.log('🔄 authStore réinitialisé');
        }
    },
});