import api from './axios';

const API_URL = '/labels';

export const labelsApi = {
    /**
     * Get all labels with optional filters
     * @param {Object} params - Query parameters (projet_id, scope: 'all'|'global'|'project')
     */
    getAll(params = {}) {
        return api.get(API_URL, { params });
    },

    /**
     * Get a single label
     */
    getById(id) {
        return api.get(`${API_URL}/${id}`);
    },

    /**
     * Create a new label
     */
    create(data) {
        return api.post(API_URL, data);
    },

    /**
     * Update a label
     */
    update(id, data) {
        return api.put(`${API_URL}/${id}`, data);
    },

    /**
     * Delete a label
     */
    delete(id) {
        return api.delete(`${API_URL}/${id}`);
    },

    /**
     * Reorder labels
     */
    reorder(labels) {
        return api.post(`${API_URL}/reorder`, { labels });
    },

    /**
     * Duplicate a label
     */
    duplicate(id, projetId = null) {
        return api.post(`${API_URL}/${id}/duplicate`, { projet_id: projetId });
    },

    /**
     * Get label usage statistics
     */
    getStats(projetId = null) {
        return api.get(`${API_URL}/stats`, {
            params: { projet_id: projetId }
        });
    },

    /**
     * Get labels for a task
     */
    getTaskLabels(tacheId) {
        return api.get(`/taches/${tacheId}/labels`);
    },

    /**
     * Sync labels for a task
     */
    syncTaskLabels(tacheId, labelIds) {
        return api.post(`/taches/${tacheId}/labels/sync`, { label_ids: labelIds });
    },

    /**
     * Attach a label to a task
     */
    attachToTask(tacheId, labelId) {
        return api.post(`/taches/${tacheId}/labels/attach`, { label_id: labelId });
    },

    /**
     * Detach a label from a task
     */
    detachFromTask(tacheId, labelId) {
        return api.post(`/taches/${tacheId}/labels/detach`, { label_id: labelId });
    },

    /**
     * Detach all labels from a task
     */
    detachAllFromTask(tacheId) {
        return api.delete(`/taches/${tacheId}/labels/detach-all`);
    }
};
