import { defineStore } from "pinia";
import activitesApi from "@/api/activites";

export const useActiviteStore = defineStore("activite", {
    state: () => ({
        activites: [],
        currentActivite: null,
        loading: false,
        error: null,
        filters: {
            search: "",
            projet_id: null,
            responsable_id: null,
            status: null,
            is_overdue: null,
        },
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 10,
            total: 0,
        },
    }),

    getters: {
        activeActivites: (state) =>
            state.activites.filter((a) => a.status === "active"),
        archivedActivites: (state) =>
            state.activites.filter((a) => a.status === "archived"),
        overdueActivites: (state) =>
            state.activites.filter((a) => a.is_overdue),
    },

    actions: {
        // Fetch activites with filters
        async fetchActivites(filters = {}) {
            this.loading = true;
            this.error = null;
            try {
                const mergedFilters = {
                    ...this.filters,
                    ...filters,
                    per_page: this.pagination.per_page,
                };
                const { data } = await activitesApi.getAll(mergedFilters);

                this.activites = data.data || [];
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
                    "Erreur lors du chargement des activités";
                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Fetch activities for a project
        async fetchActivitiesForProjet(projetId, filters = {}) {
            this.loading = true;
            this.error = null;
            try {
                const mergedFilters = {
                    ...this.filters,
                    ...filters,
                    per_page: this.pagination.per_page,
                };
                const { data } = await activitesApi.getForProjet(
                    projetId,
                    mergedFilters,
                );

                this.activites = data.data || [];
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
                    "Erreur lors du chargement des activités";
                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Fetch single activity
        async fetchActivite(id) {
            this.loading = true;
            this.error = null;
            try {
                const { data } = await activitesApi.getById(id);
                this.currentActivite = data.data;
                return data.data;
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Erreur lors du chargement de l'activité";
                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Create activity
        async createActivite(activiteData) {
            this.loading = true;
            this.error = null;
            try {
                const { data } = await activitesApi.create(activiteData);
                this.activites.unshift(data.data);
                return data.data;
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Erreur lors de la création de l'activité";
                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Update activity
        async updateActivite(id, activiteData) {
            this.loading = true;
            this.error = null;
            try {
                const { data } = await activitesApi.update(id, activiteData);
                const index = this.activites.findIndex((a) => a.id === id);
                if (index !== -1) {
                    this.activites[index] = data.data;
                }
                if (this.currentActivite?.id === id) {
                    this.currentActivite = data.data;
                }
                return data.data;
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Erreur lors de la mise à jour de l'activité";
                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Delete activity
        async deleteActivite(id) {
            this.loading = true;
            this.error = null;
            try {
                await activitesApi.delete(id);
                this.activites = this.activites.filter((a) => a.id !== id);
                if (this.currentActivite?.id === id) {
                    this.currentActivite = null;
                }
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Erreur lors de la suppression de l'activité";
                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Archive activity
        async archiveActivite(id) {
            try {
                const { data } = await activitesApi.archive(id);
                const index = this.activites.findIndex((a) => a.id === id);
                if (index !== -1) {
                    this.activites[index] = data.data;
                }
                if (this.currentActivite?.id === id) {
                    this.currentActivite = data.data;
                }
                return data.data;
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Erreur lors de l'archivage de l'activité";
                throw error;
            }
        },

        // Unarchive activity
        async unarchiveActivite(id) {
            try {
                const { data } = await activitesApi.unarchive(id);
                const index = this.activites.findIndex((a) => a.id === id);
                if (index !== -1) {
                    this.activites[index] = data.data;
                }
                if (this.currentActivite?.id === id) {
                    this.currentActivite = data.data;
                }
                return data.data;
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Erreur lors du désarchivage de l'activité";
                throw error;
            }
        },

        // Duplicate activity
        async duplicateActivite(id, overrides = {}) {
            this.loading = true;
            this.error = null;
            try {
                const { data } = await activitesApi.duplicate(id, overrides);
                this.activites.unshift(data.data);
                return data.data;
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Erreur lors de la duplication de l'activité";
                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Reorder activities
        async reorderActivites(orderedIds) {
            try {
                await activitesApi.reorder(orderedIds);
                // Update local state
                orderedIds.forEach((id, index) => {
                    const activite = this.activites.find((a) => a.id === id);
                    if (activite) {
                        activite.ordre = index;
                    }
                });
                // Re-sort by ordre
                this.activites.sort((a, b) => a.ordre - b.ordre);
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Erreur lors du réordonnement des activités";
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
                projet_id: null,
                responsable_id: null,
                status: null,
                is_overdue: null,
            };
        },

        // Clear current activity
        clearCurrentActivite() {
            this.currentActivite = null;
        },

        // Clear error
        clearError() {
            this.error = null;
        },
    },
});
