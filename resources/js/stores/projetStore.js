import { defineStore } from "pinia";
import projetsApi from "@/api/projets";

export const useProjetStore = defineStore("projet", {
    state: () => ({
        projets: [],
        currentProjet: null,
        stats: null,
        loading: false,
        error: null,
        filters: {
            search: "",
            status: null,
            visibility: null,
            responsable_id: null,
            tags: [],
            is_template: null,
            is_favorite: null,
            is_overdue: null,
        },
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 5,
            total: 0,
        },
    }),

    getters: {
        activeProjets: (state) =>
            state.projets.filter((p) => p.status === "active"),
        archivedProjets: (state) =>
            state.projets.filter((p) => p.status === "archived"),
        completedProjets: (state) =>
            state.projets.filter((p) => p.status === "completed"),
        favoriteProjets: (state) => state.projets.filter((p) => p.is_favorite),
        overdueProjets: (state) => state.projets.filter((p) => p.is_overdue),
    },

    actions: {
        // Fetch projets with filters
        async fetchProjets(filters = {}) {
            this.loading = true;
            this.error = null;
            try {
                const mergedFilters = {
                    ...this.filters,
                    ...filters,
                    per_page: this.pagination.per_page,
                };
                const { data } = await projetsApi.getAll(mergedFilters);

                this.projets = data.data || [];
                if (data.meta) {
                    this.pagination = {
                        current_page: data.meta.current_page,
                        last_page: data.meta.last_page,
                        per_page: data.meta.per_page,
                        total: data.meta.total,
                    };
                }
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Erreur lors du chargement des projets";
                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Fetch current user's projets
        async fetchMyProjets(filters = {}) {
            this.loading = true;
            this.error = null;
            try {
                const mergedFilters = { ...this.filters, ...filters };
                const { data } = await projetsApi.getMyProjets(mergedFilters);

                this.projets = data.data || [];
                if (data.meta) {
                    this.pagination = {
                        current_page: data.meta.current_page,
                        last_page: data.meta.last_page,
                        per_page: data.meta.per_page,
                        total: data.meta.total,
                    };
                }
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Erreur lors du chargement des projets";
                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Fetch dashboard stats
        async fetchDashboardStats() {
            try {
                const { data } = await projetsApi.getDashboardStats();
                this.stats = data.data;
            } catch (error) {
                console.error("Error fetching dashboard stats:", error);
            }
        },

        // Fetch single project
        async fetchProjet(id) {
            this.loading = true;
            this.error = null;
            try {
                const { data } = await projetsApi.getById(id);
                this.currentProjet = data.data;
                return data.data;
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Erreur lors du chargement du projet";
                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Create projet
        async createProjet(projetData) {
            this.loading = true;
            this.error = null;
            try {
                const { data } = await projetsApi.create(projetData);
                this.projets.unshift(data.data);
                return data.data;
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Erreur lors de la création du projet";
                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Update projet
        async updateProjet(id, projetData) {
            this.loading = true;
            this.error = null;
            try {
                const { data } = await projetsApi.update(id, projetData);
                const index = this.projets.findIndex((p) => p.id === id);
                if (index !== -1) {
                    this.projets[index] = data.data;
                }
                if (this.currentProjet?.id === id) {
                    this.currentProjet = data.data;
                }
                return data.data;
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Erreur lors de la mise à jour du projet";
                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Delete projet
        async deleteProjet(id) {
            this.loading = true;
            this.error = null;
            try {
                await projetsApi.delete(id);
                this.projets = this.projets.filter((p) => p.id !== id);
                if (this.currentProjet?.id === id) {
                    this.currentProjet = null;
                }
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Erreur lors de la suppression du projet";
                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Archive projet
        async archiveProjet(id) {
            try {
                const { data } = await projetsApi.archive(id);
                const index = this.projets.findIndex((p) => p.id === id);
                if (index !== -1) {
                    this.projets[index] = data.data;
                }
                if (this.currentProjet?.id === id) {
                    this.currentProjet = data.data;
                }
                return data.data;
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Erreur lors de l'archivage du projet";
                throw error;
            }
        },

        // Unarchive projet
        async unarchiveProjet(id) {
            try {
                const { data } = await projetsApi.unarchive(id);
                const index = this.projets.findIndex((p) => p.id === id);
                if (index !== -1) {
                    this.projets[index] = data.data;
                }
                if (this.currentProjet?.id === id) {
                    this.currentProjet = data.data;
                }
                return data.data;
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Erreur lors du désarchivage du projet";
                throw error;
            }
        },

        // Complete projet
        async completeProjet(id) {
            try {
                const { data } = await projetsApi.complete(id);
                const index = this.projets.findIndex((p) => p.id === id);
                if (index !== -1) {
                    this.projets[index] = data.data;
                }
                if (this.currentProjet?.id === id) {
                    this.currentProjet = data.data;
                }
                return data.data;
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Erreur lors de la completion du projet";
                throw error;
            }
        },

        // Clone projet
        async cloneProjet(id, overrides = {}) {
            this.loading = true;
            this.error = null;
            try {
                const { data } = await projetsApi.clone(id, overrides);
                this.projets.unshift(data.data);
                return data.data;
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Erreur lors du clonage du projet";
                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Toggle favorite
        async toggleFavorite(id) {
            try {
                const { data } = await projetsApi.toggleFavorite(id);
                const index = this.projets.findIndex((p) => p.id === id);
                if (index !== -1) {
                    this.projets[index] = data.data;
                }
                if (this.currentProjet?.id === id) {
                    this.currentProjet = data.data;
                }
                return data.data;
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Erreur lors de la mise à jour des favoris";
                throw error;
            }
        },

        // Add member
        async addMember(projetId, memberData) {
            try {
                await projetsApi.addMember(projetId, memberData);
                // Refresh projet to get updated members
                await this.fetchProjet(projetId);
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Erreur lors de l'ajout du membre";
                throw error;
            }
        },

        // Update member
        async updateMember(projetId, userId, permissions) {
            try {
                await projetsApi.updateMember(projetId, userId, permissions);
                // Refresh projet to get updated members
                await this.fetchProjet(projetId);
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Erreur lors de la mise à jour du membre";
                throw error;
            }
        },

        // Remove member
        async removeMember(projetId, userId) {
            try {
                await projetsApi.removeMember(projetId, userId);
                // Refresh projet to get updated members
                await this.fetchProjet(projetId);
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Erreur lors de la suppression du membre";
                throw error;
            }
        },

        // Update filters
        updateFilters(filters) {
            this.filters = { ...this.filters, ...filters };
        },

        // Reset filters
        resetFilters() {
            this.filters = {
                search: "",
                status: null,
                visibility: null,
                responsable_id: null,
                tags: [],
                is_template: null,
                is_favorite: null,
                is_overdue: null,
            };
        },

        // Clear current projet
        clearCurrentProjet() {
            this.currentProjet = null;
        },

        // Clear error
        clearError() {
            this.error = null;
        },
    },
});
