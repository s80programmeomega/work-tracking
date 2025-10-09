import api from './axios';

const API_URL = '/labels';

export const labelsApi = {
    /**
     * Get all labels
     */
    getAll() {
        return api.get(API_URL);
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
    reorder(orderedIds) {
        return api.post(`${API_URL}/reorder`, {
            ordered_ids: orderedIds
        });
    }
};
