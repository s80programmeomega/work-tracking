import { defineStore } from 'pinia';
import { labelsApi } from '../api/labels';

export const useLabelStore = defineStore('label', {
    state: () => ({
        labels: [],
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
        }
    },

    actions: {
        /**
         * Fetch all labels
         */
        async fetchLabels() {
            this.loading = true;
            this.error = null;
            try {
                const response = await labelsApi.getAll();
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
        async reorderLabels(orderedIds) {
            this.loading = true;
            this.error = null;
            try {
                await labelsApi.reorder(orderedIds);
                await this.fetchLabels();
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors du réordonnancement des labels';
                throw error;
            } finally {
                this.loading = false;
            }
        }
    }
});
