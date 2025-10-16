import { defineStore } from 'pinia';
import { labelsApi } from '../api/labels';

export const useLabelStore = defineStore('label', {
    state: () => ({
        labels: [],
        globalLabels: [],
        projectLabels: {},
        taskLabels: {},
        labelStats: null,
        loading: false,
        error: null
    }),

    getters: {
        /**
         * Get labels ordered by ordre
         */
        orderedLabels: (state) => {
            return [...state.labels].sort((a, b) => a.ordre - b.ordre);
        },

        /**
         * Get label by id
         */
        getLabelById: (state) => (id) => {
            return state.labels.find(label => label.id === id);
        },

        /**
         * Get global labels only
         */
        getGlobalLabels: (state) => {
            return state.labels.filter(label => label.is_global);
        },

        /**
         * Get project-specific labels
         */
        getProjectLabels: (state) => (projetId) => {
            return state.labels.filter(label => label.projet_id === projetId && !label.is_global);
        },

        /**
         * Get labels for a task
         */
        getTaskLabels: (state) => (tacheId) => {
            return state.taskLabels[tacheId] || [];
        }
    },

    actions: {
        /**
         * Fetch all labels with optional filters
         */
        async fetchLabels(params = {}) {
            this.loading = true;
            this.error = null;
            try {
                const response = await labelsApi.getAll(params);
                this.labels = response.data.data || response.data;
            } catch (error) {
                console.error('Error fetching labels:', error);
                this.error = error.response?.data?.message || 'Erreur lors de la récupération des labels';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        /**
         * Fetch labels for a specific project
         */
        async fetchLabelsForProject(projetId) {
            return await this.fetchLabels({ projet_id: projetId, scope: 'all' });
        },

        /**
         * Fetch only global labels
         */
        async fetchGlobalLabels() {
            return await this.fetchLabels({ scope: 'global' });
        },

        /**
         * Create a new label
         */
        async createLabel(data) {
            this.loading = true;
            this.error = null;
            try {
                const response = await labelsApi.create(data);
                this.labels.push(response.data.data);
                return response.data.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors de la création du label';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        /**
         * Update a label
         */
        async updateLabel(id, data) {
            this.loading = true;
            this.error = null;
            try {
                const response = await labelsApi.update(id, data);
                const index = this.labels.findIndex(l => l.id === id);
                if (index !== -1) {
                    this.labels[index] = response.data.data;
                }
                return response.data.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors de la mise à jour du label';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        /**
         * Delete a label
         */
        async deleteLabel(id) {
            this.loading = true;
            this.error = null;
            try {
                await labelsApi.delete(id);
                this.labels = this.labels.filter(l => l.id !== id);
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors de la suppression du label';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        /**
         * Reorder labels
         */
        async reorderLabels(labels) {
            this.loading = true;
            this.error = null;
            try {
                await labelsApi.reorder(labels);
                await this.fetchLabels();
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors du réordonnancement des labels';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        /**
         * Duplicate a label
         */
        async duplicateLabel(id, projetId = null) {
            this.loading = true;
            this.error = null;
            try {
                const response = await labelsApi.duplicate(id, projetId);
                this.labels.push(response.data.data);
                return response.data.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors de la duplication du label';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        /**
         * Fetch label usage statistics
         */
        async fetchLabelStats(projetId = null) {
            this.loading = true;
            this.error = null;
            try {
                const response = await labelsApi.getStats(projetId);
                this.labelStats = response.data.data;
                return this.labelStats;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors de la récupération des statistiques';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        /**
         * Fetch labels for a specific task
         */
        async fetchTaskLabels(tacheId) {
            this.loading = true;
            this.error = null;
            try {
                const response = await labelsApi.getTaskLabels(tacheId);
                this.taskLabels[tacheId] = response.data.data || response.data;
                return this.taskLabels[tacheId];
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors de la récupération des labels de la tâche';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        /**
         * Sync labels for a task
         */
        async syncTaskLabels(tacheId, labelIds) {
            this.loading = true;
            this.error = null;
            try {
                const response = await labelsApi.syncTaskLabels(tacheId, labelIds);
                this.taskLabels[tacheId] = response.data.data || response.data;

                // Update usage counts
                await this.fetchLabels();

                return this.taskLabels[tacheId];
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors de la synchronisation des labels';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        /**
         * Attach a label to a task
         */
        async attachLabelToTask(tacheId, labelId) {
            this.loading = true;
            this.error = null;
            try {
                const response = await labelsApi.attachToTask(tacheId, labelId);
                this.taskLabels[tacheId] = response.data.data || response.data;

                // Update usage count for this label
                const label = this.getLabelById(labelId);
                if (label) {
                    label.usage_count = (label.usage_count || 0) + 1;
                }

                return this.taskLabels[tacheId];
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors de l\'ajout du label';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        /**
         * Detach a label from a task
         */
        async detachLabelFromTask(tacheId, labelId) {
            this.loading = true;
            this.error = null;
            try {
                const response = await labelsApi.detachFromTask(tacheId, labelId);
                this.taskLabels[tacheId] = response.data.data || response.data;

                // Update usage count for this label
                const label = this.getLabelById(labelId);
                if (label && label.usage_count > 0) {
                    label.usage_count--;
                }

                return this.taskLabels[tacheId];
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors de la suppression du label';
                throw error;
            } finally {
                this.loading = false;
            }
        }
    }
});
