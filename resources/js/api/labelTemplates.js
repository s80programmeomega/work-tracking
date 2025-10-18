import api from './axios';

const API_URL = '/label-templates';

export const labelTemplatesApi = {
    /**
     * Get all label templates with optional type filter
     * @param {string} type - Filter by workflow type ('agile', 'kanban', 'waterfall', 'custom')
     */
    getAll(type = null) {
        const params = type ? { type } : {};
        return api.get(API_URL, { params });
    },

    /**
     * Get a single label template by ID
     */
    getById(id) {
        return api.get(`${API_URL}/${id}`);
    },

    /**
     * Get the default template
     */
    getDefault() {
        return api.get(`${API_URL}/default`);
    },

    /**
     * Get predefined templates (for initial setup)
     */
    getPredefined() {
        return api.get(`${API_URL}/predefined`);
    },

    /**
     * Create a new label template
     * @param {Object} data - Template data with items array
     */
    create(data) {
        return api.post(API_URL, data);
    },

    /**
     * Update a label template
     */
    update(id, data) {
        return api.put(`${API_URL}/${id}`, data);
    },

    /**
     * Delete a label template
     */
    delete(id) {
        return api.delete(`${API_URL}/${id}`);
    },

    /**
     * Apply a template to a project
     * @param {number} templateId - Template ID
     * @param {number} projetId - Project ID
     */
    applyToProject(templateId, projetId) {
        return api.post(`${API_URL}/${templateId}/apply`, { projet_id: projetId });
    },

    /**
     * Duplicate a template
     * @param {number} templateId - Template ID
     * @param {string} newName - Optional new name for the duplicate
     */
    duplicate(templateId, newName = null) {
        return api.post(`${API_URL}/${templateId}/duplicate`, { nom: newName });
    },

    /**
     * Set a template as default
     */
    setAsDefault(templateId) {
        return api.post(`${API_URL}/${templateId}/set-default`);
    }
};
