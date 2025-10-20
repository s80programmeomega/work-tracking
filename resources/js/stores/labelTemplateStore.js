import { defineStore } from 'pinia';
import { labelTemplatesApi } from '../api/labelTemplates';

export const useLabelTemplateStore = defineStore('labelTemplate', {
    state: () => ({
        templates: [],
        defaultTemplate: null,
        predefinedTemplates: [],
        loading: false,
        error: null
    }),

    getters: {
        /**
         * Get template by ID
         */
        getTemplateById: (state) => (id) => {
            return state.templates.find(template => template.id === id);
        },

        /**
         * Get templates by workflow type
         */
        getTemplatesByType: (state) => (type) => {
            return state.templates.filter(template => template.type_workflow === type);
        }
    },

    actions: {
        /**
         * Fetch all templates
         */
        async fetchTemplates(type = null) {
            this.loading = true;
            this.error = null;
            try {
                const response = await labelTemplatesApi.getAll(type);
                this.templates = response.data.data || response.data;
            } catch (error) {
                console.error('Error fetching templates:', error);
                this.error = error.response?.data?.message || 'Erreur lors de la récupération des templates';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        /**
         * Fetch a single template with items
         */
        async fetchTemplateById(id) {
            this.loading = true;
            this.error = null;
            try {
                const response = await labelTemplatesApi.getById(id);
                const template = response.data.data;

                // Update or add to templates list
                const index = this.templates.findIndex(t => t.id === id);
                if (index !== -1) {
                    this.templates[index] = template;
                } else {
                    this.templates.push(template);
                }

                return template;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors de la récupération du template';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        /**
         * Fetch default template
         */
        async fetchDefaultTemplate() {
            this.loading = true;
            this.error = null;
            try {
                const response = await labelTemplatesApi.getDefault();
                this.defaultTemplate = response.data.data;
                return this.defaultTemplate;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors de la récupération du template par défaut';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        /**
         * Fetch predefined templates
         */
        async fetchPredefinedTemplates() {
            this.loading = true;
            this.error = null;
            try {
                const response = await labelTemplatesApi.getPredefined();
                this.predefinedTemplates = response.data.data;
                return this.predefinedTemplates;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors de la récupération des templates prédéfinis';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        /**
         * Create a new template
         */
        async createTemplate(data) {
            this.loading = true;
            this.error = null;
            try {
                const response = await labelTemplatesApi.create(data);
                const template = response.data.data;
                this.templates.push(template);

                // If this is set as default, update defaultTemplate
                if (template.is_default) {
                    this.defaultTemplate = template;
                }

                return template;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors de la création du template';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        /**
         * Update a template
         */
        async updateTemplate(id, data) {
            this.loading = true;
            this.error = null;
            try {
                const response = await labelTemplatesApi.update(id, data);
                const template = response.data.data;

                // Update in templates list
                const index = this.templates.findIndex(t => t.id === id);
                if (index !== -1) {
                    this.templates[index] = template;
                }

                // If this is set as default, update defaultTemplate
                if (template.is_default) {
                    this.defaultTemplate = template;
                }

                return template;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors de la mise à jour du template';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        /**
         * Delete a template
         */
        async deleteTemplate(id) {
            this.loading = true;
            this.error = null;
            try {
                await labelTemplatesApi.delete(id);
                this.templates = this.templates.filter(t => t.id !== id);

                // If this was the default, clear defaultTemplate
                if (this.defaultTemplate?.id === id) {
                    this.defaultTemplate = null;
                }
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors de la suppression du template';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        /**
         * Apply a template to a project
         */
        async applyTemplateToProject(templateId, projetId) {
            this.loading = true;
            this.error = null;
            try {
                const response = await labelTemplatesApi.applyToProject(templateId, projetId);
                return response.data.data; // Returns created labels
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors de l\'application du template';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        /**
         * Duplicate a template
         */
        async duplicateTemplate(templateId, newName = null) {
            this.loading = true;
            this.error = null;
            try {
                const response = await labelTemplatesApi.duplicate(templateId, newName);
                const template = response.data.data;
                this.templates.push(template);
                return template;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors de la duplication du template';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        /**
         * Set a template as default
         */
        async setAsDefault(templateId) {
            this.loading = true;
            this.error = null;
            try {
                const response = await labelTemplatesApi.setAsDefault(templateId);
                const template = response.data.data;

                // Update all templates in list
                this.templates = this.templates.map(t => ({
                    ...t,
                    is_default: t.id === templateId
                }));

                this.defaultTemplate = template;

                return template;
            } catch (error) {
                this.error = error.response?.data?.message || 'Erreur lors de la définition du template par défaut';
                throw error;
            } finally {
                this.loading = false;
            }
        }
    }
});
