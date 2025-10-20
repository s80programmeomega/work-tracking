import { computed } from 'vue';
import { useLabelStore } from '../stores/labelStore';

export function useLabels() {
    const labelStore = useLabelStore();

    const labels = computed(() => labelStore.orderedLabels);
    const globalLabels = computed(() => labelStore.getGlobalLabels);
    const labelStats = computed(() => labelStore.labelStats);
    const loading = computed(() => labelStore.loading);
    const error = computed(() => labelStore.error);

    const fetchLabels = async (params = {}) => {
        await labelStore.fetchLabels(params);
    };

    const fetchLabelsForProject = async (projetId) => {
        await labelStore.fetchLabelsForProject(projetId);
    };

    const fetchGlobalLabels = async () => {
        await labelStore.fetchGlobalLabels();
    };

    const createLabel = async (data) => {
        return await labelStore.createLabel(data);
    };

    const updateLabel = async (id, data) => {
        return await labelStore.updateLabel(id, data);
    };

    const deleteLabel = async (id) => {
        await labelStore.deleteLabel(id);
    };

    const reorderLabels = async (labels) => {
        await labelStore.reorderLabels(labels);
    };

    const duplicateLabel = async (id, projetId = null) => {
        return await labelStore.duplicateLabel(id, projetId);
    };

    const fetchLabelStats = async (projetId = null) => {
        return await labelStore.fetchLabelStats(projetId);
    };

    const getLabelById = (id) => {
        return labelStore.getLabelById(id);
    };

    const getProjectLabels = (projetId) => {
        return labelStore.getProjectLabels(projetId);
    };

    // Task label management
    const fetchTaskLabels = async (tacheId) => {
        return await labelStore.fetchTaskLabels(tacheId);
    };

    const getTaskLabels = (tacheId) => {
        return labelStore.getTaskLabels(tacheId);
    };

    const syncTaskLabels = async (tacheId, labelIds) => {
        return await labelStore.syncTaskLabels(tacheId, labelIds);
    };

    const attachLabelToTask = async (tacheId, labelId) => {
        return await labelStore.attachLabelToTask(tacheId, labelId);
    };

    const detachLabelFromTask = async (tacheId, labelId) => {
        return await labelStore.detachLabelFromTask(tacheId, labelId);
    };

    return {
        // State
        labels,
        globalLabels,
        labelStats,
        loading,
        error,

        // Label CRUD
        fetchLabels,
        fetchLabelsForProject,
        fetchGlobalLabels,
        createLabel,
        updateLabel,
        deleteLabel,
        reorderLabels,
        duplicateLabel,

        // Stats
        fetchLabelStats,

        // Getters
        getLabelById,
        getProjectLabels,

        // Task labels
        fetchTaskLabels,
        getTaskLabels,
        syncTaskLabels,
        attachLabelToTask,
        detachLabelFromTask
    };
}
