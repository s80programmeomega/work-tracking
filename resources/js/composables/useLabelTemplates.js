import { computed } from 'vue';
import { useLabelTemplateStore } from '../stores/labelTemplateStore';

export function useLabelTemplates() {
    const templateStore = useLabelTemplateStore();

    const templates = computed(() => templateStore.templates);
    const defaultTemplate = computed(() => templateStore.defaultTemplate);
    const predefinedTemplates = computed(() => templateStore.predefinedTemplates);
    const loading = computed(() => templateStore.loading);
    const error = computed(() => templateStore.error);

    const fetchTemplates = async (type = null) => {
        await templateStore.fetchTemplates(type);
    };

    const fetchTemplateById = async (id) => {
        return await templateStore.fetchTemplateById(id);
    };

    const fetchDefaultTemplate = async () => {
        return await templateStore.fetchDefaultTemplate();
    };

    const fetchPredefinedTemplates = async () => {
        return await templateStore.fetchPredefinedTemplates();
    };

    const createTemplate = async (data) => {
        return await templateStore.createTemplate(data);
    };

    const updateTemplate = async (id, data) => {
        return await templateStore.updateTemplate(id, data);
    };

    const deleteTemplate = async (id) => {
        await templateStore.deleteTemplate(id);
    };

    const applyTemplateToProject = async (templateId, projetId) => {
        return await templateStore.applyTemplateToProject(templateId, projetId);
    };

    const duplicateTemplate = async (templateId, newName = null) => {
        return await templateStore.duplicateTemplate(templateId, newName);
    };

    const setAsDefault = async (templateId) => {
        return await templateStore.setAsDefault(templateId);
    };

    const getTemplateById = (id) => {
        return templateStore.getTemplateById(id);
    };

    const getTemplatesByType = (type) => {
        return templateStore.getTemplatesByType(type);
    };

    return {
        // State
        templates,
        defaultTemplate,
        predefinedTemplates,
        loading,
        error,

        // CRUD
        fetchTemplates,
        fetchTemplateById,
        fetchDefaultTemplate,
        fetchPredefinedTemplates,
        createTemplate,
        updateTemplate,
        deleteTemplate,

        // Actions
        applyTemplateToProject,
        duplicateTemplate,
        setAsDefault,

        // Getters
        getTemplateById,
        getTemplatesByType
    };
}
